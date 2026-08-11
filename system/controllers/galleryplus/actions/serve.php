<?php

class actionGalleryplusServe extends cmsAction {

    public function run($photo_id = 0, $preset = 'big') {

        if (!$photo_id) {
            $photo_id = intval($this->request->get('photo_id', 0));
        }
        if (!$preset || $preset === 'big') {
            $preset = $this->request->get('preset', 'big');
        }

        $photo_id = intval($photo_id);
        if (!$photo_id) {
            return cmsCore::error404();
        }

        $allowed_presets = ['small', 'big', 'nocrop', 'original'];
        if (!in_array($preset, $allowed_presets)) {
            $preset = 'big';
        }

        $photo = $this->model->getItemById('galleryplus_photos', $photo_id);
        if (!$photo) {
            return cmsCore::error404();
        }

        $album = $this->model->getAlbum($photo['album_id']);
        if (!$album) {
            return cmsCore::error404();
        }

        $show_adult_for_guests = (bool)($this->options['show_adult_to_guests'] ?? false);
        $user = $this->cms_user;
        if (!$this->model->canAccessAlbum($album, $user->id, $user->karma ?? 0, (int)($this->options['adult_karma'] ?? 0), $show_adult_for_guests, $user->rating ?? 0, (int)($this->options['adult_rating'] ?? 0))) {
            return cmsCore::error404();
        }

        // Фото на модерации доступно только владельцу и модераторам
        if (!(int)$photo['is_approved']) {
            $is_owner = $this->cms_user->id && (int)$photo['user_id'] === (int)$this->cms_user->id;
            if (!$is_owner && !$this->cms_user->is_admin) {
                $is_moderator = false;
                if (cmsCore::isModelExists('moderation')) {
                    $mod = cmsCore::getModel('moderation');
                    if ($mod) {
                        $is_moderator = $mod->userIsContentModerator('galleryplus', $this->cms_user->id);
                    }
                }
                if (!$is_moderator) { return cmsCore::error404(); }
            }
        }

        $image = cmsModel::yamlToArray($photo['image']);

        switch ($preset) {
            case 'small':
                $file_key = 'galleryplus_thumb';
                break;
            case 'big':
                $file_key = 'galleryplus_big';
                break;
            case 'nocrop':
                $file_key = 'galleryplus_nocrop';
                break;
            case 'original':
            default:
                $file_key = null;
                break;
        }

        $file_path = null;

        if ($file_key && !empty($image[$file_key])) {
            $file_path = $image[$file_key];
        } elseif (is_array($image)) {
            $file_path = reset($image);
        }

        if (!$file_path) {
            return cmsCore::error404();
        }

        $full_path = cmsConfig::get('upload_path') . $file_path;

        if (!is_file($full_path) || !is_readable($full_path)) {
            return cmsCore::error404();
        }

        $ext = strtolower(pathinfo($full_path, PATHINFO_EXTENSION));
        $mime_map = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'svg'  => 'image/svg+xml',
        ];
        $mime = $mime_map[$ext] ?: 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($full_path));
        header('Cache-Control: public, max-age=2592000');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');

        readfile($full_path);
        exit;
    }

}
