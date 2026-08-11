<?php
class formWidgetGalleryplusAlbumsOptions extends cmsForm {

    public function init() {
        return [
            [
                'type' => 'fieldset',
                'title' => LANG_OPTIONS,
                'childs' => [
                    new fieldNumber('options:limit', [
                        'title' => LANG_LIST_LIMIT,
                        'default' => 10,
                        'rules' => [
                            ['required'],
                            ['min', 1],
                        ]
                    ]),
                ]
            ]
        ];
    }
}
