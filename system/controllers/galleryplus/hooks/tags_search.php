<?php

class onGalleryplusTagsSearch extends cmsAction {

    public $disallow_event_db_register = true;

    public function run($target_subject, $tag, $page_url) {

        $this->cms_template->addTplCSSName('galleryplus');
        $this->cms_template->addTplJSName('galleryplus');

        $user = $this->cms_user;

        $this->model->preset_small  = $this->options['preset_small'] ?? 'galleryplus_thumb';
        $this->model->preset_big    = $this->options['preset_big'] ?? 'galleryplus_big';
        $this->model->preset_nocrop = $this->options['preset_nocrop'] ?? 'galleryplus_nocrop';
        $this->model->adult_karma   = (int)($this->options['adult_karma'] ?? 0);
        $this->model->user_karma    = $user->karma ?? 0;
        $this->model->adult_rating  = (int)($this->options['adult_rating'] ?? 0);
        $this->model->user_rating   = $user->rating ?? 0;

        if ($target_subject === 'album') {
            return $this->searchAlbums($tag, $page_url);
        }

        if ($target_subject === 'photo') {
            return $this->searchPhotos($tag, $page_url);
        }

        return '';
    }

    private function searchAlbums($tag, $page_url) {

        $perpage = $this->options['limit'] ?? 24;
        $page = $this->request->get('page', 1);

        $this->model
            ->join('tags_bind', 't', "t.target_id = i.id AND t.target_subject = 'album' AND t.target_controller = 'galleryplus'")
            ->filterEqual('t.tag_id', $tag['id']);
        $total = $this->model->getCount('galleryplus_albums');
        $this->model->resetFilters();

        $this->model
            ->join('tags_bind', 't', "t.target_id = i.id AND t.target_subject = 'album' AND t.target_controller = 'galleryplus'")
            ->filterEqual('t.tag_id', $tag['id']);

        $albums = $this->model->getAlbums($page, $perpage, $this->cms_user->id);
        $this->model->resetFilters();

        if (!$albums) { $albums = []; }

        foreach ($albums as &$a) {
            $a['is_owner'] = $this->cms_user->id && $a['user_id'] == $this->cms_user->id;
        }
        unset($a);

        $use_album_tags = !empty($this->options['use_album_tags']);
        if ($use_album_tags && $albums) {
            $tags_model = cmsCore::getModel('tags');
            foreach ($albums as &$a) {
                $a['tags'] = $tags_model->getTagsForTarget('galleryplus', 'album', $a['id']);
            }
            unset($a);
        }

        $html = '<div class="galleryplus-albums-grid">';
        if ($albums) {
            foreach ($albums as $a) {
                $is_adult = ($a['privacy'] ?? '') === 'adult';
                $is_owner = !empty($a['is_owner']);
                $show_blur = $is_adult && !$is_owner;
                $adult_class = $show_blur ? ' galleryplus-album-card--adult' : '';
                $cover_html = '';
                if ($show_blur) {
                    $cover_html = '<img src="' . ($a['cover_url'] ?: '') . '" alt="" loading="lazy" class="galleryplus-blurred" data-width="' . ($a['cover_width'] ?? 0) . '" data-height="' . ($a['cover_height'] ?? 0) . '" style="' . ($a['cover_url'] ? '' : 'display:none;') . '">';
                    $cover_html .= '<div class="galleryplus-adult-badge">18+</div>';
                    if (!$a['cover_url']) {
                        $cover_html .= '<div class="galleryplus-album-cover-empty" style="position:relative;z-index:1;">' . (LANG_GALLERYPLUS_NO_PHOTOS ?? 'No photos') . '</div>';
                    }
                } elseif ($a['cover_url']) {
                    $cover_html = '<img src="' . $a['cover_url'] . '" alt="' . htmlspecialchars($a['title']) . '" loading="lazy" data-width="' . ($a['cover_width'] ?? 0) . '" data-height="' . ($a['cover_height'] ?? 0) . '">';
                } else {
                    $cover_html = '<div class="galleryplus-album-cover-empty">' . (LANG_GALLERYPLUS_NO_PHOTOS ?? 'No photos') . '</div>';
                }
                $html .= '<a href="' . $a['url'] . '" class="galleryplus-album-card' . $adult_class . '">';
                $html .= '<div class="galleryplus-album-cover">' . $cover_html;
                $html .= '<div class="galleryplus-album-info">';
                $html .= '<span class="galleryplus-album-title">' . htmlspecialchars($a['title']) . '</span>';
                if ($show_blur) {
                    $html .= '<span class="galleryplus-album-adult-label">18+</span>';
                }
                $html .= '<span class="galleryplus-album-count">' . ($a['photo_count'] ?? 0) . ' ' . (LANG_GALLERYPLUS_PHOTOS ?? 'photos') . '</span>';
                $html .= '<span class="galleryplus-album-likes">&#10084; ' . ($a['likes_count'] ?? 0) . '</span>';
                $html .= '</div></div></a>';
            }
        }
        $html .= '</div>';

        if ($total > $perpage) {
            $html .= '<div class="galleryplus-pagination">';
            $html .= html_pagebar($page, $total, $perpage, $page_url . '&page=%s');
            $html .= '</div>';
        }

        return $html;
    }

    private function searchPhotos($tag, $page_url) {

        $perpage = $this->options['limit'] ?? 24;
        $page = $this->request->get('page', 1);

        $this->model
            ->join('tags_bind', 't', "t.target_id = i.id AND t.target_subject = 'photo' AND t.target_controller = 'galleryplus'")
            ->filterEqual('t.tag_id', $tag['id']);
        $total = $this->model->getCount('galleryplus_photos');
        $this->model->resetFilters();

        $this->model
            ->join('tags_bind', 't', "t.target_id = i.id AND t.target_subject = 'photo' AND t.target_controller = 'galleryplus'")
            ->filterEqual('t.tag_id', $tag['id']);

        $show_adult_to_guests = $this->options['show_adult_to_guests'] ?? 0;
        $include_adult_for_guests = !$this->cms_user->id && $show_adult_to_guests;

        $photos = $this->model->getPhotos($page, $perpage, 0, $include_adult_for_guests);
        $this->model->resetFilters();

        if (!$photos) { $photos = []; }

        $html = '<div class="galleryplus-grid">';
        if ($photos) {
            foreach ($photos as $photo) {
                $title = htmlspecialchars($photo['title'] ?: ($photo['filename'] ?? ''));
                $author = htmlspecialchars($photo['user']['nickname'] ?? '');
                $is_adult = !empty($photo['is_adult']);
                $likes_count = $photo['likes_count'] ?? 0;
                $comments_count = $photo['comments'] ?? 0;
                $obj = htmlspecialchars(json_encode([
                    'id'       => $photo['id'],
                    'url'      => $photo['url'],
                    'src'      => $photo['url_big'],
                    'nocrop'   => $photo['url_nocrop'] ?: '',
                    'thumb'    => $photo['url_thumb'],
                    'title'    => $title,
                    'author'   => $author,
                    'adult'    => $is_adult,
                    'likes'    => $likes_count,
                    'owner_id' => $photo['user_id'],
                    'comments' => $comments_count,
                    'desc'     => $photo['content'] ?? '',
                ], JSON_UNESCAPED_UNICODE));
                $adult_class = $is_adult ? ' galleryplus-item--adult' : '';
                $html .= '<div class="galleryplus-item' . $adult_class . '" data-object="' . $obj . '">';
                $html .= '<a href="' . $photo['url'] . '" class="galleryplus-viewer-link">';
                $html .= '<img src="' . $photo['url_thumb'] . '" alt="' . $title . '" loading="lazy" width="' . ($photo['width'] ?? 0) . '" height="' . ($photo['height'] ?? 0) . '" class="' . ($is_adult ? 'galleryplus-blurred' : '') . '">';
                if ($is_adult) { $html .= '<div class="galleryplus-adult-badge">18+</div>'; }
                $html .= '</a>';
                $html .= '<div class="galleryplus-item-overlay">';
                $html .= '<a href="' . $photo['url'] . '" class="galleryplus-item-overlay-title">' . $title . '</a>';
                $html .= '<div class="galleryplus-item-overlay-bottom">';
                $html .= '<span class="galleryplus-item-author">' . $author . '</span>';
                $html .= '<div class="galleryplus-item-overlay-stats">';
                $html .= '<span class="galleryplus-item-likes">&#10084; ' . $likes_count . '</span>';
                $html .= '<span class="galleryplus-item-comments">&#9993; ' . $comments_count . '</span>';
                $html .= '</div></div></div></div>';
            }
        }
        $html .= '</div>';

        if ($total > $perpage) {
            $html .= '<div class="galleryplus-pagination">';
            $html .= html_pagebar($page, $total, $perpage, $page_url . '&page=%s');
            $html .= '</div>';
        }

        return $html;
    }

}
