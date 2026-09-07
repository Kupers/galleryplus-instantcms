<?php

class onGalleryplusUserTabShow extends cmsAction {

    public function run($profile, $tab_name, $tab) {

        $this->cms_template->addTplCSSName('galleryplus');
        $this->cms_template->addTplJSName('galleryplus');

        if ($tab_name === 'favorites') {
            return $this->runFavorites($profile, $tab);
        }

        $page_url = href_to_profile($profile, ['albums']);
        $page = $this->request->get('page', 1);
        $perpage = 12;

        $albums = $this->model->getUserAlbums($profile['id'], $page, $perpage);
        if (!$albums) { $albums = []; }

        $has_next = count($albums) > $perpage;
        if ($has_next) { array_pop($albums); }

        $is_owner = $this->cms_user->id && $profile['id'] == $this->cms_user->id;

        $html = $this->cms_template->renderInternal($this, 'profile_tab', [
            'user'      => $this->cms_user,
            'tab'       => $tab,
            'profile'   => $profile,
            'albums'    => $albums,
            'page'      => $page,
            'has_next'  => $has_next,
            'page_url'  => $page_url,
            'is_owner'  => $is_owner,
        ]);

        return $html;
    }

    private function runFavorites($profile, $tab) {

        $user = $this->cms_user;
        if (!$user->id || (int)$profile['id'] !== (int)$user->id) {
            return '';
        }

        $this->model->preset_small  = $this->options['preset_small'] ?? 'galleryplus_thumb';
        $this->model->preset_big    = $this->options['preset_big'] ?? 'galleryplus_big';
        $this->model->preset_nocrop = $this->options['preset_nocrop'] ?? 'galleryplus_nocrop';
        $this->model->adult_karma   = (int)($this->options['adult_karma'] ?? 0);
        $this->model->user_karma    = $user->karma ?? 0;
        $this->model->adult_rating  = (int)($this->options['adult_rating'] ?? 0);
        $this->model->user_rating   = $user->rating ?? 0;
        $this->model->skip_visible_albums_filter = true;

        $page = $this->request->get('page', 1);
        $perpage = 12;

        $show_adult_to_guests = $this->options['show_adult_to_guests'] ?? 0;
        $include_adult_for_guests = !$user->id && $show_adult_to_guests;

        $is_owner = $user->id && (int)$profile['id'] === (int)$user->id;

        $photos = $this->model->getFavoritePhotos($profile['id'], $page, $perpage, $include_adult_for_guests);
        if (!$photos) { $photos = []; }
        $photos = $this->applyAdultFilter($photos);
        $has_next = count($photos) > $perpage;
        if ($has_next) { array_pop($photos); }

        $ids = array_column($photos, 'id');
        $likes_data = $this->model->getPhotosLikesBatch($ids, $user->id);
        $fav_data   = $this->model->getPhotosFavoritesBatch($ids, $user->id);
        foreach ($photos as &$p) {
            $pid = $p['id'];
            $p['likes_count'] = $likes_data[$pid]['count'] ?? 0;
            $p['is_liked']    = $likes_data[$pid]['liked'] ?? false;
            $p['is_favorite'] = !empty($fav_data[$pid]);
        }
        unset($p);

        $page_url = href_to_profile($profile, ['favorites']);

        $html = $this->cms_template->renderInternal($this, 'favorites_tab', [
            'user'            => $user,
            'tab'             => $tab,
            'profile'         => $profile,
            'photos'          => $photos,
            'page'            => $page,
            'has_next'        => $has_next,
            'page_url'        => $page_url,
            'is_owner'        => $is_owner,
            'total'           => $this->model->getUserFavoritesCount($profile['id']),
            'show_lightbox_desc' => !empty($this->options['show_lightbox_desc']),
        ]);

        return $html;
    }

}
