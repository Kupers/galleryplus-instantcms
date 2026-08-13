<?php

class actionGalleryplusView extends cmsAction {

    public function run() {
        $slug = $this->request->get('slug', '');
        if (!$slug) { return cmsCore::error404(); }

        $user = $this->cms_user;

        $this->model->preset_small  = $this->options['preset_small'] ?? 'galleryplus_thumb';
        $this->model->preset_big    = $this->options['preset_big'] ?? 'galleryplus_big';
        $this->model->preset_nocrop = $this->options['preset_nocrop'] ?? 'galleryplus_nocrop';
        $this->model->adult_karma   = (int)($this->options['adult_karma'] ?? 0);
        $this->model->user_karma    = $user->karma ?? 0;
        $this->model->adult_rating  = (int)($this->options['adult_rating'] ?? 0);
        $this->model->user_rating   = $user->rating ?? 0;

        $show_adult_for_guests = (bool)($this->options['show_adult_to_guests'] ?? false);

        if (!empty($this->options['is_comments_photo'])) {
            $this->cms_template->addTplJSName('jquery-scroll');
            $this->cms_template->addTplJSName('comments');
        }

        $photo = $this->model->getPhotoBySlug($slug);
        if (!$photo) { return cmsCore::error404(); }

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

        $is_blurred = false;
        if (!empty($photo['album'])) {
            if (!$this->model->canAccessAlbum($photo['album'], $this->cms_user->id, $this->model->user_karma, $this->model->adult_karma, $show_adult_for_guests, $this->model->user_rating, $this->model->adult_rating)) {
                if (!$this->cms_user->id) {
                    return $this->redirectTo('auth', 'login');
                }
                return cmsCore::error404();
            }
            if (!empty($photo['album']['privacy']) && $photo['album']['privacy'] === 'adult' && !$this->cms_user->id && $show_adult_for_guests) {
                $is_blurred = true;
            }
        }

        $this->cms_template->addBreadcrumb(
            defined('LANG_GALLERYPLUS_TITLE') ? LANG_GALLERYPLUS_TITLE : 'Gallery',
            href_to('galleryplus')
        );
        if (!empty($photo['album'])) {
            if (!empty($photo['album']['category_id'])) {
                $photo_category = $this->model->getCategoryById((int)$photo['album']['category_id']);
                if ($photo_category) {
                    $this->cms_template->addBreadcrumb(
                        $photo_category['title'],
                        href_to('galleryplus', 'category', [$photo_category['slug']]) . '.html'
                    );
                }
            }
            $this->cms_template->addBreadcrumb(
                $photo['album']['title'],
                href_to('galleryplus', 'album', [$photo['album']['slug']]) . '.html'
            );
        }
        $this->cms_template->addBreadcrumb($photo['title'] ?: ($photo['filename'] ?? ''));

        $this->model->incrementCounter($photo['id']);

        $likes_count = $this->model->getLikesCount($photo['id'], 'photo');
        $user_liked  = $this->model->getUserLikeStatus($photo['id'], 'photo', $this->cms_user->id);
        $is_owner    = $this->cms_user->id && (int)$photo['user_id'] === (int)$this->cms_user->id;

        // Comments widget
        $comments_widget = '';
        if (!empty($this->options['is_comments_photo'])) {
            $cc = cmsCore::getController('comments');
            $cc->target_controller = 'galleryplus';
            $cc->target_subject    = 'photo';
            $cc->target_id         = $photo['id'];
            $cc->target_user_id    = $photo['user_id'];
            $comments_widget = $cc->getWidget();
        }

        $photo['exif_formatted'] = $this->formatExif($photo['exif'] ?? []);

        $gps_lat = null;
        $gps_lon = null;
        $exif = is_array($photo['exif'] ?? null) ? $photo['exif'] : [];
        if (!empty($exif['GPSLatitude']) && !empty($exif['GPSLongitude'])) {
            $gps_lat = (float) $exif['GPSLatitude'];
            $gps_lon = (float) $exif['GPSLongitude'];
        } elseif (!empty($exif['gps_lat']) && !empty($exif['gps_lon'])) {
            $gps_lat = (float) $exif['gps_lat'];
            $gps_lon = (float) $exif['gps_lon'];
        }

        $photo_tags = [];
        if (!empty($this->options['use_photo_tags'])) {
            $tags_model = cmsCore::getModel('tags');
            $photo_tags = $tags_model->getTagsForTarget('galleryplus', 'photo', $photo['id']);
        }

        $adjacent = $this->model->getAdjacentPhotos($photo['id'], $photo['album_id']);

        return $this->cms_template->render('view', [
            'photo'            => $photo,
            'user'             => $this->cms_user,
            'likes_count'      => $likes_count,
            'user_liked'       => $user_liked,
            'is_owner'         => $is_owner,
            'comments_widget'  => $comments_widget,
            'photo_tags'       => $photo_tags,
            'use_photo_tags'   => !empty($this->options['use_photo_tags']),
            'is_blurred'       => $is_blurred,
            'hide_exif'        => !empty($this->options['hide_exif']),
            'hide_map'         => !empty($this->options['hide_map']),
            'show_embed_codes' => !empty($this->options['show_embed_codes']),
            'gps_lat'          => $gps_lat,
            'gps_lon'          => $gps_lon,
            'prev_photo'       => $adjacent['prev'],
            'next_photo'       => $adjacent['next'],
        ]);
    }

    private function formatExif($exif) {
        if (empty($exif) || !is_array($exif)) { return []; }
        $result = [];
        $map = [
            'Make'              => defined('LANG_GALLERYPLUS_EXIF_CAMERA') ? LANG_GALLERYPLUS_EXIF_CAMERA : 'Камера',
            'Model'             => defined('LANG_GALLERYPLUS_EXIF_CAMERA') ? LANG_GALLERYPLUS_EXIF_CAMERA : 'Камера',
            'ISOSpeedRatings'   => 'ISO',
            'FNumber'           => defined('LANG_GALLERYPLUS_EXIF_APERTURE') ? LANG_GALLERYPLUS_EXIF_APERTURE : 'Диафрагма',
            'ApertureFNumber'   => defined('LANG_GALLERYPLUS_EXIF_APERTURE') ? LANG_GALLERYPLUS_EXIF_APERTURE : 'Диафрагма',
            'ExposureTime'      => defined('LANG_GALLERYPLUS_EXIF_EXPOSURE') ? LANG_GALLERYPLUS_EXIF_EXPOSURE : 'Выдержка',
            'Software'          => 'ПО',
            'Flash'             => 'Вспышка',
            'DateTimeOriginal'  => defined('LANG_GALLERYPLUS_EXIF_DATE') ? LANG_GALLERYPLUS_EXIF_DATE : 'Дата съёмки',
            'ExposureBiasValue' => 'Коррекция экспозиции',
            'Camera'            => defined('LANG_GALLERYPLUS_EXIF_CAMERA') ? LANG_GALLERYPLUS_EXIF_CAMERA : 'Камера',
            'Date'              => defined('LANG_GALLERYPLUS_EXIF_DATE') ? LANG_GALLERYPLUS_EXIF_DATE : 'Дата съёмки',
            'FocalLengthIn35mmFilm' => defined('LANG_GALLERYPLUS_EXIF_FOCAL') ? LANG_GALLERYPLUS_EXIF_FOCAL : 'Фокусное расстояние',
        ];
        $lower = array_change_key_case($map, CASE_LOWER);
        foreach ($lower as $lkey => $label) {
            $match_key = null;
            foreach ($exif as $ek => $ev) {
                if (strcasecmp($ek, $lkey) === 0) { $match_key = $ek; break; }
            }
            if ($match_key && !empty($exif[$match_key])) {
                $format_key = array_search($label, $map, true) ?: $match_key;
                $result[] = ['name' => $label, 'value' => $this->formatValue($format_key, $exif[$match_key])];
            }
        }
        $has_gps = false;
        if (!empty($exif['GPSLatitude']) && !empty($exif['GPSLongitude'])) {
            $lat = $exif['GPSLatitude'];
            $lon = $exif['GPSLongitude'];
            $result[] = ['name' => defined('LANG_GALLERYPLUS_EXIF_LOCATION') ? LANG_GALLERYPLUS_EXIF_LOCATION : 'Расположение', 'value' => $lat . ', ' . $lon];
            $has_gps = true;
        }
        if (!$has_gps && !empty($exif['gps_lat']) && !empty($exif['gps_lon'])) {
            $lat = $exif['gps_lat'];
            $lon = $exif['gps_lon'];
            $result[] = ['name' => defined('LANG_GALLERYPLUS_EXIF_LOCATION') ? LANG_GALLERYPLUS_EXIF_LOCATION : 'Расположение', 'value' => $lat . ', ' . $lon];
        }
        return $result;
    }

    private function formatValue($key, $value) {
        $val = trim((string)$value);
        switch (strtolower($key)) {
            case 'isospeedratings':
                $num = preg_replace('/\D/', '', $val);
                return $num ?: $val;
            case 'fnumber': return is_numeric($val) ? 'f/' . rtrim(rtrim(sprintf('%.1f', (float)$val), '0'), '.') : $val;
            case 'exposuretime':
                if (is_numeric($val) && $val > 0) {
                    if ($val >= 1) { return $val . 's'; }
                    $denom = round(1 / $val);
                    return $denom > 0 ? '1/' . $denom . 's' : $val . 's';
                }
                if (preg_match('#^(\d+)/(\d+)$#', $val, $m)) {
                    $frac = $m[1] / $m[2];
                    if ($frac >= 1) { return round($frac, 1) . 's'; }
                    $denom = $m[2] / $m[1];
                    return $denom > 0 ? '1/' . round($denom) . 's' : $val . 's';
                }
                return $val . 's';
            case 'focallength':
                $num = preg_replace('/[^\d.]/', '', $val);
                return $num ? $num . 'mm' : $val;
            case 'exposurebiasvalue': return ((float)$val > 0 ? '+' : '') . sprintf('%.1f', (float)$val) . ' EV';
            default: return $val;
        }
    }

}
