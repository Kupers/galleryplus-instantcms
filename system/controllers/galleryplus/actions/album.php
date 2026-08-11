<?php

class actionGalleryplusAlbum extends cmsAction {

    private $can_select = false;

    public function run($slug = null) {

        if (!$slug) {
            $slug = $this->request->get('slug', '');
        }
        $slug = preg_replace('/\.html$/i', '', $slug);

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

        $album = $this->model->getAlbumBySlug($slug);
        if (!$album) { return cmsCore::error404(); }

        if (!$this->model->canAccessAlbum($album, $user->id, $this->model->user_karma, $this->model->adult_karma, false, $this->model->user_rating, $this->model->adult_rating)) {

            $password_submit = $this->request->get('album_password', '');
            if ($album['privacy'] === 'password' && $password_submit) {
                if (!cmsForm::validateCSRFToken($this->request->get('csrf_token', ''))) {
                    cmsUser::addSessionMessage(LANG_FORM_ERRORS, 'error');
                    return $this->redirectTo('galleryplus', 'album', [$album['slug'] . '.html']);
                }
                if (password_verify($password_submit, $album['privacy_password'])) {
                    $_SESSION['galleryplus_album_' . $album['id']] = true;
                    return $this->redirectTo('galleryplus', 'album', [$album['slug'] . '.html']);
                }
                cmsUser::addSessionMessage(LANG_GALLERYPLUS_ALBUM_WRONG_PASSWORD, 'error');
                return $this->cms_template->render('album', [
                    'album'  => $album,
                    'photos' => [],
                    'page'   => 1,
                    'has_next' => false,
                    'user'   => $this->cms_user,
                    'locked' => true,
                    'can_select' => false,
                    'category' => null,
                ]);
            }

            if ($album['privacy'] === 'password' && !empty($_SESSION['galleryplus_album_' . $album['id']])) {
                // already unlocked via session
            } elseif ($album['privacy'] === 'password') {
                if ($this->request->isAjax()) {
                    return $this->cms_template->renderJSON(['redirect' => href_to('galleryplus', 'album', [$album['slug']]) . '.html']);
                }
                return $this->cms_template->render('album', [
                    'album'    => $album,
                    'photos'   => [],
                    'page'     => 1,
                    'has_next' => false,
                    'user'     => $this->cms_user,
                    'locked'   => true,
                    'can_select' => false,
                    'category' => null,
                ]);
            } elseif ($album['privacy'] === 'adult') {
                if (!$this->cms_user->id) {
                    return $this->redirectTo('auth', 'login');
                }
                if ($this->model->adult_rating > 0 && $this->model->user_rating < $this->model->adult_rating) {
                    cmsUser::addSessionMessage(
                        sprintf(LANG_GALLERYPLUS_RATING_ADULT_ERROR, $this->model->adult_rating),
                        'error'
                    );
                } else {
                    cmsUser::addSessionMessage(
                        sprintf(LANG_GALLERYPLUS_KARMA_ADULT_ERROR, $this->model->adult_karma),
                        'error'
                    );
                }
                $back = $_SERVER['HTTP_REFERER'] ?? href_to('galleryplus');
                return $this->redirect($back);
            } else {
                return cmsCore::error404();
            }
        }

        $page    = $this->request->get('page', 1);
        $perpage = $this->options['limit'] ?? 24;

        $album['photo_count'] = $this->model->getPhotosCount($album['id']);

        $this->model->preset_small  = $this->options['preset_small'] ?? 'galleryplus_thumb';
        $this->model->preset_big    = $this->options['preset_big'] ?? 'galleryplus_big';
        $this->model->preset_nocrop = $this->options['preset_nocrop'] ?? 'galleryplus_nocrop';
        $this->model->filterEqual('album_id', $album['id']);

        $this->can_select = $this->canSelect($album);

        if ($this->request->isAjax() && $page > 1) {
            return $this->loadMore($album, $page, $perpage);
        }

        $photos = $this->model->getPhotos($page, $perpage);
        if (!$photos) { $photos = []; }
        $photos = $this->applyAdultFilter($photos);
        $has_next = count($photos) > $perpage;
        if ($has_next) { array_pop($photos); }

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

        // Comments widget
        $comments_widget = '';
        if (!empty($this->options['is_comments_album'])) {
            $cc = cmsCore::getController('comments');
            $cc->target_controller = 'galleryplus';
            $cc->target_subject    = 'album';
            $cc->target_id         = $album['id'];
            $cc->target_user_id    = $album['user_id'];
            $comments_widget = $cc->getWidget();
        }

        $category = null;
        if (!empty($album['category_id'])) {
            $category = $this->model->getCategoryById((int)$album['category_id']);
            if ($category) {
                $category['url'] = href_to('galleryplus', 'category', [$category['slug']]) . '.html';
            }
        }

        $album_tags = [];
        if (!empty($this->options['use_album_tags'])) {
            $tags_model = cmsCore::getModel('tags');
            $album_tags = $tags_model->getTagsForTarget('galleryplus', 'album', $album['id']);
        }

        $can_upload = false;
        if ($this->cms_user->id) {
            $is_album_owner = $this->cms_user->id == $album['user_id'];
            $is_admin = $this->cms_user->is_admin;
            $upload_karma = (int)($this->options['upload_karma'] ?? 0);
            $karma_ok = $upload_karma <= 0 || $is_admin || $this->cms_user->karma >= $upload_karma;
            if ($karma_ok && ($is_admin || $is_album_owner || !empty($album['allow_upload']))) {
                $can_upload = true;
            }
        }

        return $this->cms_template->render('album', [
            'album'           => $album,
            'photos'          => $photos,
            'page'            => $page,
            'has_next'        => $has_next,
            'user'            => $this->cms_user,
            'locked'          => false,
            'is_owner'        => $this->cms_user->id && $album['user_id'] == $this->cms_user->id,
            'comments_widget' => $comments_widget,
            'can_select'      => $this->can_select,
            'category'        => $category,
            'album_tags'      => $album_tags,
            'can_upload'      => $can_upload,
            'show_lightbox_desc' => !empty($this->options['show_lightbox_desc']),
        ]);
    }

    public function loadMore($album, $page, $perpage) {
        $this->model->filterEqual('album_id', $album['id']);
        $this->model->preset_small  = $this->options['preset_small'] ?? 'galleryplus_thumb';
        $this->model->preset_big    = $this->options['preset_big'] ?? 'galleryplus_big';
        $this->model->preset_nocrop = $this->options['preset_nocrop'] ?? 'galleryplus_nocrop';

        $photos = $this->model->getPhotos($page, $perpage);
        if (!$photos) { $photos = []; }
        $photos = $this->applyAdultFilter($photos);
        $has_next = count($photos) > $perpage;
        if ($has_next) { array_pop($photos); }

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

    private function canSelect($album) {
        if (!$this->cms_user->id) { return false; }
        if ($this->cms_user->is_admin) { return true; }
        if ($this->cms_user->id == $album['user_id']) { return true; }
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
        $checkbox = $this->can_select
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
