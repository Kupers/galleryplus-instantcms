<?php
class widgetGalleryplusAlbums extends cmsWidget {

    public function run() {
        $model = cmsCore::getModel('galleryplus');
        $user = cmsUser::getInstance();
        $current_user_id = cmsUser::get('id');

        $model->limit($this->getOption('limit', 10));
        $model->orderBy('i.date_pub', 'desc');

        $albums = $model->getAlbums(1, $this->getOption('limit', 10), $current_user_id);
        if (!$albums) { return false; }

        $albums_list = [];
        foreach ($albums as $album) {
            $albums_list[] = [
                'id'         => $album['id'],
                'title'      => $album['title'],
                'slug'       => $album['slug'],
                'url'        => $album['url'],
                'cover_url'  => $album['cover_url'],
                'photo_count' => $album['photo_count'],
                'likes_count' => $album['likes_count'],
                'user'       => $album['user'],
                'is_protected' => $model->isAlbumProtected($album),
                'privacy'    => $album['privacy'] ?? '',
            ];
        }

        return [
            'albums' => $albums_list,
        ];
    }
}
