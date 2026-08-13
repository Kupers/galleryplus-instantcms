<?php

class actionGalleryplusIndex extends cmsAction {

    public function run() {

        $page = $this->request->get('page', 1);
        $mode = $this->request->get('mode', $this->options['default_mode'] ?? 'albums');
        $explore = $this->request->get('explore', 'recent');
        $category_slug = $this->request->get('category', '');

        $user = $this->cms_user;

        $this->model->preset_small  = $this->options['preset_small'] ?? 'galleryplus_thumb';
        $this->model->preset_big    = $this->options['preset_big'] ?? 'galleryplus_big';
        $this->model->preset_nocrop = $this->options['preset_nocrop'] ?? 'galleryplus_nocrop';
        $this->model->adult_karma   = (int)($this->options['adult_karma'] ?? 0);
        $this->model->user_karma    = $user->karma ?? 0;
        $this->model->adult_rating  = (int)($this->options['adult_rating'] ?? 0);
        $this->model->user_rating   = $user->rating ?? 0;

        if (!empty($this->options['is_comments_photo'])) {
            $this->cms_template->addTplJSName('jquery-scroll');
            $this->cms_template->addTplJSName('comments');
        }

        $current_category = null;
        $categories = [];
        $use_categories = !empty($this->options['use_categories']);

        if ($use_categories) {
            $categories = $this->model->getCategoriesAll();

            if ($category_slug) {
                $current_category = $this->model->getGalleryCategoryBySlug($category_slug);
                if (!$current_category) { return cmsCore::error404(); }
                $current_category['url'] = href_to('galleryplus', 'category', [$current_category['slug']]) . '.html';
            }
        }

        $category_id = $current_category ? $current_category['id'] : 0;

        if ($current_category) {
            $this->cms_template->addBreadcrumb(
                defined('LANG_GALLERYPLUS_TITLE') ? LANG_GALLERYPLUS_TITLE : 'Gallery',
                href_to('galleryplus')
            );
            $this->cms_template->addBreadcrumb($current_category['title']);
        } else {
            $this->cms_template->addBreadcrumb(
                defined('LANG_GALLERYPLUS_TITLE') ? LANG_GALLERYPLUS_TITLE : 'Gallery'
            );
        }

        if ($mode === 'albums') {
            return $this->showAlbums($page, $category_id);
        }

        if ($this->request->isAjax() && $page > 1) {
            return $this->loadMore($page, $explore, $category_id);
        }

        $perpage = $this->options['limit'] ?? 24;

        $show_adult_to_guests = $this->options['show_adult_to_guests'] ?? 0;
        $include_adult_for_guests = !$this->cms_user->id && $show_adult_to_guests;

        $total = $category_id
            ? $this->model->getPhotosCount(null, $category_id, $include_adult_for_guests)
            : $this->model->getPhotosCount(null, 0, $include_adult_for_guests);

        $this->applyExploreOrder($explore);

        $photos = $this->model->getPhotos($page, $perpage, $category_id, $include_adult_for_guests);
        if (!$photos) { $photos = []; }
        $has_next = count($photos) > $perpage;
        if ($has_next) { array_pop($photos); }

        $photos = $this->applyAdultFilter($photos);

        // Attach likes data
        $user_id = $this->cms_user->id;
        $ids = array_column($photos, 'id');
        $likes_data = $this->model->getPhotosLikesBatch($ids, $user_id);
        foreach ($photos as &$p) {
            $pid = $p['id'];
            $p['likes_count'] = $likes_data[$pid]['count'] ?? 0;
            $p['is_liked']    = $likes_data[$pid]['liked'] ?? false;
        }
        unset($p);

        $can_select = $this->canSelect();

        return $this->cms_template->render('index', [
            'photos'           => $photos,
            'mode'             => $mode,
            'page'             => $page,
            'has_next'         => $has_next,
            'total'            => $mode === 'paged' ? $total : 0,
            'perpage'          => $perpage,
            'user'             => $this->cms_user,
            'explore'          => $explore,
            'is_guest'         => !$this->cms_user->id,
            'can_select'       => $can_select,
            'current_category' => $current_category,
            'categories'       => $categories,
            'use_categories'   => $use_categories,
            'use_album_tags'   => !empty($this->options['use_album_tags']),
            'use_photo_tags'   => !empty($this->options['use_photo_tags']),
            'show_lightbox_desc' => !empty($this->options['show_lightbox_desc']),
        ]);
    }

    public function showAlbums($page, $category_id = 0) {

        $user = $this->cms_user;

        $perpage = $this->options['limit'] ?? 24;
        $show_adult_to_guests = $this->options['show_adult_to_guests'] ?? 0;
        $include_adult_for_guests = !$user->id && $show_adult_to_guests;

        if ($category_id) {
            $albums = $this->model->getAlbumsByCategory($category_id, $page, $perpage, $user->id, $include_adult_for_guests);
            $total = $this->model->getAlbumsCountByCategory($category_id, $user->id, $include_adult_for_guests);
        } else {
            $albums = $this->model->getAlbums($page, $perpage, $user->id, $include_adult_for_guests);
            $total = $this->model->getAlbumsCount($user->id, $include_adult_for_guests);
        }
        if (!$albums) { $albums = []; }

        if (!isset($this->options['hide_empty_albums']) || !empty($this->options['hide_empty_albums'])) {
            $albums = array_values(array_filter($albums, function($a) {
                return ($a['photo_count'] ?? 0) > 0;
            }));
        }

        $has_next = $albums && (count($albums) > $perpage);
        if ($has_next) { array_pop($albums); }

        // Mark albums owned by current user so template can skip blur
        $adult_karma = (int)($this->options['adult_karma'] ?? 0);
        $user_karma = $user->karma ?? 0;
        $is_admin = $user->is_admin;
        $is_moderator = false;
        if ($user->id) {
            $mod = cmsCore::getModel('moderation');
            $is_moderator = $mod->userIsContentModerator('galleryplus', $user->id);
        }
        foreach ($albums as &$a) {
            $a['is_owner'] = $user->id && $a['user_id'] == $user->id;
            $a['can_view_adult'] = $a['is_owner'] || $is_admin || $is_moderator;
        }
        unset($a);

        $use_categories = !empty($this->options['use_categories']);
        $current_category = null;
        $categories = [];
        if ($use_categories) {
            $categories = $this->model->getCategoriesAll();
            if ($category_id) {
                $current_category = $this->model->getCategoryById($category_id);
                if ($current_category) {
                    $current_category['url'] = href_to('galleryplus', 'category', [$current_category['slug']]) . '.html';
                }
            }
        }

        if ($current_category) {
            $this->cms_template->addBreadcrumb(
                defined('LANG_GALLERYPLUS_TITLE') ? LANG_GALLERYPLUS_TITLE : 'Gallery',
                href_to('galleryplus')
            );
            $this->cms_template->addBreadcrumb($current_category['title']);
        } else {
            $this->cms_template->addBreadcrumb(
                defined('LANG_GALLERYPLUS_TITLE') ? LANG_GALLERYPLUS_TITLE : 'Gallery'
            );
        }

        $use_album_tags = !empty($this->options['use_album_tags']);

        if ($use_album_tags && $albums) {
            $tags_model = cmsCore::getModel('tags');
            foreach ($albums as &$a) {
                $a['tags'] = $tags_model->getTagsForTarget('galleryplus', 'album', $a['id']);
            }
            unset($a);
        }

        return $this->cms_template->render('index', [
            'albums'           => $albums,
            'mode'             => 'albums',
            'page'             => $page,
            'has_next'         => false,
            'total'            => $total,
            'perpage'          => $perpage,
            'user'             => $this->cms_user,
            'explore'          => 'recent',
            'can_select'       => $this->canSelect(),
            'current_category' => $current_category,
            'categories'       => $categories,
            'use_categories'   => $use_categories,
            'use_album_tags'   => $use_album_tags,
            'use_photo_tags'   => !empty($this->options['use_photo_tags']),
            'show_lightbox_desc' => !empty($this->options['show_lightbox_desc']),
        ]);
    }

    public function loadMore($page, $explore = 'recent', $category_id = 0) {
        $this->applyExploreOrder($explore);

        $show_adult_to_guests = $this->options['show_adult_to_guests'] ?? 0;
        $include_adult_for_guests = !$this->cms_user->id && $show_adult_to_guests;

        $perpage = $this->options['limit'] ?? 24;
        $photos = $this->model->getPhotos($page, $perpage, $category_id, $include_adult_for_guests);
        if (!$photos) { $photos = []; }
        $has_next = count($photos) > $perpage;
        if ($has_next) { array_pop($photos); }

        $photos = $this->applyAdultFilter($photos);

        // Attach likes data
        $user_id = $this->cms_user->id;
        $ids = array_column($photos, 'id');
        $likes_data = $this->model->getPhotosLikesBatch($ids, $user_id);
        foreach ($photos as &$p) {
            $pid = $p['id'];
            $p['likes_count'] = $likes_data[$pid]['count'] ?? 0;
            $p['is_liked']    = $likes_data[$pid]['liked'] ?? false;
        }
        unset($p);

        $html = '';
        foreach ($photos as $photo) {
            $html .= $this->renderPhotoCard($photo);
        }

        return $this->cms_template->renderJSON([
            'html'     => $html,
            'page'     => $page + 1,
            'has_next' => $has_next,
        ]);
    }

    private function applyExploreOrder($explore) {
        switch ($explore) {
            case 'popular':
                $this->model->orderBy('hits_count', 'desc');
                break;
            case 'trending':
                $this->model->orderBy('comments', 'desc');
                break;
            case 'liked':
                $this->model->orderByRaw('(SELECT COUNT(*) FROM {#}galleryplus_likes WHERE target_type=\'photo\' AND target_id=i.id) desc');
                break;
            default:
                $this->model->orderBy(
                    $this->options['ordering'] ?? 'date_pub',
                    $this->options['orderto'] ?? 'desc'
                );
                break;
        }
    }

    private function canSelect() {
        if (!$this->cms_user->id) { return false; }
        if ($this->cms_user->is_admin) { return true; }
        try {
            if (cmsCore::isModelExists('moderation')) {
                $mod = cmsCore::getModel('moderation');
                if ($mod && method_exists($mod, 'userIsContentModerator')) {
                    return $mod->userIsContentModerator('galleryplus', $this->cms_user->id);
                }
            }
        } catch (\Throwable $e) {}
        return false;
    }

    private function renderPhotoCard($photo) {
        $title = htmlspecialchars($photo['title'] ?: $photo['filename'] ?? '');
        $author = htmlspecialchars($photo['user']['nickname'] ?? '');
        $avatar = $photo['user']['avatar'] ?? '';
        $is_adult = !empty($photo['is_adult']);
        $is_liked = !empty($photo['is_liked']);
        $likes_count = $photo['likes_count'] ?? 0;
        $comments_count = $photo['comments'] ?? 0;
        $data = htmlspecialchars(json_encode([
            'id'       => $photo['id'],
            'url'      => $photo['url'],
            'src'      => $photo['url_big'],
            'nocrop'   => $photo['url_nocrop'] ?: '',
            'thumb'    => $photo['url_thumb'],
            'title'    => $title,
            'author'   => $author,
            'avatar'   => $avatar,
            'adult'    => $is_adult,
            'likes'    => $likes_count,
            'liked'    => $is_liked,
            'owner_id' => $photo['user_id'],
            'comments' => $comments_count,
            'desc'     => $photo['content'] ?? '',
        ], JSON_UNESCAPED_UNICODE));
        $blur_class = $is_adult ? ' galleryplus-item--adult' : '';
        $checkbox = $this->canSelect()
            ? '<label class="galleryplus-checkbox-wrap"><input type="checkbox" class="galleryplus-select-cb" data-id="' . $photo['id'] . '"><span class="galleryplus-checkbox"></span></label>'
            : '';
        return '<div class="galleryplus-item' . $blur_class . '" data-object="' . $data . '">'
            . $checkbox
            . '<a href="' . $photo['url'] . '" class="galleryplus-viewer-link">'
            . '<img src="' . $photo['url_thumb'] . '" alt="' . $title . '" loading="lazy" width="' . ($photo['width'] ?? 0) . '" height="' . ($photo['height'] ?? 0) . '" class="' . ($is_adult ? 'galleryplus-blurred' : '') . '">'
            . ($is_adult ? '<div class="galleryplus-adult-badge">18+</div>' : '')
            . '</a>'
            . '<div class="galleryplus-item-overlay">'
            . '<a href="' . $photo['url'] . '" class="galleryplus-item-overlay-title">' . $title . '</a>'
            . '<div class="galleryplus-item-overlay-bottom">'
            . '<a href="' . href_to('users', $photo['user']['id']) . '" class="galleryplus-item-author">' . $author . '</a>'
            . '<div class="galleryplus-item-overlay-stats">'
            . '<span class="galleryplus-item-likes">&#10084; ' . $likes_count . '</span>'
            . '<span class="galleryplus-item-comments">&#9993; ' . $comments_count . '</span>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

}
