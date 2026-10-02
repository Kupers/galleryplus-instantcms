<?php

class onGalleryplusFulltextSearch extends cmsAction {

    public function run() {

        $min = 3; // минимальная длина запроса

        $sources['galleryplus'] = defined('LANG_GALLERYPLUS_TITLE') ? LANG_GALLERYPLUS_TITLE : 'Галерея';

        // по каким полям поиск
        $match_fields['galleryplus']    = ['title', 'content'];
        // какие поля получать
        $select_fields['galleryplus']   = ['id', 'title', 'content', 'image', 'sizes', 'slug', 'album_id', 'date_pub', 'user_id', 'width', 'height'];
        // из какой таблицы выборка
        $table_names['galleryplus']     = 'galleryplus_photos';

        // ищем только по опубликованным и общедоступным фото
        $filters['galleryplus'] = [
            [
                'field'     => 'is_approved',
                'condition' => '=',
                'value'     => 1
            ],
            [
                'field'     => 'is_private',
                'condition' => '=',
                'value'     => 0
            ],
            [
                'field'     => 'gp_a.is_pub',
                'condition' => '=',
                'value'     => 1
            ],
            [
                'field'     => 'gp_a.privacy',
                'condition' => '=',
                'value'     => 'public'
            ],
            [
                'field'     => 'gp_a.is_paid',
                'condition' => '=',
                'value'     => 0
            ]
        ];

        // альбомы, чтобы отсечь закрытые
        $joins['galleryplus'] = [
            'joinInner' => ['galleryplus_albums', 'gp_a', 'gp_a.id = i.album_id']
        ];

        return [
            'name'          => $this->name,
            'sources'       => $sources,
            'table_names'   => $table_names,
            'match_fields'  => $match_fields,
            'select_fields' => $select_fields,
            'filters'       => $filters,
            'joins'         => $joins,
            'item_callback' => function ($item, $model, $sources_name, $match_fields, $select_fields) {

                $item['title'] = strip_tags($item['title']);
                $item['image'] = cmsModel::yamlToArray($item['image'] ?? '');
                $item['sizes'] = cmsModel::yamlToArray($item['sizes'] ?? '');

                $item['url_thumb'] = html_image_src($item['image'], 'galleryplus_thumb', true);
                if (!$item['url_thumb']) {
                    $item['url_thumb'] = html_image_src($item['image'], 'normal', true);
                }
                if (!$item['url_thumb']) {
                    $item['url_thumb'] = html_image_src($item['image'], 'original', true);
                }
                if (!$item['url_thumb']) {
                    return false;
                }

                $item['url'] = href_to('galleryplus', $item['slug'] ?: ('photo-' . $item['id'])) . '.html';

                return $item;
            }
        ];

    }

}