<?php

class actionGalleryplusEdit extends cmsAction {

    public function run() {

        $photo_id = (int) $this->request->get('photo_id', 0);
        if (!$photo_id) { return cmsCore::error404(); }

        $photo = $this->model->getItemById('galleryplus_photos', $photo_id);
        if (!$photo) { return cmsCore::error404(); }

        $photo['exif'] = cmsModel::yamlToArray($photo['exif'] ?? '');
        $photo['url']          = href_to('galleryplus', $photo['slug'] ?? 'photo-' . $photo_id) . '.html';
        $this->model->preset_small = $this->options['preset_small'] ?? 'galleryplus_thumb';
        $this->model->preset_big   = $this->options['preset_big'] ?? 'galleryplus_big';
        $photo['url_thumb']    = html_image_src($photo['image'], $this->model->preset_big, true)
            ?: html_image_src($photo['image'], $this->model->preset_small, true);
        $photo['url_big']      = html_image_src($photo['image'], $this->model->preset_big, true)
            ?: html_image_src($photo['image'], 'normal', true);
        $photo['url_nocrop']   = html_image_src($photo['image'], $this->model->preset_nocrop, true);
        $photo['url_original'] = html_image_src($photo['image'], 'original', true);

        $is_owner = $this->cms_user->id && (int)$photo['user_id'] === (int)$this->cms_user->id;
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

        if (!$is_owner && !$is_admin && !$is_moderator) {
            return cmsCore::error403();
        }

        $album = $this->model->getAlbum($photo['album_id']);

        if ($this->request->has('submit')) {

            $title   = strip_tags($this->request->get('title', ''));
            $content = $this->request->get('content', '');

            $update = [];
            $update['title']   = $title ?: ('Photo #' . $photo_id);
            $update['content'] = $content;

            if ($title !== strip_tags($photo['title'] ?? '')) {
                $slug = lang_slug($title);
                if (!$slug) { $slug = 'photo'; }
                $slug .= '-' . $photo_id;
                $update['slug'] = $slug;
            }

            $this->model->resetFilters();
            $this->model->update('galleryplus_photos', $photo_id, $update);

            if ($this->request->get('exif_delete')) {
                $this->model->resetFilters();
                $this->model->update('galleryplus_photos', $photo_id, ['exif' => '']);
            } else {
                $exif_input = $this->request->get('exif', []);
                if (is_array($exif_input) && !empty($exif_input)) {
                    $current_exif = is_array($photo['exif'] ?? null) ? $photo['exif'] : [];
                    $merged_exif = array_merge($current_exif, $exif_input);
                    $this->model->resetFilters();
                    $this->model->update('galleryplus_photos', $photo_id, ['exif' => $merged_exif]);
                }
            }

            if (!empty($this->options['use_photo_tags']) && $this->request->has('tags')) {
                $tags_model = cmsCore::getModel('tags');
                $tags_model->updateTags($this->request->get('tags', ''), 'galleryplus', 'photo', $photo_id);
            }

            $this->model->addLog('edit', 'photo', $photo_id, $update['title'] ?? '', $photo['user_id'], $this->cms_user->id);

            cmsUser::addSessionMessage(
                defined('LANG_SUCCESS_MSG') ? LANG_SUCCESS_MSG : 'Saved',
                'success'
            );

            $new_slug = $update['slug'] ?? $photo['slug'] ?? '';
            $redirect = $new_slug
                ? href_to('galleryplus', $new_slug) . '.html'
                : href_to('galleryplus');
            return $this->redirect($redirect);
        }

        $photo_tags = '';
        if (!empty($this->options['use_photo_tags'])) {
            $tags_model = cmsCore::getModel('tags');
            $tags = $tags_model->getTagsForTarget('galleryplus', 'photo', $photo_id);
            $photo_tags = $tags ? implode(', ', $tags) : '';
        }

        $this->cms_template->addBreadcrumb(
            defined('LANG_GALLERYPLUS_TITLE') ? LANG_GALLERYPLUS_TITLE : 'Gallery',
            href_to('galleryplus')
        );
        if ($album) {
            $album['url'] = href_to('galleryplus', 'album', [$album['slug']]) . '.html';
            $this->cms_template->addBreadcrumb(
                $album['title'],
                $album['url']
            );
        }

        $photo_url = href_to('galleryplus', $photo['slug'] ?? 'photo-' . $photo_id) . '.html';
        $this->cms_template->addBreadcrumb(
            defined('LANG_GALLERYPLUS_EDIT') ? LANG_GALLERYPLUS_EDIT : 'Edit photo'
        );

        return $this->cms_template->render('edit', [
            'photo'         => $photo,
            'album'         => $album,
            'photo_url'     => $photo_url,
            'photo_tags'    => $photo_tags,
            'use_photo_tags' => !empty($this->options['use_photo_tags']),
            'map_center_lat' => $this->options['map_center_lat'] ?? '59.938933',
            'map_center_lng' => $this->options['map_center_lng'] ?? '30.315721',
        ]);
    }

}
