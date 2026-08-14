<?php
class formWidgetGalleryplusMapOptions extends cmsForm {

    public function init() {
        return [
            [
                'type' => 'fieldset',
                'title' => LANG_OPTIONS,
                'childs' => [
                    new fieldNumber('options:map_height', [
                        'title' => LANG_GALLERYPLUS_WIDGET_MAP_HEIGHT,
                        'default' => 500,
                        'hint' => 'px',
                        'rules' => [
                            ['required'],
                            ['min', 200],
                        ]
                    ]),
                    new fieldNumber('options:default_zoom', [
                        'title' => LANG_GALLERYPLUS_WIDGET_MAP_ZOOM,
                        'default' => 5,
                        'rules' => [
                            ['required'],
                            ['min', 1],
                            ['max', 18],
                        ]
                    ]),
                    new fieldNumber('options:limit', [
                        'title' => LANG_LIST_LIMIT,
                        'default' => 0,
                        'hint' => '0 — ' . (defined('LANG_GALLERYPLUS_ALL') ? LANG_GALLERYPLUS_ALL : 'Все'),
                        'rules' => [
                            ['min', 0],
                        ]
                    ]),
                    new fieldNumber('options:map_center_lat', [
                        'title' => LANG_GALLERYPLUS_MAP_CENTER_LAT,
                        'default' => 59.938933,
                        'hint' => LANG_GALLERYPLUS_MAP_CENTER_LAT_HINT,
                        'rules' => [
                            ['min', -90],
                            ['max', 90],
                        ]
                    ]),
                    new fieldNumber('options:map_center_lng', [
                        'title' => LANG_GALLERYPLUS_MAP_CENTER_LNG,
                        'default' => 30.315721,
                        'hint' => LANG_GALLERYPLUS_MAP_CENTER_LNG_HINT,
                        'rules' => [
                            ['min', -180],
                            ['max', 180],
                        ]
                    ]),
                    new fieldCheckbox('options:map_user_loc', [
                        'title' => LANG_GALLERYPLUS_WIDGET_MAP_USER_LOC,
                        'hint' => LANG_GALLERYPLUS_WIDGET_MAP_USER_LOC_HINT,
                        'default' => 0,
                    ]),
                ]
            ]
        ];
    }
}
