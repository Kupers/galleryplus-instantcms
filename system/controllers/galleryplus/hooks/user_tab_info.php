<?php

class onGalleryplusUserTabInfo extends cmsAction {

    public function run($profile, $tab_name) {

        if ($tab_name === 'favorites') {
            // Избранное — личное: вкладка видна только владельцу профиля
            if (!$this->cms_user->id || (int)$this->cms_user->id !== (int)$profile['id']) {
                return false;
            }
            $count = $this->model->getUserFavoritesCount($profile['id']);
            if (!$count) { return false; }
            return [
                'title'   => LANG_GALLERYPLUS_FAVORITES,
                'counter' => $count
            ];
        }

        $count = $this->model->getUserAlbumsCount($profile['id']);

        if (!$count) { return false; }

        return [
            'title'   => LANG_GALLERYPLUS_ALBUMS,
            'counter' => $count
        ];
    }

}
