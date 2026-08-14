<?php
class widgetGalleryplusMap extends cmsWidget {

    public function run() {

        $model = cmsCore::getModel('galleryplus');
        $user = cmsUser::getInstance();
        $current_user_id = cmsUser::get('id');

        $limit = $this->getOption('limit', 0);

        $model->joinUser();
        $model->filterPrivacy();
        $model->filterApprovedOnly();
        $model->filterVisibleAlbums($current_user_id);
        $model->resetFilters();

        $db = cmsDatabase::getInstance();

        $sql = "SELECT i.id, i.title, i.slug, i.exif, i.image, i.user_id,
                       u.nickname AS user_nickname, u.slug AS user_slug
                FROM {#}galleryplus_photos AS i
                LEFT JOIN {#}users AS u ON u.id = i.user_id
                WHERE i.exif LIKE '%GPSLatitude%'
                  AND i.is_approved = 1";

        $user_id = cmsUser::get('id');
        if ($user_id) {
            $sql .= " AND i.id NOT IN (
                SELECT b.id FROM {#}galleryplus_albums AS a
                JOIN {#}galleryplus_photos AS b ON b.album_id = a.id
                WHERE a.privacy = 'private' AND a.user_id != {$user_id}
            )";
        } else {
            $sql .= " AND i.id NOT IN (
                SELECT b.id FROM {#}galleryplus_albums AS a
                JOIN {#}galleryplus_photos AS b ON b.album_id = a.id
                WHERE a.privacy = 'private'
            )";
        }

        $sql .= " ORDER BY i.date_pub DESC";

        if ($limit > 0) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $result = $db->query($sql);
        if (!$result) {
            return false;
        }
        $rows = $db->fetchAll($result);

        if (!$rows) {
            return false;
        }

        $photos = [];
        foreach ($rows as $row) {
            $exif = cmsModel::yamlToArray($row['exif']);

            $lat = null;
            $lon = null;

            if (!empty($exif['GPSLatitude']) && !empty($exif['GPSLongitude'])) {
                $lat = (float)$exif['GPSLatitude'];
                $lon = (float)$exif['GPSLongitude'];
            } elseif (!empty($exif['gps_lat']) && !empty($exif['gps_lon'])) {
                $lat = (float)$exif['gps_lat'];
                $lon = (float)$exif['gps_lon'];
            }

            if ($lat === null || $lon === null) {
                continue;
            }

            $image = cmsModel::yamlToArray($row['image']);

            $slug = $row['slug'] ?: 'photo-' . $row['id'];
            $title = $row['title'];
            if (empty($title)) {
                $orig = $image['original'] ?? '';
                $title = $orig ? pathinfo($orig, PATHINFO_FILENAME) : 'photo-' . $row['id'];
            }

            $photos[] = [
                'id'       => $row['id'],
                'title'    => $title,
                'slug'     => $slug,
                'url'      => href_to('galleryplus', $slug) . '.html',
                'lat'      => $lat,
                'lon'      => $lon,
                'thumb'    => html_image_src($image, 'galleryplus_thumb', true),
                'user'     => [
                    'nickname' => $row['user_nickname'],
                    'slug'     => $row['user_slug'],
                ],
            ];
        }

        if (empty($photos)) {
            return false;
        }

        $map_center_lat = $this->getOption('map_center_lat', '59.938933');
        $map_center_lng = $this->getOption('map_center_lng', '30.315721');

        if ($this->getOption('map_user_loc', 0)) {
            $user_loc = $this->getUserLocation();
            if ($user_loc) {
                $map_center_lat = $user_loc['lat'];
                $map_center_lng = $user_loc['lon'];
            }
        }

        return [
            'photos'         => $photos,
            'map_height'     => $this->getOption('map_height', '500'),
            'default_zoom'   => $this->getOption('default_zoom', '5'),
            'map_center_lat' => $map_center_lat,
            'map_center_lng' => $map_center_lng,
        ];
    }

    private function getUserLocation() {

        $ip = cmsUser::getIp();

        if (!$ip || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        $cache_dir  = cmsConfig::get('cache_path') . 'galleryplus_map' . DIRECTORY_SEPARATOR;
        $cache_file = $cache_dir . md5($ip) . '.json';

        if (is_file($cache_file)) {
            if ((time() - filemtime($cache_file)) > 86400) {
                @unlink($cache_file);
            } else {
                $cached = @json_decode(file_get_contents($cache_file), true);
                if (is_array($cached)) {
                    if (isset($cached['lat'], $cached['lon'])) {
                        return $cached;
                    }
                    return null;
                }
            }
        }

        // локальные/приватные/служебные адреса не определяются — не дёргаем API
        if (!$this->isPublicIp($ip)) {
            $this->writeGeoCache($cache_dir, $cache_file, null);
            return null;
        }

        $coords = $this->fetchGeoFromApi($ip);

        $this->writeGeoCache($cache_dir, $cache_file, $coords);

        return $coords;
    }

    private function isPublicIp($ip) {

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    private function writeGeoCache($cache_dir, $cache_file, $coords) {

        if (!is_dir($cache_dir)) {
            @mkdir($cache_dir, 0777, true);
        }

        @file_put_contents($cache_file, $coords ? json_encode($coords) : json_encode(['fail' => 1]));
    }

    private function fetchGeoFromApi($ip) {

        // ip-api.com (бесплатно, без ключа; только http)
        $data = $this->httpGetJson('http://ip-api.com/json/' . $ip . '?fields=status,lat,lon,query');
        if (isset($data['status']) && $data['status'] === 'success' && isset($data['lat'], $data['lon'])) {
            return [
                'lat' => (float)$data['lat'],
                'lon' => (float)$data['lon'],
            ];
        }

        // fallback: ipwho.is (бесплатно, без ключа, https)
        $data = $this->httpGetJson('https://ipwho.is/' . $ip);
        if (!empty($data['success']) && isset($data['latitude'], $data['longitude'])) {
            return [
                'lat' => (float)$data['latitude'],
                'lon' => (float)$data['longitude'],
            ];
        }

        return null;
    }

    private function httpGetJson($url) {

        $options = [
            'http' => [
                'timeout'       => 4,
                'ignore_errors' => true,
                'header'        => "User-Agent: GalleryPlus/1.0\r\n",
            ],
        ];

        $body = @file_get_contents($url, false, stream_context_create($options));

        if ($body === false && function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 4,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT      => 'GalleryPlus/1.0',
            ]);
            $body = curl_exec($ch);
            curl_close($ch);
        }

        if (!$body) {
            return null;
        }

        $data = json_decode($body, true);

        return is_array($data) ? $data : null;
    }
}
