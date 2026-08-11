<?php

class actionGalleryplusAlbumEdit extends cmsAction {

    public function run($slug = null) {

        if (!$slug) {
            $slug = $this->request->get('slug', '');
        }
        $slug = preg_replace('/\.html$/i', '', $slug);
        if (!$slug) { return cmsCore::error404(); }

        $album = $this->model->getAlbumBySlug($slug);
        if (!$album) { return cmsCore::error404(); }

        if (!$this->model->canEditAlbum($album, $this->cms_user->id)) {
            return cmsCore::error404();
        }

        if ($this->request->has('submit')) {

            $csrf_token = $this->request->get('csrf_token', '');
            if (!cmsForm::validateCSRFToken($csrf_token)) {
                cmsUser::addSessionMessage(LANG_FORM_ERRORS, 'error');
                return $this->redirectBack();
            }

            $title       = $this->request->get('title', '');
            $content     = $this->request->get('content', '');
            $privacy     = $this->request->get('privacy', 'public');
            $password    = $this->request->get('password', '');
            $privacy_users = $this->request->get('privacy_users', '');

            $allow_upload = $this->request->get('allow_upload', 0);

            $update = [
                'title'        => strip_tags($title),
                'content'      => strip_tags($content),
                'allow_upload' => $allow_upload ? 1 : 0,
                'category_id'  => (int)$this->request->get('category_id', 0),
            ];

            $allowed_privacy = ['public', 'private', 'friends', 'users', 'password', 'adult'];
            if (in_array($privacy, $allowed_privacy)) {
                $update['privacy'] = $privacy;
            }

            if ($privacy === 'password' && $password) {
                $update['privacy_password'] = password_hash($password, PASSWORD_DEFAULT);
            } elseif ($privacy !== 'password') {
                $update['privacy_password'] = null;
            }

            if ($privacy === 'users') {
                $user_ids = [];
                $names = explode(',', $privacy_users);
                foreach ($names as $name) {
                    $name = trim($name);
                    if (!$name) { continue; }
                    $user = cmsCore::getModel('users')->getUserByNickname($name);
                    if ($user) { $user_ids[] = $user['id']; }
                }
                $update['privacy_users'] = $user_ids ? implode(',', $user_ids) : null;
            } else {
                $update['privacy_users'] = null;
            }

            $this->model->updateAlbum($album['id'], $update);

            if (!empty($this->options['use_album_tags']) && $this->request->has('tags')) {
                $tags_model = cmsCore::getModel('tags');
                $tags_model->updateTags($this->request->get('tags', ''), 'galleryplus', 'album', $album['id']);
            }

            $this->model->addLog('edit', 'album', $album['id'], strip_tags($title), $album['user_id'], $this->cms_user->id);

            cmsUser::addSessionMessage(LANG_GALLERYPLUS_ALBUM_SAVED, 'success');
            return $this->redirectTo('galleryplus', 'album', [$album['slug'] . '.html']);
        }

        if ($this->request->get('action') === 'delete') {

            $csrf_token = $this->request->get('csrf_token', '');
            if (!cmsForm::validateCSRFToken($csrf_token)) {
                cmsUser::addSessionMessage(LANG_FORM_ERRORS, 'error');
                return $this->redirectBack();
            }

            $this->model->deleteAlbum($album['id'], $this->cms_user->id);

            cmsUser::addSessionMessage(LANG_GALLERYPLUS_ALBUM_DELETED, 'success');
            return $this->redirectTo('galleryplus');
        }

        $use_categories = !empty($this->options['use_categories']);
        $categories = $use_categories ? $this->model->getCategoriesAll() : [];

        $use_album_tags = !empty($this->options['use_album_tags']);
        $album_tags = [];
        if ($use_album_tags) {
            $tags_model = cmsCore::getModel('tags');
            $album_tags = $tags_model->getTagsForTarget('galleryplus', 'album', $album['id']);
        }

        return $this->cms_template->render('album_edit', [
            'album'      => $album,
            'user'       => $this->cms_user,
            'categories' => $categories,
            'use_album_tags' => $use_album_tags,
            'album_tags' => $album_tags,
        ]);
    }

}
