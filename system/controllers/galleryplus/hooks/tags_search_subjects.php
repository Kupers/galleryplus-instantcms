<?php

class onGalleryplusTagsSearchSubjects extends cmsAction {

    public function run($data) {

        list($tag, $targets) = $data;

        $menu_items = [];

        if (empty($targets[$this->name])) {
            return $menu_items;
        }

        $subjects = array_unique($targets[$this->name]);

        $labels = [
            'album' => LANG_GALLERYPLUS_ALBUMS ?? 'Albums',
            'photo' => LANG_GALLERYPLUS_TITLE ?? 'Gallery',
        ];

        foreach ($subjects as $subject) {
            $key = $this->name . '-' . $subject;
            $menu_items[$key] = [
                'title' => $labels[$subject] ?? $subject,
                'url'   => href_to('tags', $key, [string_urlencode($tag['tag'])]),
            ];
        }

        return $menu_items;
    }

}
