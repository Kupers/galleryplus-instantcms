<?php

class onGalleryplusUserTabInfo extends cmsAction {

    public function run($profile, $tab_name) {

        $count = $this->model->getUserAlbumsCount($profile['id']);

        if (!$count) { return false; }

        return [
            'title'   => LANG_GALLERYPLUS_ALBUMS,
            'counter' => $count
        ];
    }

}
