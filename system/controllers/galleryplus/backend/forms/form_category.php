<?php

class formGalleryplusCategory extends cmsForm {

    public function init($params = []) {

        $is_edit = !empty($params['id']);

        return [

            [
                'type'   => 'fieldset',
                'title'  => LANG_GALLERYPLUS_CATEGORY,
                'childs' => [

                    new fieldString('title', [
                        'title'   => LANG_GALLERYPLUS_CATEGORY_TITLE,
                        'default' => $params['title'] ?? '',
                        'rules'   => [['required']],
                    ]),

                    new fieldString('slug', [
                        'title'    => LANG_GALLERYPLUS_CATEGORY_SLUG,
                        'default'  => $params['slug'] ?? '',
                        'readonly' => $is_edit,
                    ]),

                    new fieldText('description', [
                        'title'   => LANG_GALLERYPLUS_CATEGORY_DESC,
                        'default' => $params['description'] ?? '',
                    ]),

                    new fieldNumber('ordering', [
                        'title'   => LANG_GALLERYPLUS_SORTING_ORDER,
                        'default' => $params['ordering'] ?? 0,
                    ]),

                    new fieldCheckbox('is_hidden', [
                        'title'   => LANG_GALLERYPLUS_IS_HIDDEN,
                        'hint'    => LANG_GALLERYPLUS_IS_HIDDEN_HINT,
                        'default' => $params['is_hidden'] ?? 0,
                    ]),

                ]
            ],

        ];

    }

}
