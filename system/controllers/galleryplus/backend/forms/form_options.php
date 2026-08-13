<?php

class formGalleryplusOptions extends cmsForm {

    public $is_tabbed = true;

    public function init() {

        $presets = [];
        try {
            cmsCore::loadControllerLanguage('images');
            $images_model = cmsCore::getModel('images');
            if ($images_model) {
                $presets = $images_model->getPresetsList() ?: [];
            }
        } catch (\Exception $e) {}

        return [

            [
                'title'  => LANG_OPTIONS,
                'type'   => 'fieldset',
                'childs' => [

                    new fieldCheckbox('use_categories', [
                        'title'   => LANG_GALLERYPLUS_USE_CATEGORIES,
                        'hint'    => LANG_GALLERYPLUS_USE_CATEGORIES_HINT,
                        'default' => 0,
                    ]),

                    new fieldList('preset_small', [
                        'title'   => LANG_GALLERYPLUS_PRESET_SMALL,
                        'default' => 'galleryplus_thumb',
                        'items'   => $presets,
                        'rules'   => $presets ? [['required']] : [],
                        'hint'    => LANG_GALLERYPLUS_PRESET_SMALL_HINT,
                    ]),

                    new fieldList('preset_big', [
                        'title'   => LANG_GALLERYPLUS_PRESET_BIG,
                        'default' => 'galleryplus_big',
                        'items'   => $presets,
                        'rules'   => $presets ? [['required']] : [],
                        'hint'    => LANG_GALLERYPLUS_PRESET_BIG_HINT,
                    ]),

                    new fieldList('ordering', [
                        'title'   => LANG_SORTING,
                        'default' => 'date_pub',
                        'items'   => [
                            'date_pub'   => LANG_DATE_PUB,
                            'hits_count' => LANG_HITS,
                            'rating'     => LANG_RATING
                        ]
                    ]),

                    new fieldList('orderto', [
                        'title'   => LANG_GALLERYPLUS_ORDER_TO,
                        'default' => 'desc',
                        'items'   => [
                            'asc'  => LANG_SORTING_ASC,
                            'desc' => LANG_SORTING_DESC
                        ]
                    ]),

                    new fieldNumber('limit', [
                        'title'   => LANG_LIST_LIMIT,
                        'default' => 24,
                        'rules'   => [['required'], ['min', 1]]
                    ]),

                    new fieldList('default_mode', [
                        'title'   => LANG_GALLERYPLUS_DEFAULT_MODE,
                        'default' => 'albums',
                        'items'   => [
                            'albums'   => LANG_GALLERYPLUS_MODE_ALBUMS,
                            'infinite' => LANG_GALLERYPLUS_MODE_INFINITE,
                            'paged'    => LANG_GALLERYPLUS_MODE_PAGED,
                        ]
                    ]),

                    new fieldCheckbox('show_original', [
                        'title'   => LANG_GALLERYPLUS_SHOW_ORIGINAL,
                        'hint'    => LANG_GALLERYPLUS_SHOW_ORIGINAL_HINT,
                        'default' => 1,
                    ]),

                    new fieldList('preset_nocrop', [
                        'title'   => LANG_GALLERYPLUS_PRESET_NOCROP,
                        'default' => 'galleryplus_nocrop',
                        'items'   => $presets,
                        'hint'    => LANG_GALLERYPLUS_PRESET_NOCROP_HINT,
                    ]),

                    new fieldCheckbox('logging_enabled', [
                        'title'   => LANG_GALLERYPLUS_LOGGING,
                        'hint'    => LANG_GALLERYPLUS_LOGGING_HINT,
                        'default' => 1,
                    ]),

                ]
            ],

            [
                'title'  => LANG_GALLERYPLUS_VIEW_TAB,
                'type'   => 'fieldset',
                'childs' => [

                    new fieldCheckbox('use_album_tags', [
                        'title'   => LANG_GALLERYPLUS_USE_ALBUM_TAGS,
                        'hint'    => LANG_GALLERYPLUS_USE_ALBUM_TAGS_HINT,
                        'default' => 0,
                    ]),

                    new fieldCheckbox('use_photo_tags', [
                        'title'   => LANG_GALLERYPLUS_USE_PHOTO_TAGS,
                        'hint'    => LANG_GALLERYPLUS_USE_PHOTO_TAGS_HINT,
                        'default' => 0,
                    ]),

                    new fieldCheckbox('hide_empty_albums', [
                        'title'   => LANG_GALLERYPLUS_HIDE_EMPTY_ALBUMS,
                        'hint'    => LANG_GALLERYPLUS_HIDE_EMPTY_ALBUMS_HINT,
                        'default' => 1,
                    ]),

                    new fieldCheckbox('show_adult_in_feed', [
                        'title'   => LANG_GALLERYPLUS_SHOW_ADULT_IN_FEED,
                        'hint'    => LANG_GALLERYPLUS_SHOW_ADULT_IN_FEED_HINT,
                        'default' => 1,
                    ]),

                    new fieldCheckbox('show_adult_to_guests', [
                        'title'   => LANG_GALLERYPLUS_SHOW_ADULT_TO_GUESTS,
                        'hint'    => LANG_GALLERYPLUS_SHOW_ADULT_TO_GUESTS_HINT,
                        'default' => 0,
                    ]),

                    new fieldCheckbox('show_lightbox_desc', [
                        'title'   => LANG_GALLERYPLUS_LIGHTBOX_DESC,
                        'hint'    => LANG_GALLERYPLUS_LIGHTBOX_DESC_HINT,
                        'default' => 1,
                    ]),

                    new fieldCheckbox('hide_exif', [
                        'title'   => LANG_GALLERYPLUS_HIDE_EXIF,
                        'hint'    => LANG_GALLERYPLUS_HIDE_EXIF_HINT,
                        'default' => 0,
                    ]),

                    new fieldCheckbox('show_embed_codes', [
                        'title'   => LANG_GALLERYPLUS_SHOW_EMBED_CODES,
                        'hint'    => LANG_GALLERYPLUS_SHOW_EMBED_CODES_HINT,
                        'default' => 1,
                    ]),

                    new fieldCheckbox('hide_map', [
                        'title'   => LANG_GALLERYPLUS_HIDE_MAP,
                        'hint'    => LANG_GALLERYPLUS_HIDE_MAP_HINT,
                        'default' => 0,
                    ]),

                    new fieldNumber('map_center_lat', [
                        'title'   => LANG_GALLERYPLUS_MAP_CENTER_LAT,
                        'hint'    => LANG_GALLERYPLUS_MAP_CENTER_LAT_HINT,
                        'default' => 59.938933,
                        'rules'   => [['required']],
                    ]),

                    new fieldNumber('map_center_lng', [
                        'title'   => LANG_GALLERYPLUS_MAP_CENTER_LNG,
                        'hint'    => LANG_GALLERYPLUS_MAP_CENTER_LNG_HINT,
                        'default' => 30.315721,
                        'rules'   => [['required']],
                    ]),

                ]
            ],

            [
                'title'  => LANG_PERMISSIONS,
                'type'   => 'fieldset',
                'childs' => [

                    new fieldNumber('upload_karma', [
                        'title'   => LANG_GALLERYPLUS_KARMA_UPLOAD,
                        'hint'    => LANG_GALLERYPLUS_KARMA_UPLOAD_HINT,
                        'default' => 0,
                        'rules'   => [['min', 0]],
                    ]),

                    new fieldNumber('adult_karma', [
                        'title'   => LANG_GALLERYPLUS_KARMA_ADULT,
                        'hint'    => LANG_GALLERYPLUS_KARMA_ADULT_HINT,
                        'default' => 0,
                        'rules'   => [['min', 0]],
                    ]),

                    new fieldNumber('adult_rating', [
                        'title'   => LANG_GALLERYPLUS_RATING_ADULT,
                        'hint'    => LANG_GALLERYPLUS_RATING_ADULT_HINT,
                        'default' => 0,
                        'rules'   => [['min', 0]],
                    ]),

                ]
            ],

            [
                'title'  => LANG_GALLERYPLUS_UPLOAD,
                'type'   => 'fieldset',
                'childs' => [

                    new fieldCheckbox('auto_approve', [
                        'title'   => LANG_GALLERYPLUS_AUTO_APPROVE,
                        'hint'    => LANG_GALLERYPLUS_AUTO_APPROVE_HINT,
                        'default' => 1,
                    ]),

                    new fieldList('naming_scheme', [
                        'title'   => LANG_GALLERYPLUS_NAMING_SCHEME,
                        'default' => 'original',
                        'items'   => [
                            'original' => LANG_GALLERYPLUS_NAMING_ORIGINAL,
                            'random'   => LANG_GALLERYPLUS_NAMING_RANDOM,
                            'mixed'    => LANG_GALLERYPLUS_NAMING_MIXED,
                            'id'       => LANG_GALLERYPLUS_NAMING_ID,
                        ]
                    ]),

                    new fieldNumber('max_file_size', [
                        'title'   => LANG_GALLERYPLUS_MAX_FILE_SIZE,
                        'hint'    => LANG_GALLERYPLUS_MAX_FILE_SIZE_HINT,
                        'default' => 0,
                        'rules'   => [['min', 0]]
                    ]),

                    new fieldNumber('max_width', [
                        'title'   => LANG_GALLERYPLUS_MAX_WIDTH,
                        'default' => 0,
                        'rules'   => [['min', 0]]
                    ]),

                    new fieldNumber('max_height', [
                        'title'   => LANG_GALLERYPLUS_MAX_HEIGHT,
                        'default' => 0,
                        'rules'   => [['min', 0]]
                    ]),

                ]
            ],

            [
                'title'  => LANG_GALLERYPLUS_COMMENTS_LIKES,
                'type'   => 'fieldset',
                'childs' => [

                    new fieldCheckbox('is_comments_photo', [
                        'title'   => LANG_GALLERYPLUS_COMMENTS_PHOTO,
                        'default' => 1,
                    ]),

                    new fieldCheckbox('is_comments_album', [
                        'title'   => LANG_GALLERYPLUS_COMMENTS_ALBUM,
                        'default' => 0,
                    ]),

                ]
            ],

        ];

    }

}
