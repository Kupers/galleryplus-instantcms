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

        return [
            'photos'         => $photos,
            'map_height'     => $this->getOption('map_height', '500'),
            'default_zoom'   => $this->getOption('default_zoom', '5'),
            'map_center_lat' => $this->getOption('map_center_lat', '59.938933'),
            'map_center_lng' => $this->getOption('map_center_lng', '30.315721'),
        ];
    }
}
