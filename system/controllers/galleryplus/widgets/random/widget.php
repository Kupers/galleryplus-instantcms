<?php
class widgetGalleryplusRandom extends cmsWidget {

    public function run() {
        $model = cmsCore::getModel('galleryplus');
        $user = cmsUser::getInstance();
        $current_user_id = cmsUser::get('id');

        $this->disableCache();

        $model->joinUser();
        $model->filterPrivacy();
        $model->filterApprovedOnly();
        $model->filterVisibleAlbums($current_user_id);

        $limit = $this->getOption('limit', 6);

        $total = $model->getCount('galleryplus_photos');
        if (!$total) { return false; }

        $offset = ($total > $limit) ? rand(0, $total - $limit) : 0;
        $model->limit($offset, $limit);

        $photos = $model->get('galleryplus_photos', function ($item, $model) {
            $item['image'] = cmsModel::yamlToArray($item['image']);
            $item['sizes'] = cmsModel::yamlToArray($item['sizes']);
            $item['user'] = [
                'id'       => $item['user_id'],
                'nickname' => $item['user_nickname'],
                'slug'     => $item['user_slug'],
                'avatar'   => $item['user_avatar'],
            ];
            $item = $model->decoratePhoto($item);
            $item['url_thumb']    = html_image_src($item['image'], $model->preset_small, true);
            $item['url_big']      = html_image_src($item['image'], $model->preset_big, true)
                ?: html_image_src($item['image'], 'normal', true);
            return $item;
        }, false);

        if (!$photos) { return false; }

        return [
            'photos' => $photos,
        ];
    }
}
