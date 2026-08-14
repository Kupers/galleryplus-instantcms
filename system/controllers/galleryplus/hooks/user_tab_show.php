<?php

class onGalleryplusUserTabShow extends cmsAction {

    public function run($profile, $tab_name, $tab) {

        $this->cms_template->addTplCSSName('galleryplus');
        $this->cms_template->addTplJSName('galleryplus');

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

}
