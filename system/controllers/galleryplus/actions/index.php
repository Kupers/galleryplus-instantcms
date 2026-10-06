<?php

class actionGalleryplusIndex extends cmsAction {

    public function run() {

        $page = $this->request->get('page', 1);
        $mode = $this->request->get('mode', $this->options['default_mode'] ?? 'albums');
        $explore = $this->request->get('explore', 'recent');
        $category_slug = $this->request->get('category', '');
        $tags_param = (string)$this->request->get('tags', '');

        $user = $this->cms_user;

        $this->model->preset_small  = $this->options['preset_small'] ?? 'galleryplus_thumb';
        $this->model->preset_big    = $this->options['preset_big'] ?? 'galleryplus_big';
        $this->model->preset_nocrop = $this->options['preset_nocrop'] ?? 'galleryplus_nocrop';
        $this->model->original_paid = $this->billingEnabledFeature('download_original');
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

        $active_tags = $this->resolveActiveTags($tags_param);
        $tag_ids     = array_column($active_tags, 'id');
        $show_tag_filter = !empty($this->options['use_photo_tags']) && cmsCore::isModelExists('tags');

        if ($this->request->isAjax() && $page > 1) {
            return $this->loadMore($page, $explore, $category_id, $tag_ids);
        }

        $perpage = $this->options['limit'] ?? 24;

        $show_adult_to_guests = $this->options['show_adult_to_guests'] ?? 0;
        $include_adult_for_guests = !$this->cms_user->id && $show_adult_to_guests;
        $show_adult_in_feed = array_key_exists('show_adult_in_feed', $this->options) ? (bool)$this->options['show_adult_in_feed'] : true;
        $show_adult = ($show_adult_in_feed && $this->cms_user->id) || !empty($show_adult_to_guests);

        $this->applyTagFilter($tag_ids);

        $total = $category_id
            ? $this->model->getPhotosCount(null, $category_id, $include_adult_for_guests, $show_adult)
            : $this->model->getPhotosCount(null, 0, $include_adult_for_guests, $show_adult);

        $this->applyExploreOrder($explore);
        $this->applyTagFilter($tag_ids);

        $photos = $this->model->getPhotos($page, $perpage, $category_id, $include_adult_for_guests, $show_adult);
        if (!$photos) { $photos = []; }
        $has_next = count($photos) > $perpage;
        if ($has_next) { array_pop($photos); }

        $photos = $this->applyAdultFilter($photos);

        // Attach likes data
        $user_id = $this->cms_user->id;
        $ids = array_column($photos, 'id');
        $likes_data = $this->model->getPhotosLikesBatch($ids, $user_id);
        $fav_data   = $this->model->getPhotosFavoritesBatch($ids, $user_id);
        foreach ($photos as &$p) {
            $pid = $p['id'];
            $p['likes_count'] = $likes_data[$pid]['count'] ?? 0;
            $p['is_liked']    = $likes_data[$pid]['liked'] ?? false;
            $p['is_favorite'] = !empty($fav_data[$pid]);
        }
        unset($p);

        $can_select = $this->canSelect();

        $tag_filter = $this->buildTagFilter($mode, $explore, $category_slug, $active_tags);

        if ($this->request->isAjax() && $this->request->get('gp_filter', '') !== '') {
            return $this->filterResult($photos, $has_next, $mode, $perpage, $total, $current_category, $tag_filter);
        }

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
            'show_tag_filter'  => $show_tag_filter,
            'active_tags'      => $active_tags,
            'tags_query'       => $tag_filter['query'],
            'tag_filter_base'  => $tag_filter['base'],
            'show_lightbox_desc' => !empty($this->options['show_lightbox_desc']),
            'truncate_lightbox_desc' => isset($this->options['truncate_lightbox_desc']) ? !empty($this->options['truncate_lightbox_desc']) : 1,
            'lightbox_desc_limit' => isset($this->options['lightbox_desc_limit']) ? (int)$this->options['lightbox_desc_limit'] : 300,
        ]);
    }

    /**
     * AJAX-ответ на смену тегов: карточки первой страницы + пагинация + пустое состояние.
     * Страница целиком не перерисовывается — JS заменяет содержимое сетки и пересобирает раскладку.
     */
    private function filterResult($photos, $has_next, $mode, $perpage, $total, $current_category, $tag_filter) {

        $html = '';
        foreach ($photos as $photo) {
            $html .= $this->renderPhotoCard($photo);
        }

        $pagination = '';
        if ($mode === 'paged' && $total > $perpage) {
            $query = ['mode' => 'paged'];
            if (!empty($current_category)) { $query['category'] = $current_category['slug']; }
            if (!empty($tag_filter['tags'])) { $query['tags'] = $tag_filter['tags']; }
            $pagination = html_pagebar(1, $perpage, $total, href_to('galleryplus'), $query);
        }

        $empty_html = '';
        if (!$photos) {
            $msg    = defined('LANG_GALLERYPLUS_TAG_FILTER_EMPTY') ? LANG_GALLERYPLUS_TAG_FILTER_EMPTY : 'Ничего не найдено по этим тегам';
            $reset  = defined('LANG_GALLERYPLUS_TAG_FILTER_RESET') ? LANG_GALLERYPLUS_TAG_FILTER_RESET : 'Сбросить';
            $base   = htmlspecialchars($tag_filter['base']);
            $empty_html = '<div class="galleryplus-empty"><span class="galleryplus-empty-tagfilter">' . htmlspecialchars($msg) . '</span>'
                . '<a class="galleryplus-tagfilter-reset" href="' . $base . '">' . htmlspecialchars($reset) . '</a></div>';
        }

        return $this->cms_template->renderJSON([
            'html'       => $html,
            'page'       => $mode === 'infinite' ? 2 : 1,
            'has_next'   => $mode === 'infinite' ? (bool)$has_next : false,
            'total'      => (int)$total,
            'mode'       => $mode,
            'empty'      => !$photos,
            'empty_html' => $empty_html,
            'pagination' => $pagination,
        ]);
    }

    /**
     * Разбирает ?tags=a,b,c в список существующих тегов (id + название).
     * Несуществующие/пустые отбрасываются, максимум 10 штук.
     */
    private function resolveActiveTags($raw) {

        $result = [];

        $raw = trim((string)$raw);
        if ($raw === '' || $raw === false) { return $result; }
        if (!cmsCore::isModelExists('tags')) { return $result; }

        $names = preg_split('/[,\s]+/u', $raw);
        $names = array_slice(array_filter(array_map('trim', $names), function($v) {
            return $v !== '' && $v !== '#';
        }), 0, 10);

        if (!$names) { return $result; }

        $tags_model = cmsCore::getModel('tags');

        foreach ($names as $name) {
            $name = ltrim($name, '#');
            if ($name === '') { continue; }
            $row = $tags_model->getTagByTag($name);
            if (!$row || empty($row['id'])) { continue; }
            $id = (int)$row['id'];
            $result[$id] = [
                'id'  => $id,
                'tag' => $row['tag'],
            ];
        }

        return array_values($result);
    }

    /**
     * Фильтр по тегам: фото, у которых есть ЛЮБОЙ из выбранных тегов.
     * Условие подставляется подзапросом, поэтому дубликатов строк не бывает.
     */
    private function applyTagFilter(array $tag_ids) {

        if (empty($tag_ids)) { return; }

        $ids = implode(',', array_map('intval', $tag_ids));

        $this->model->filter(
            "i.id IN (SELECT tb.target_id FROM {#}tags_bind tb" .
            " WHERE tb.target_controller = 'galleryplus'" .
            " AND tb.target_subject = 'photo'" .
            " AND tb.tag_id IN ({$ids}))"
        );
    }

    /**
     * Базовая ссылка для переходов фильтра (текущий режим/сортировка/категория)
     * и готовая query-строка с выбранными тегами.
     */
    private function buildTagFilter($mode, $explore, $category_slug, array $active_tags) {

        $qs = [];
        if ($mode && $mode !== 'albums') { $qs['mode'] = $mode; }
        if ($explore && $explore !== 'recent') { $qs['explore'] = $explore; }
        if ($category_slug) { $qs['category'] = $category_slug; }

        $base = href_to('galleryplus');
        if ($qs) { $base .= '?' . http_build_query($qs); }

        $query = '';
        if ($active_tags) {
            $names = array_map(function($t) { return $t['tag']; }, $active_tags);
            $query = '&tags=' . rawurlencode(implode(',', $names));
        }

        return [
            'base'  => $base,
            'query' => $query,
            'tags'  => $active_tags ? implode(',', array_map(function($t) { return $t['tag']; }, $active_tags)) : '',
        ];
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
            'truncate_lightbox_desc' => isset($this->options['truncate_lightbox_desc']) ? !empty($this->options['truncate_lightbox_desc']) : 1,
            'lightbox_desc_limit' => isset($this->options['lightbox_desc_limit']) ? (int)$this->options['lightbox_desc_limit'] : 300,
        ]);
    }

    public function loadMore($page, $explore = 'recent', $category_id = 0, array $tag_ids = []) {
        $this->model->original_paid = $this->billingEnabledFeature('download_original');
        $this->applyExploreOrder($explore);

        $show_adult_to_guests = $this->options['show_adult_to_guests'] ?? 0;
        $include_adult_for_guests = !$this->cms_user->id && $show_adult_to_guests;
        $show_adult_in_feed = array_key_exists('show_adult_in_feed', $this->options) ? (bool)$this->options['show_adult_in_feed'] : true;
        $show_adult = ($show_adult_in_feed && $this->cms_user->id) || !empty($show_adult_to_guests);

        $perpage = $this->options['limit'] ?? 24;
        $this->applyTagFilter($tag_ids);
        $photos = $this->model->getPhotos($page, $perpage, $category_id, $include_adult_for_guests, $show_adult);
        if (!$photos) { $photos = []; }
        $has_next = count($photos) > $perpage;
        if ($has_next) { array_pop($photos); }

        $photos = $this->applyAdultFilter($photos);

        // Attach likes data
        $user_id = $this->cms_user->id;
        $ids = array_column($photos, 'id');
        $likes_data = $this->model->getPhotosLikesBatch($ids, $user_id);
        $fav_data   = $this->model->getPhotosFavoritesBatch($ids, $user_id);
        foreach ($photos as &$p) {
            $pid = $p['id'];
            $p['likes_count'] = $likes_data[$pid]['count'] ?? 0;
            $p['is_liked']    = $likes_data[$pid]['liked'] ?? false;
            $p['is_favorite'] = !empty($fav_data[$pid]);
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
        $is_favorite = !empty($photo['is_favorite']);
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
            'favorite' => $is_favorite,
            'owner_id' => $photo['user_id'],
            'comments' => $comments_count,
            'desc'     => $photo['content'] ?? '',
        ], JSON_UNESCAPED_UNICODE));
        $blur_class = $is_adult ? ' galleryplus-item--adult' : '';
        $fav_btn = '<button class="galleryplus-fav-btn galleryplus-fav-btn--card' . ($is_favorite ? ' favorited' : '') . '" data-photo-id="' . $photo['id'] . '" title="' . (defined('LANG_GALLERYPLUS_FAVORITE') ? LANG_GALLERYPLUS_FAVORITE : 'В избранное') . '">' . ($is_favorite ? '&#9733;' : '&#9734;') . '</button>';
        $checkbox = $this->canSelect()
            ? '<label class="galleryplus-checkbox-wrap"><input type="checkbox" class="galleryplus-select-cb" data-id="' . $photo['id'] . '"><span class="galleryplus-checkbox"></span></label>'
            : '';
        return '<div class="galleryplus-item' . $blur_class . '" data-object="' . $data . '">'
            . $fav_btn
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
