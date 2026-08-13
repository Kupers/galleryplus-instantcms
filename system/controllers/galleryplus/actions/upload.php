<?php

class actionGalleryplusUpload extends cmsAction {

    public function run() {

        if (!$this->cms_user->id) {
            return cmsCore::error404();
        }

        $upload_karma = (int)($this->options['upload_karma'] ?? 0);
        $is_admin = $this->cms_user->is_admin;
        if ($upload_karma > 0 && !$is_admin && $this->cms_user->karma < $upload_karma) {
            cmsUser::addSessionMessage(
                sprintf(LANG_GALLERYPLUS_KARMA_UPLOAD_ERROR, $upload_karma),
                'error'
            );
            return $this->redirectTo('galleryplus');
        }

        $this->model->preset_small = $this->options['preset_small'] ?? 'galleryplus_thumb';
        $this->model->preset_big   = $this->options['preset_big'] ?? 'galleryplus_big';

        if ($this->request->isAjax()) {
            $action = $this->request->get('action', '');
            if ($action === 'ajax_create_album') {
                return $this->ajaxCreateAlbum();
            }
            if ($action === 'ajax_save_album') {
                return $this->ajaxSaveAlbum();
            }
            return $this->processUpload();
        }

        return $this->showUploadForm();
    }

    public function showUploadForm() {

        $this->cms_template->addBreadcrumb(
            defined('LANG_GALLERYPLUS_TITLE') ? LANG_GALLERYPLUS_TITLE : 'Gallery',
            href_to('galleryplus')
        );
        $this->cms_template->addBreadcrumb(
            defined('LANG_GALLERYPLUS_UPLOAD') ? LANG_GALLERYPLUS_UPLOAD : 'Upload'
        );

        $albums = $this->model->getAlbums(1, 500);
        if (!$albums) {
            $albums = [];
        }

        $albums = array_filter($albums, function ($a) {
            return $this->model->canUploadToAlbum($a, $this->cms_user->id);
        });

        $album_id = $this->request->get('album_id', 0);

        $albums_json = [];
        foreach ($albums as $a) {
            $label = $a['title'];
            if (!empty($a['user']['nickname'])) {
                $label .= ' — ' . $a['user']['nickname'];
            }
            $albums_json[] = ['id' => $a['id'], 'title' => $a['title'], 'label' => $label];
        }

        $use_categories = !empty($this->options['use_categories']);
        $categories = $use_categories ? $this->model->getCategoriesAll() : [];

        return $this->cms_template->render('upload', [
            'albums'      => $albums,
            'albums_json' => json_encode($albums_json, JSON_UNESCAPED_UNICODE),
            'album_id'    => $album_id,
            'user'        => $this->cms_user,
            'options'     => $this->options,
            'privacy_opts' => $this->getPrivacyOptions(),
            'categories'  => $categories,
            'use_album_tags' => !empty($this->options['use_album_tags']),
            'use_photo_tags' => !empty($this->options['use_photo_tags']),
        ]);
    }

    public function ajaxCreateAlbum() {

        $title = $this->request->get('title', '');
        if (!$title) {
            return $this->cms_template->renderJSON(['error' => LANG_GALLERYPLUS_ALBUM_TITLE_REQUIRED]);
        }

        $existing = $this->model->filterEqual('title', $title)->filterEqual('user_id', $this->cms_user->id)->getItem('galleryplus_albums');
        $this->model->resetFilters();

        if ($existing) {
            return $this->cms_template->renderJSON($existing);
        }

        $slug = lang_slug($title);

        $privacy = $this->request->get('privacy', 'public');
        $allowed_privacy = ['public', 'private', 'friends', 'users', 'password', 'adult'];
        if (!in_array($privacy, $allowed_privacy)) {
            $privacy = 'public';
        }

        $privacy_users = null;
        $privacy_password = null;
        if ($privacy === 'users') {
            $user_ids = [];
            $names = explode(',', strip_tags($this->request->get('privacy_users', '')));
            foreach ($names as $name) {
                $name = trim($name);
                if (!$name) { continue; }
                $u = cmsCore::getModel('users')->filterEqual('nickname', $name)->getUser();
                if ($u) { $user_ids[] = $u['id']; }
            }
            $privacy_users = $user_ids ? implode(',', $user_ids) : null;
        }
        if ($privacy === 'password') {
            $password = $this->request->get('password', '');
            if ($password) {
                $privacy_password = password_hash($password, PASSWORD_DEFAULT);
            }
        }

        $album_id = $this->model->insert('galleryplus_albums', [
            'title'          => strip_tags($title),
            'slug'           => $slug,
            'user_id'        => $this->cms_user->id,
            'content'        => strip_tags($this->request->get('content', '')),
            'privacy'        => $privacy,
            'privacy_users'  => $privacy_users,
            'privacy_password' => $privacy_password,
            'category_id'    => (int)$this->request->get('category_id', 0),
            'allow_upload'   => (int)$this->request->get('allow_upload', 0),
            'date_pub'       => null,
        ]);

        if (!empty($this->options['use_album_tags'])) {
            $tags = $this->request->get('tags', '');
            if ($tags) {
                $tags_model = cmsCore::getModel('tags');
                $tags_model->addTags($tags, 'galleryplus', 'album', $album_id);
            }
        }

        $album = $this->model->getAlbum($album_id);

        return $this->cms_template->renderJSON($album);
    }

    public function ajaxSaveAlbum() {

        $album_id = (int)$this->request->get('album_id', 0);
        if (!$album_id) {
            return $this->cms_template->renderJSON(['error' => 'Album ID required']);
        }

        $album = $this->model->getAlbum($album_id);
        if (!$album || $album['user_id'] != $this->cms_user->id) {
            return $this->cms_template->renderJSON(['error' => 'Access denied']);
        }

        $update = [];

        $title = $this->request->get('title', '');
        if ($title) {
            $update['title'] = strip_tags($title);
        }

        $content = $this->request->get('content', '');
        $update['content'] = strip_tags($content);

        $allow_upload = $this->request->get('allow_upload', 0);
        $update['allow_upload'] = $allow_upload ? 1 : 0;

        $category_id = (int)$this->request->get('category_id', 0);
        $update['category_id'] = $category_id;

        $privacy = $this->request->get('privacy', '');
        $allowed_privacy = ['public', 'private', 'friends', 'users', 'password', 'adult'];
        if (in_array($privacy, $allowed_privacy)) {
            $update['privacy'] = $privacy;
            if ($privacy === 'password') {
                $password = $this->request->get('password', '');
                if ($password) {
                    $update['privacy_password'] = password_hash($password, PASSWORD_DEFAULT);
                }
            } elseif ($privacy !== 'password') {
                $update['privacy_password'] = null;
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
                $update['privacy_users'] = $user_ids ? implode(',', $user_ids) : null;
            } else {
                $update['privacy_users'] = null;
            }
        }

        $this->model->updateAlbum($album_id, $update);

        if (!empty($this->options['use_album_tags']) && $this->request->has('tags')) {
            $tags_model = cmsCore::getModel('tags');
            $tags_model->updateTags($this->request->get('tags', ''), 'galleryplus', 'album', $album_id);
        }

        $album = $this->model->getAlbum($album_id);
        return $this->cms_template->renderJSON($album);
    }

    private function getPrivacyOptions() {
        return [
            ['value' => 'public',   'label' => LANG_GALLERYPLUS_PRIVACY_PUBLIC,         'hint' => LANG_GALLERYPLUS_PRIVACY_PUBLIC_HINT],
            ['value' => 'private',  'label' => LANG_GALLERYPLUS_PRIVACY_PRIVATE,        'hint' => LANG_GALLERYPLUS_PRIVACY_PRIVATE_HINT],
            ['value' => 'friends',  'label' => LANG_GALLERYPLUS_PRIVACY_FRIENDS,        'hint' => LANG_GALLERYPLUS_PRIVACY_FRIENDS_HINT],
            ['value' => 'users',    'label' => LANG_GALLERYPLUS_PRIVACY_USERS,          'hint' => LANG_GALLERYPLUS_PRIVACY_USERS_HINT],
            ['value' => 'password', 'label' => LANG_GALLERYPLUS_PRIVACY_PASSWORD,       'hint' => LANG_GALLERYPLUS_PRIVACY_PASSWORD_HINT],
            ['value' => 'adult',    'label' => LANG_GALLERYPLUS_PRIVACY_ADULT,          'hint' => LANG_GALLERYPLUS_PRIVACY_ADULT_HINT],
        ];
    }

    public function processUpload() {

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        try {

            $presets = cmsCore::getModel('images')->orderByList([
                ['by' => 'is_square', 'to' => 'asc'],
                ['by' => 'width', 'to' => 'desc']
            ])->filterIsNull('is_internal')->getPresets();

            if (!$presets) {
                return $this->cms_template->renderJSON([
                    'success' => false,
                    'error'   => 'no presets'
                ]);
            }

            $preset_small_name = $this->options['preset_small'] ?? 'galleryplus_thumb';
            $preset_big_name   = $this->options['preset_big'] ?? 'galleryplus_big';
            $preset_nocrop_name = $this->options['preset_nocrop'] ?? 'galleryplus_nocrop';

            $preset_small  = null;
            $preset_big    = null;
            $preset_nocrop = null;
            foreach ($presets as $p) {
                if ($p['name'] === $preset_small_name) { $preset_small = $p; }
                if ($p['name'] === $preset_big_name)   { $preset_big   = $p; }
                if ($p['name'] === $preset_nocrop_name) { $preset_nocrop = $p; }
            }

            if (!$preset_small || !$preset_big) {
                return $this->cms_template->renderJSON([
                    'success' => false,
                    'error'   => 'preset not found: ' . (!$preset_small ? $preset_small_name : $preset_big_name)
                ]);
            }

            $username  = $this->cms_user->nickname;
            $year      = date('Y');
            $month     = date('m');
            $subdir    = 'galleryplus/' . $username . '/' . $year . '/' . $month;

            $full_upload_subdir = $this->cms_config->upload_path . $subdir . '/';
            if (!is_dir($full_upload_subdir)) {
                mkdir($full_upload_subdir, 0775, true);
            }

            $naming = $this->options['naming_scheme'] ?? 'original';
            if ($naming !== 'original') {
                $orig_name = $_FILES['file']['name'] ?? 'photo';
                $clean_name = files_sanitize_name(pathinfo($orig_name, PATHINFO_FILENAME));
                $random = substr(md5(microtime(true) . uniqid()), 0, 8);
                switch ($naming) {
                    case 'random':
                        $this->cms_uploader->setFileName($random);
                        break;
                    case 'mixed':
                        $this->cms_uploader->setFileName($clean_name . '_' . $random);
                        break;
                    case 'id':
                        $this->cms_uploader->setFileName('photo');
                        break;
                }
            }

            $max_size = !empty($this->options['max_file_size']) ? $this->options['max_file_size'] * 1048576 : 0;

            $result = $this->cms_uploader->setAllowedMime([
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp'
            ])->upload('file', false, $max_size, $subdir);

            if ($result['success']) {
                try {
                    $image = new cmsImages($result['path']);
                    $image->setDestinationDir(dirname($result['path']) . '/');
                } catch (\Throwable $exc) {
                    $result['success'] = false;
                    $result['error']   = LANG_UPLOAD_ERR_MIME;
                }
            }

            if (!$result['success']) {
                if (!empty($result['path'])) {
                    files_delete_file($result['path'], 2);
                }
                return $this->cms_template->renderJSON($result);
            }

            $base_filename = pathinfo($result['name'], PATHINFO_FILENAME);

            $resized_path = $image->resizeByPreset($preset_big, $base_filename . '_' . $preset_big_name);
            if ($resized_path) { $result['paths'][$preset_big_name] = $resized_path; }

            $resized_path = $image->resizeByPreset($preset_small, $base_filename . '_' . $preset_small_name);
            if ($resized_path) { $result['paths'][$preset_small_name] = $resized_path; }

            $result['filename'] = basename($result['path']);

            $image_data = img_get_params($result['path']);
            if ($image_data === false) {
                files_delete_file($result['path'], 2);
                return $this->cms_template->renderJSON([
                    'success' => false,
                    'error'   => 'img_get_params failed for: ' . $result['path']
                ]);
            }

            $gps_data = $this->extractGpsData($result['path']);
            if ($gps_data) {
                $image_data['exif'] = array_merge($image_data['exif'], $gps_data);
            }

            // Generate no-crop preset (WebP at original size, no resize).
            // Only generated when "Обработка оригинала" is enabled, replaces the original
            $nocrop_generated = false;
            if ($preset_nocrop && !empty($this->options['show_original'])) {
                $dest_name = files_sanitize_name($base_filename . '_' . $preset_nocrop_name);
                $dest_ext  = !empty($preset_nocrop['convert_format']) ? $preset_nocrop['convert_format'] : 'webp';
                $dest_file = dirname($result['path']) . '/' . $dest_name . '.' . $dest_ext;
                $dest_type = $dest_ext === 'webp' ? IMAGETYPE_WEBP : IMAGETYPE_JPEG;
                try {
                    $nocrop_image = new cmsImages($result['path']);
                    $nocrop_image->setDestinationDir(dirname($result['path']) . '/');
                    $nocrop_image->resizeToWidth($image_data['width'], true);
                    if ($nocrop_image->save($dest_file, $dest_type, (int)($preset_nocrop['quality'] ?? 92))) {
                        $result['paths'][$preset_nocrop_name] = $subdir . '/' . $dest_name . '.' . $dest_ext;
                        $nocrop_generated = true;
                    }
                } catch (\Throwable $exc) {
                    // skip nocrop on error
                }
            }

            $max_w = !empty($this->options['max_width']) ? (int)$this->options['max_width'] : 0;
            $max_h = !empty($this->options['max_height']) ? (int)$this->options['max_height'] : 0;
            if ($max_w && $image_data['width'] > $max_w) {
                files_delete_file($result['path'], 2);
                return $this->cms_template->renderJSON([
                    'success' => false,
                    'error'   => sprintf(LANG_GALLERYPLUS_ERR_MAX_WIDTH, $max_w, $image_data['width'])
                ]);
            }
            if ($max_h && $image_data['height'] > $max_h) {
                files_delete_file($result['path'], 2);
                return $this->cms_template->renderJSON([
                    'success' => false,
                    'error'   => sprintf(LANG_GALLERYPLUS_ERR_MAX_HEIGHT, $max_h, $image_data['height'])
                ]);
            }

            $big_image_data = false;
            if (!empty($result['paths'][$preset_big_name])) {
                $big_image_data = img_get_params($this->cms_config->upload_path . $result['paths'][$preset_big_name]);
            }
            $image_data['orientation'] = is_array($big_image_data) ? $big_image_data['orientation'] : '';

            // The original file is always removed; we never store it.
            // Dimensions fall back to the big preset when no no-crop variant exists.
            @unlink($result['path']);
            if (!$nocrop_generated && is_array($big_image_data)) {
                $image_data['width']  = $big_image_data['width'];
                $image_data['height'] = $big_image_data['height'];
            }

            unset($result['path']);

            $sizes = [];
            foreach ($result['paths'] as $name => $relpath) {
                $s = @getimagesize($this->cms_config->upload_path . $relpath);
                if ($s === false) { continue; }
                $sizes[$name] = ['width' => $s[0], 'height' => $s[1]];
            }

            $auto_approve = !empty($this->options['auto_approve'] ?? 1) || $this->userCanBypassModeration();

            $xmp_description = $this->extractXmpDescription($result['path'] ?? '');

            $album_id = (int)$this->request->get('album_id', 0);
            if (!$album_id) {
                $album_id = $this->getDefaultAlbumId();
            }

            $photo_id = $this->model->addPhoto([
                'user_id'     => $this->cms_user->id,
                'album_id'    => $album_id,
                'image'       => $result['paths'],
                'width'       => $image_data['width'],
                'height'      => $image_data['height'],
                'sizes'       => $sizes,
                'orientation' => $image_data['orientation'] ?? '',
                'is_approved' => $auto_approve,
                'exif'        => !empty($image_data['exif']) ? $image_data['exif'] : null,
                'content'     => $xmp_description ?: null,
            ]);

            if ($photo_id === false) {
                return $this->cms_template->renderJSON([
                    'success' => false,
                    'error'   => 'addPhoto returned false'
                ]);
            }

            if (!$auto_approve) {
                $this->notifyPendingPhoto($photo_id, $album_id);
            }

            if ($naming === 'id') {
                $new_base = 'photo-' . $photo_id;
                foreach ($result['paths'] as $preset_name => $old_relpath) {
                    $old_abspath = $this->cms_config->upload_path . $old_relpath;
                    if (!file_exists($old_abspath)) { continue; }
                    $ext = pathinfo($old_abspath, PATHINFO_EXTENSION);
                    $new_filename = $new_base . ($preset_name !== 'original' ? '_' . $preset_name : '') . '.' . $ext;
                    $new_relpath = dirname($old_relpath) . '/' . $new_filename;
                    if (rename($old_abspath, $this->cms_config->upload_path . $new_relpath)) {
                        $result['paths'][$preset_name] = $new_relpath;
                    }
                }
                $this->model->updatePhoto($photo_id, ['image' => $result['paths']]);
            }

            $thumb_path = $result['paths'][$preset_small_name] ?? end($result['paths']);

            $result['url']     = $this->cms_config->upload_host . '/' . $thumb_path;
            $result['big_url'] = $this->cms_config->upload_host . '/' . ($result['paths'][$preset_big_name] ?? reset($result['paths']));

            if ($auto_approve) {
                // Auto-fill title from original filename (truncated to 15 chars)
                $orig_filename = $_FILES['file']['name'] ?? 'photo';
                $clean_name = pathinfo($orig_filename, PATHINFO_FILENAME);
                $auto_title = mb_strlen($clean_name) > 15 ? mb_substr($clean_name, 0, 15) . '...' : $clean_name;
                $slug = $this->generateSlug($auto_title, $photo_id);
                $this->model->updatePhoto($photo_id, ['title' => $auto_title, 'slug' => $slug]);
                $result['paths'] = array_merge($result['paths'], ['slug' => $slug]);
            } else {
                // Moderation: still assign a stable slug so the photo page URL works after approval
                $slug = 'photo-' . $photo_id;
                $this->model->updatePhoto($photo_id, ['slug' => $slug]);
            }

            $result['id']      = $photo_id;
            $result['thumb']   = $this->cms_config->upload_host . '/' . $thumb_path;
            $result['pending'] = !$auto_approve;

            return $this->cms_template->renderJSON($result);

        } catch (\Throwable $e) {
            return $this->cms_template->renderJSON([
                'success' => false,
                'error'   => $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()
            ]);
        }
    }

    private function extractGpsData($filepath) {
        if (!function_exists('exif_read_data') || !is_readable($filepath)) {
            return [];
        }

        $exif = @exif_read_data($filepath, 'GPS', true);
        if (!$exif || empty($exif['GPS'])) {
            return [];
        }

        $gps = $exif['GPS'];

        if (empty($gps['GPSLatitude']) || empty($gps['GPSLongitude'])) {
            return [];
        }

        $lat = $this->gpsDmsToDecimal($gps['GPSLatitude'], $gps['GPSLatitudeRef'] ?? 'N');
        $lon = $this->gpsDmsToDecimal($gps['GPSLongitude'], $gps['GPSLongitudeRef'] ?? 'E');

        if ($lat === null || $lon === null) {
            return [];
        }

        return [
            'GPSLatitude'  => $lat,
            'GPSLongitude' => $lon,
            'gps_lat'      => $lat,
            'gps_lon'      => $lon,
        ];
    }

    private function gpsDmsToDecimal($dms, $ref) {
        if (!is_array($dms) || count($dms) < 3) {
            return null;
        }

        $conv = function ($value) {
            if (is_string($value) && strpos($value, '/') !== false) {
                list($numerator, $denominator) = array_pad(explode('/', $value), 2, 1);
                return $denominator == 0 ? 0 : $numerator / $denominator;
            }
            return (float)$value;
        };

        $degrees = $conv($dms[0]) + $conv($dms[1]) / 60 + $conv($dms[2]) / 3600;
        $ref = strtoupper(trim((string)$ref));

        if ($ref === 'S' || $ref === 'W') {
            $degrees = -$degrees;
        }

        return round($degrees, 7);
    }

    private function extractXmpDescription($filepath) {
        if (!$filepath || !file_exists($filepath)) { return ''; }

        $contents = @file_get_contents($filepath);
        if (!$contents) { return ''; }

        if (!preg_match('/<\?xpacket begin=.+?\?>(.*?)<\?xpacket end=.*?\?>/s', $contents, $m)) {
            return '';
        }

        $xmp = @simplexml_load_string($m[1]);
        if (!$xmp) { return ''; }

        $ns = $xmp->getNamespaces(true);
        $dc_ns = $ns['dc'] ?? 'http://purl.org/dc/elements/1.1/';

        $dc = $xmp->children($dc_ns);
        if (!$dc) { return ''; }

        $desc = $dc->description;
        if (!$desc) { return ''; }

        $alt = $desc->children('rdf', true)->Alt;
        if (!$alt) { return ''; }

        $li = $alt->li ?? $alt->children('rdf', true)->li;
        if (!$li) { return ''; }

        $text = trim(strip_tags((string)$li));
        return $text;
    }

    private function generateSlug($title, $id) {
        $slug = $title ? lang_slug($title) : 'photo';
        if (!$slug) { $slug = 'photo'; }
        $slug .= '-' . $id;
        return $slug;
    }

    private function userCanBypassModeration() {

        if ($this->cms_user->is_admin) {
            return true;
        }

        if (cmsCore::isModelExists('moderation')) {
            $mod = cmsCore::getModel('moderation');
            if ($mod && $mod->userIsContentModerator('galleryplus', $this->cms_user->id)) {
                return true;
            }
        }

        return false;
    }

    private function notifyPendingPhoto($photo_id, $album_id) {

        if (!cmsController::enabled('messages')) {
            return;
        }

        $recipients = [];

        $admins = $this->model->db->getRows('{users}', 'is_admin = 1', 'id');
        if ($admins) {
            foreach ($admins as $admin) {
                $recipients[(int)$admin['id']] = (int)$admin['id'];
            }
        }

        if (cmsCore::isModelExists('moderation')) {
            $mods = cmsCore::getModel('moderation')->getContentTypeModerators('galleryplus');
            if ($mods) {
                foreach ($mods as $mod) {
                    $recipients[(int)$mod['user_id']] = (int)$mod['user_id'];
                }
            }
        }

        if (!$recipients) {
            return;
        }

        $messenger = cmsCore::getController('messages');
        $messenger->clearRecipients();
        foreach ($recipients as $user_id) {
            $messenger->addRecipient($user_id);
        }

        $link = '<a href="' . href_to('admin', 'controllers', ['edit', 'galleryplus', 'pending']) . '">' . LANG_GALLERYPLUS_PENDING . '</a>';

        $messenger->sendNoticePM([
            'content' => sprintf(LANG_GALLERYPLUS_PENDING_NOTIFY_ADMIN, $link),
            'options' => ['is_closeable' => true]
        ]);
    }

    private function getDefaultAlbumId() {
        $title = $this->cms_user->nickname;
        $existing = $this->model
            ->filterEqual('title', $title)
            ->filterEqual('user_id', $this->cms_user->id)
            ->getItem('galleryplus_albums');
        $this->model->resetFilters();
        if ($existing) {
            return $existing['id'];
        }
        $slug = lang_slug($title);
        $album_id = $this->model->insert('galleryplus_albums', [
            'title'    => $title,
            'slug'     => $slug,
            'user_id'  => $this->cms_user->id,
            'privacy'  => 'public',
            'date_pub' => null,
        ]);
        return $album_id;
    }

}
