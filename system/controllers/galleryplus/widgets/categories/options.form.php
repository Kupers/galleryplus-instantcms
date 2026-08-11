<?php
class formWidgetGalleryplusCategoriesOptions extends cmsForm {

    public function init() {
        return [
            [
                'type' => 'fieldset',
                'title' => LANG_OPTIONS,
                'childs' => [
                    new fieldNumber('options:limit', [
                        'title' => LANG_LIST_LIMIT,
                        'default' => 0,
                        'hint' => '0 — ' . LANG_GALLERYPLUS_ALL_CATEGORIES,
                        'rules' => [
                            ['min', 0],
                        ]
                    ]),
                    new fieldCheckbox('options:show_counts', [
                        'title' => LANG_GALLERYPLUS_WIDGET_SHOW_COUNTS,
                        'default' => 1,
                    ]),
                ]
            ]
        ];
    }
}
