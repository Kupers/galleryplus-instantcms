<?php

class onGalleryplusFrontpageTypes extends cmsAction {

    public function run() {

        return [
            'name'  => $this->name,
            'types' => [
                'galleryplus:index' => defined('LANG_GALLERYPLUS_FRONTPAGE') ? LANG_GALLERYPLUS_FRONTPAGE : 'Gallery+'
            ]
        ];

    }

}