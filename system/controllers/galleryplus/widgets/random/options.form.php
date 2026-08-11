<?php
class formWidgetGalleryplusRandomOptions extends cmsForm {

    public function init() {
        return [
            [
                'type' => 'fieldset',
                'title' => LANG_OPTIONS,
                'childs' => [
                    new fieldNumber('options:limit', [
                        'title' => LANG_LIST_LIMIT,
                        'default' => 6,
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
