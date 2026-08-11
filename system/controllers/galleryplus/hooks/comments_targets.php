<?php

class onGalleryplusCommentsTargets extends cmsAction {

    public function run() {
        return [
            'name'  => 'galleryplus',
            'types' => [
                'galleryplus:photo' => LANG_GALLERYPLUS_PHOTO ?? 'Photos',
                'galleryplus:album' => LANG_GALLERYPLUS_ALBUM ?? 'Albums',
            ]
        ];
    }

}
