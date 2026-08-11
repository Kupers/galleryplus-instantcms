<?php
class widgetGalleryplusPhotos extends cmsWidget {

    public function run() {
        $model = cmsCore::getModel('galleryplus');
        $user = cmsUser::getInstance();
        $current_user_id = cmsUser::get('id');

        $model->orderBy('i.date_pub', 'desc');
        $model->joinUser();
        if (!$model->isPrivacyFilterDisabled()) { $model->filterPrivacy(); }
        if (!$model->isApprovedFilterDisabled()) { $model->filterApprovedOnly(); }

        $model->filterVisibleAlbums($current_user_id);

        $model->limitPagePlus(1, $this->getOption('limit', 10));

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
