<?php

class actionGalleryplusSave extends cmsAction {

    public function run() {

        $album_id      = $this->request->get('album_id', '');
        $new_album_title = $this->request->get('new_album_title', '');
        // Handle single photo edit
        $action = $this->request->get('action', '');
        if ($action === 'update_photo') {
            return $this->updatePhoto();
        }
        if ($action === 'delete_photos') {
            return $this->deletePhotos();
        }
        if ($action === 'delete_albums') {
            return $this->deleteAlbums();
        }

        $photo_ids     = $this->request->get('photos', []);
        $titles        = $this->request->get('title', []);

        if (!$photo_ids) {
            return $this->redirectBack();
        }

        // If no album was selected on the upload form, fall back to the
        // album the photos were already assigned to during upload
        // (processUpload assigns the user's default album when album_id is empty).
        if (!$album_id && $album_id !== 'new') {
            $first = $this->model->getItemById('galleryplus_photos', (int)reset($photo_ids));
            if ($first && !empty($first['album_id'])) {
                $album_id = (int)$first['album_id'];
            }
        }

        if ($album_id === 'new' && $new_album_title) {

            $title = strip_tags($new_album_title);

            // If this user already has an album with same title, reuse it
            $existing = $this->model->filterEqual('title', $title)->filterEqual('user_id', $this->cms_user->id)->getItem('galleryplus_albums');
            $this->model->resetFilters();

            if ($existing) {
                $album_id = $existing['id'];
                $album    = $existing;
            } else {
                $slug = lang_slug($title);
                $album_id = $this->model->insert('galleryplus_albums', [
                    'title'   => $title,
                    'slug'    => $slug,
                    'user_id' => $this->cms_user->id,
                    'privacy' => 'public',
                    'date_pub' => null,
                ]);
                $album = ['id' => $album_id, 'slug' => $slug, 'user_id' => $this->cms_user->id];
            }
        } else {
            $album_id = (int) $album_id;
            if (!$album_id) { return $this->redirectBack(); }
            $album = $this->model->getAlbum($album_id);
            if (!$album || !$this->model->canUploadToAlbum($album, $this->cms_user->id)) {
                return $this->redirectBack();
            }
        }

        // Save album settings (privacy, allow_upload) if user is owner
        if ($album['user_id'] == $this->cms_user->id) {
            $update_album = [];

            $allow_upload = $this->request->get('allow_upload', 0);
            $update_album['allow_upload'] = $allow_upload ? 1 : 0;

            $privacy = $this->request->get('privacy', '');
            $allowed_privacy = ['public', 'private', 'friends', 'users', 'password', 'adult'];
            if (in_array($privacy, $allowed_privacy)) {
                $update_album['privacy'] = $privacy;
                if ($privacy === 'password') {
                    $password = $this->request->get('password', '');
                    if ($password) {
                        $update_album['privacy_password'] = password_hash($password, PASSWORD_DEFAULT);
                    }
                } elseif ($privacy !== 'password') {
                    $update_album['privacy_password'] = null;
                }
                if ($privacy === 'users') {
                    $privacy_users = $this->request->get('privacy_users', '');
                    $user_ids = [];
                    $names = explode(',', $privacy_users);
                    foreach ($names as $name) {
                        $name = trim($name);
                        if (!$name) { continue; }
                        $u = cmsCore::getModel('users')->filterEqual('nickname', $name)->getUser();
                        if ($u) { $user_ids[] = $u['id']; }
                    }
                    $update_album['privacy_users'] = $user_ids ? implode(',', $user_ids) : null;
                } else {
                    $update_album['privacy_users'] = null;
                }
            }

            $this->model->updateAlbum($album_id, $update_album);
        }

        $last_order = $this->model->filterEqual('album_id', $album_id)->getCount('galleryplus_photos');
        $first_slug = null;

        foreach ($photo_ids as $pid) {
            $pid = (int) $pid;
            if (!$pid) { continue; }
            $slug = $this->generateSlug($titles[$pid] ?? '', $pid);
            if (!$first_slug) { $first_slug = $slug; }
            $this->model->updatePhoto($pid, [
                'album_id' => $album_id,
                'title'    => strip_tags($titles[$pid] ?? ''),
                'slug'     => $slug,
                'ordering' => $last_order++,
                'date_pub' => null,
            ]);

            if (!empty($this->options['use_photo_tags'])) {
                $photo_tags = $this->request->get('tags_' . $pid, '');
                if ($photo_tags) {
                    $tags_model = cmsCore::getModel('tags');
                    $tags_model->addTags($photo_tags, 'galleryplus', 'photo', $pid);
                }
            }
        }

        $this->model->filterEqual('album_id', $album_id);
        $count = $this->model->getCount('galleryplus_photos');
        $this->model->resetFilters();

        $this->model->filterEqual('id', $album_id);
        $this->model->updateFiltered('galleryplus_albums', [
            'photos_count' => $count,
        ]);

        cmsUser::addSessionMessage(defined('LANG_GALLERYPLUS_SAVED') ? LANG_GALLERYPLUS_SAVED : 'Photos saved', 'success');

        $photo_ids = array_filter($photo_ids, 'is_numeric');
        if (count($photo_ids) === 1 && $first_slug) {
            $this->redirectTo('galleryplus', $first_slug . '.html');
        }
        $this->redirectTo('galleryplus', 'album', [$album['slug'] . '.html']);
    }

    private function updatePhoto() {
        $photo_id = (int)$this->request->get('photo_id', 0);
        if (!$photo_id) {
            return $this->cms_template->renderJSON(['error' => 'Invalid photo']);
        }

        $photo = $this->model->getItemById('galleryplus_photos', $photo_id);
        if (!$photo) {
            return $this->cms_template->renderJSON(['error' => 'Photo not found']);
        }

        $is_owner = $this->cms_user->id && (int)$photo['user_id'] === (int)$this->cms_user->id;
        if (!$is_owner && !$this->cms_user->is_admin) {
            return $this->cms_template->renderJSON(['error' => 'Access denied']);
        }

        $title = strip_tags($this->request->get('title', ''));
        $content = $this->request->get('content', '');
        $exif_raw = $this->request->get('exif', '');

        $update = ['title' => $title];
        if ($title !== strip_tags($photo['title'])) {
            $slug = lang_slug($title);
            if (!$slug) { $slug = 'photo'; }
            $slug .= '-' . $photo_id;
            $update['slug'] = $slug;
        }

        $update['content'] = $content;

        if ($exif_raw) {
            $exif = @json_decode($exif_raw, true);
            if (is_array($exif)) {
                $update['exif'] = $exif;
            }
        }

        $this->model->update('galleryplus_photos', $photo_id, $update);

        if (!empty($this->options['use_photo_tags']) && $this->request->has('tags')) {
            $tags_model = cmsCore::getModel('tags');
            $tags_model->updateTags($this->request->get('tags', ''), 'galleryplus', 'photo', $photo_id);
        }

        $this->model->addLog('edit', 'photo', $photo_id, $update['title'] ?? '', $photo['user_id'], $this->cms_user->id);

        return $this->cms_template->renderJSON(['success' => true]);
    }

    private function deletePhotos() {
        if (!$this->cms_user->id) {
            return $this->cms_template->renderJSON(['error' => 'Access denied']);
        }

        $ids_raw = $this->request->get('ids', '');
        if (is_string($ids_raw)) {
            $ids = array_filter(array_map('intval', explode(',', $ids_raw)));
        } elseif (is_array($ids_raw)) {
            $ids = array_filter(array_map('intval', $ids_raw));
        } else {
            $ids = [];
        }
        if (!$ids) {
            return $this->cms_template->renderJSON(['error' => 'No photos selected']);
        }

        $rows = $this->model->getPhotosImagesByIds($ids);
        if (!$rows) {
            return $this->cms_template->renderJSON(['error' => 'Photos not found']);
        }

        $is_admin = $this->cms_user->is_admin;
        $is_moderator = false;
        if (!$is_admin) {
            try {
                if (cmsCore::isModelExists('moderation')) {
                    $mod = cmsCore::getModel('moderation');
                    if ($mod && method_exists($mod, 'userIsContentModerator')) {
                        $is_moderator = $mod->userIsContentModerator('galleryplus', $this->cms_user->id);
                    }
                }
            } catch (\Throwable $e) {}
        }

        $allowed_ids = [];
        foreach ($rows as $row) {
            if ($is_admin || $is_moderator) {
                $allowed_ids[] = $row['id'];
            } else {
                $photo = $this->model->getItemById('galleryplus_photos', $row['id']);
                if ($photo && (int)$photo['user_id'] === (int)$this->cms_user->id) {
                    $allowed_ids[] = $row['id'];
                }
            }
        }

        if (!$allowed_ids) {
            return $this->cms_template->renderJSON(['error' => 'Access denied']);
        }

        $upload_path = cmsConfig::get('upload_path');

        foreach ($rows as $row) {
            if (!in_array($row['id'], $allowed_ids)) { continue; }
            $image = cmsModel::yamlToArray($row['image']);
            if (!empty($image)) {
                foreach ($image as $path) {
                    $file = $upload_path . $path;
                    if (is_file($file)) { @unlink($file); }
                }
            }
        }

        $deleted = $this->model->deletePhotosBatch($allowed_ids);

        foreach ($rows as $row) {
            if (!in_array($row['id'], $allowed_ids)) { continue; }
            $this->model->addLog('delete', 'photo', $row['id'], $row['title'] ?? '', $row['user_id'] ?? 0, $this->cms_user->id);
        }

        return $this->cms_template->renderJSON(['success' => true, 'deleted' => $deleted]);
    }

    private function generateSlug($title, $id) {
        $slug = $title ? lang_slug($title) : 'photo';
        if (!$slug) { $slug = 'photo'; }
        $slug .= '-' . $id;
        return $slug;
    }

    private function deleteAlbums() {
        if (!$this->cms_user->id) {
            return $this->cms_template->renderJSON(['error' => 'Access denied']);
        }

        $ids_raw = $this->request->get('ids', '');
        if (is_string($ids_raw)) {
            $ids = array_filter(array_map('intval', explode(',', $ids_raw)));
        } elseif (is_array($ids_raw)) {
            $ids = array_filter(array_map('intval', $ids_raw));
        } else {
            $ids = [];
        }
        if (!$ids) {
            return $this->cms_template->renderJSON(['error' => 'No albums selected']);
        }

        $is_admin = $this->cms_user->is_admin;
        $is_moderator = false;
        if (!$is_admin) {
            try {
                if (cmsCore::isModelExists('moderation')) {
                    $mod = cmsCore::getModel('moderation');
                    if ($mod && method_exists($mod, 'userIsContentModerator')) {
                        $is_moderator = $mod->userIsContentModerator('galleryplus', $this->cms_user->id);
                    }
                }
            } catch (\Throwable $e) {}
        }

        $deleted = 0;
        foreach ($ids as $id) {
            $album = $this->model->getItemById('galleryplus_albums', $id);
            if (!$album) { continue; }
            if (!$is_admin && !$is_moderator && (int)$album['user_id'] !== (int)$this->cms_user->id) { continue; }
            $this->model->deleteAlbum($id, $this->cms_user->id);
            $deleted++;
        }

        return $this->cms_template->renderJSON(['success' => true, 'deleted' => $deleted]);
    }

}
