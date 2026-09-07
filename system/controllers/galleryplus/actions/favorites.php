<?php

class actionGalleryplusFavorites extends cmsAction {

    public function run($user_id = 0) {

        $user = $this->cms_user;

        // Свои избранные
        if (!$user_id) { $user_id = $user->id; }
        $user_id = (int)$user_id;

        // Избранное — личное: чужое избранное не показывает
        if (!$user_id || !$user->id || (int)$user->id !== $user_id) {
            return cmsCore::error404();
        }

        $profile_user = $user_id ? cmsCore::getModel('users')->filterEqual('id', $user_id)->getUser() : false;
        if (!$profile_user) { return cmsCore::error404(); }

        $this->cms_template->addBreadcrumb(
            defined('LANG_GALLERYPLUS_TITLE') ? LANG_GALLERYPLUS_TITLE : 'Gallery',
            href_to('galleryplus')
        );
        $this->cms_template->addBreadcrumb(defined('LANG_GALLERYPLUS_FAVORITES') ? LANG_GALLERYPLUS_FAVORITES : 'Избранное');

        $user_karma   = $user->karma ?? 0;
        $user_rating  = $user->rating ?? 0;

        $this->model->preset_small  = $this->options['preset_small'] ?? 'galleryplus_thumb';
        $this->model->preset_big    = $this->options['preset_big'] ?? 'galleryplus_big';
        $this->model->preset_nocrop = $this->options['preset_nocrop'] ?? 'galleryplus_nocrop';
        $this->model->adult_karma   = (int)($this->options['adult_karma'] ?? 0);
        $this->model->user_karma    = $user_karma;
        $this->model->adult_rating  = (int)($this->options['adult_rating'] ?? 0);
        $this->model->user_rating   = $user_rating;
        $this->model->skip_visible_albums_filter = true;

        $is_owner = $user->id && (int)$user->id === (int)$user_id;

        $page = $this->request->get('page', 1);
        $perpage = $this->options['limit'] ?? 24;

        if ($this->request->isAjax() && $page > 1) {
            return $this->loadMore($page);
        }

        $show_adult_to_guests = $this->options['show_adult_to_guests'] ?? 0;
        $include_adult_for_guests = !$user->id && $show_adult_to_guests;

        $total = $this->model->getUserFavoritesCount($user_id);
        $photos = $this->model->getFavoritePhotos($user_id, $page, $perpage, $include_adult_for_guests);
        if (!$photos) { $photos = []; }
        $photos = $this->applyAdultFilter($photos);
        $has_next = count($photos) > $perpage;
        if ($has_next) { array_pop($photos); }

        // Данные лайков и избранных для карточек и лайтбокса
        $ids = array_column($photos, 'id');
        $likes_data = $this->model->getPhotosLikesBatch($ids, $user->id);
        $fav_data   = $this->model->getPhotosFavoritesBatch($ids, $user->id);
        foreach ($photos as &$p) {
            $pid = $p['id'];
            $p['likes_count'] = $likes_data[$pid]['count'] ?? 0;
            $p['is_liked']    = $likes_data[$pid]['liked'] ?? false;
            $p['is_favorite'] = !empty($fav_data[$pid]);
        }
        unset($p);

        return $this->cms_template->render('favorites', [
            'photos'              => $photos,
            'page'                => $page,
            'has_next'            => $has_next,
            'total'               => $total,
            'perpage'             => $perpage,
            'user'                => $user,
            'profile_user'        => $profile_user,
            'is_owner'            => $is_owner,
            'show_lightbox_desc'  => !empty($this->options['show_lightbox_desc']),
        ]);
    }

    public function loadMore($page) {

        $user = $this->cms_user;
        if (!$user->id) { return cmsCore::error404(); }

        $this->model->preset_small  = $this->options['preset_small'] ?? 'galleryplus_thumb';
        $this->model->preset_big    = $this->options['preset_big'] ?? 'galleryplus_big';
        $this->model->preset_nocrop = $this->options['preset_nocrop'] ?? 'galleryplus_nocrop';
        $this->model->adult_karma   = (int)($this->options['adult_karma'] ?? 0);
        $this->model->user_karma    = $user->karma ?? 0;
        $this->model->adult_rating  = (int)($this->options['adult_rating'] ?? 0);
        $this->model->user_rating   = $user->rating ?? 0;
        $this->model->skip_visible_albums_filter = true;

        $perpage = $this->options['limit'] ?? 24;
        $photos = $this->model->getFavoritePhotos($user->id, $page, $perpage);
        if (!$photos) { $photos = []; }
        $photos = $this->applyAdultFilter($photos);
        $has_next = count($photos) > $perpage;
        if ($has_next) { array_pop($photos); }

        $ids = array_column($photos, 'id');
        $likes_data = $this->model->getPhotosLikesBatch($ids, $user->id);
        $fav_data   = $this->model->getPhotosFavoritesBatch($ids, $user->id);
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
        return '<div class="galleryplus-item' . $blur_class . '" data-object="' . $data . '">'
            . $fav_btn
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
