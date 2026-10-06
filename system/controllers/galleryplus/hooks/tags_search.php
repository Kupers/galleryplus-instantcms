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

        $perpage = (int)($this->options['limit'] ?? 24);
        $page = max(1, (int)$this->request->get('page', 1));

        $show_adult_to_guests = $this->options['show_adult_to_guests'] ?? 0;
        $include_adult_for_guests = !$this->cms_user->id && $show_adult_to_guests;

        $this->model
            ->join('tags_bind', 't', "t.target_id = i.id AND t.target_subject = 'album' AND t.target_controller = 'galleryplus'")
            ->filterEqual('t.tag_id', $tag['id']);
        $total = $this->model->getAlbumsCount($this->cms_user->id, $include_adult_for_guests);

        $this->model
            ->join('tags_bind', 't', "t.target_id = i.id AND t.target_subject = 'album' AND t.target_controller = 'galleryplus'")
            ->filterEqual('t.tag_id', $tag['id']);

        $albums = $this->model->getAlbums($page, $perpage, $this->cms_user->id);
        $this->model->resetFilters();

        if (!$albums) { $albums = []; }
        if (count($albums) > $perpage) { array_pop($albums); }

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
            $html .= html_pagebar($page, $perpage, $total, $page_url);
            $html .= '</div>';
        }

        return $html;
    }

    private function searchPhotos($tag, $page_url) {

        $perpage = (int)($this->options['limit'] ?? 24);
        $page = max(1, (int)$this->request->get('page', 1));

        $show_adult_to_guests = $this->options['show_adult_to_guests'] ?? 0;
        $include_adult_for_guests = !$this->cms_user->id && $show_adult_to_guests;

        $this->model
            ->join('tags_bind', 't', "t.target_id = i.id AND t.target_subject = 'photo' AND t.target_controller = 'galleryplus'")
            ->filterEqual('t.tag_id', $tag['id']);
        $total = $this->model->getPhotosCount(null, 0, $include_adult_for_guests, true);

        $this->model
            ->join('tags_bind', 't', "t.target_id = i.id AND t.target_subject = 'photo' AND t.target_controller = 'galleryplus'")
            ->filterEqual('t.tag_id', $tag['id']);

        $photos = $this->model->getPhotos($page, $perpage, 0, $include_adult_for_guests);
        $this->model->resetFilters();

        if (!$photos) { $photos = []; }
        if (count($photos) > $perpage) { array_pop($photos); }

        $can_select = $this->canSelect();
        $user_albums = [];
        if ($can_select && $this->cms_user->id) {
            $user_albums = $this->model->getUserAlbumsList($this->cms_user->id);
        }

        $html = '';

        if ($can_select) {
            $html .= '<div class="galleryplus-selection-bar" id="galleryplus-selection-bar" style="display:none">';
            $html .= '<label class="galleryplus-select-all-label">';
            $html .= '<input type="checkbox" id="galleryplus-select-all">';
            $html .= '<span class="galleryplus-checkbox"></span>';
            $html .= '<span>' . (LANG_GALLERYPLUS_SELECT_ALL ?? 'Select all') . '</span>';
            $html .= '</label>';
            $html .= '<span class="galleryplus-selection-count" id="galleryplus-selection-count"></span>';
            if ($user_albums) {
                $html .= '<select class="form-control galleryplus-move-select" id="galleryplus-move-album" style="width:auto;display:none;padding:2px 8px;font-size:13px;">';
                $html .= '<option value="">' . (LANG_GALLERYPLUS_MOVE_TO_ALBUM ?? 'Переместить в альбом') . '...</option>';
                foreach ($user_albums as $ua) {
                    $html .= '<option value="' . (int)$ua['id'] . '">' . htmlspecialchars($ua['title']) . '</option>';
                }
                $html .= '</select>';
            }
            $html .= '<button class="galleryplus-btn galleryplus-delete-btn" id="galleryplus-delete-btn" style="display:none">&#128465; ' . (LANG_GALLERYPLUS_DELETE ?? 'Delete') . '</button>';
            $html .= '</div>';
        }

        $html .= '<div class="galleryplus-grid" id="galleryplus-grid" data-is-guest="' . (empty($this->cms_user->id) ? '1' : '0') . '" data-login-url="' . htmlspecialchars(href_to('auth', 'login'), ENT_QUOTES) . '">';
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
                if ($can_select) {
                    $html .= '<label class="galleryplus-checkbox-wrap">';
                    $html .= '<input type="checkbox" class="galleryplus-select-cb" data-id="' . (int)$photo['id'] . '">';
                    $html .= '<span class="galleryplus-checkbox"></span>';
                    $html .= '</label>';
                }
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
            $html .= html_pagebar($page, $perpage, $total, $page_url);
            $html .= '</div>';
        }

        if ($can_select) {
            $save_url = href_to('galleryplus', 'save');
            $confirm_delete = addslashes(LANG_GALLERYPLUS_CONFIRM_DELETE ?? 'Delete selected photos?');
            $confirm_move = addslashes(LANG_GALLERYPLUS_CONFIRM_MOVE ?? 'Переместить выбранные фото?');
            $html .= '<script>
(function() {
    var bar = document.getElementById("galleryplus-selection-bar");
    if (!bar) return;
    var selectAll = document.getElementById("galleryplus-select-all");
    var countEl = document.getElementById("galleryplus-selection-count");
    var deleteBtn = document.getElementById("galleryplus-delete-btn");
    var moveSelect = document.getElementById("galleryplus-move-album");
    var grid = document.getElementById("galleryplus-grid");
    var saveUrl = "' . htmlspecialchars($save_url, ENT_QUOTES) . '";

    function getChecked() { return document.querySelectorAll(".galleryplus-select-cb:checked"); }

    function updateBar() {
        var n = getChecked().length;
        if (n === 0) { bar.style.display = "none"; document.body.classList.remove("galleryplus-selecting"); return; }
        bar.style.display = "";
        document.body.classList.add("galleryplus-selecting");
        countEl.textContent = n;
        deleteBtn.style.display = "";
        if (moveSelect) moveSelect.style.display = "";
    }

    function clearSelection() {
        selectAll.checked = false;
        var cbs = document.querySelectorAll(".galleryplus-select-cb");
        for (var i = 0; i < cbs.length; i++) cbs[i].checked = false;
        updateBar();
    }

    function removeItems(cbs) {
        for (var i = 0; i < cbs.length; i++) {
            var item = cbs[i].closest(".galleryplus-item");
            if (item) item.remove();
        }
        if (typeof galleryplusMasonry === "function" && grid) galleryplusMasonry(grid);
    }

    bar.addEventListener("change", function(e) {
        if (e.target === selectAll) {
            var cbs = document.querySelectorAll(".galleryplus-select-cb");
            for (var i = 0; i < cbs.length; i++) cbs[i].checked = selectAll.checked;
        }
        updateBar();
    });

    if (grid) grid.addEventListener("change", function(e) {
        if (e.target.classList.contains("galleryplus-select-cb")) updateBar();
    });

    deleteBtn.addEventListener("click", function() {
        var cbs = getChecked();
        if (!cbs.length) return;
        if (!confirm("' . $confirm_delete . '")) return;
        var ids = [];
        for (var i = 0; i < cbs.length; i++) ids.push(parseInt(cbs[i].getAttribute("data-id")));
        deleteBtn.disabled = true;
        var fd = new FormData();
        fd.append("action", "delete_photos");
        fd.append("ids", ids.join(","));
        var xhr = new XMLHttpRequest();
        xhr.open("POST", saveUrl, true);
        xhr.onload = function() {
            deleteBtn.disabled = false;
            if (xhr.status === 200) {
                try { var r = JSON.parse(xhr.responseText); } catch (e) { return; }
                if (r.success) {
                    removeItems(cbs);
                    clearSelection();
                } else if (r.error) {
                    alert(r.error);
                }
            }
        };
        xhr.send(fd);
    });

    if (moveSelect) {
        moveSelect.addEventListener("change", function() {
            var targetAlbumId = parseInt(moveSelect.value);
            if (!targetAlbumId) return;
            var cbs = getChecked();
            if (!cbs.length) { moveSelect.value = ""; return; }
            if (!confirm("' . $confirm_move . '")) { moveSelect.value = ""; return; }
            var ids = [];
            for (var i = 0; i < cbs.length; i++) ids.push(parseInt(cbs[i].getAttribute("data-id")));
            var fd = new FormData();
            fd.append("action", "move_photos");
            fd.append("ids", ids.join(","));
            fd.append("new_album_id", targetAlbumId);
            var xhr = new XMLHttpRequest();
            xhr.open("POST", saveUrl, true);
            xhr.onload = function() {
                moveSelect.value = "";
                if (xhr.status === 200) {
                    try { var r = JSON.parse(xhr.responseText); } catch (e) { return; }
                    if (r.success) {
                        removeItems(cbs);
                        clearSelection();
                    } else if (r.error) {
                        alert(r.error);
                    }
                }
            };
            xhr.send(fd);
        });
    }
})();
</script>';
        }

        ob_start();
        $this->cms_template->renderControllerChild('galleryplus', 'viewer', [
            'show_lightbox_desc'      => !empty($this->options['show_lightbox_desc']),
            'truncate_lightbox_desc'  => isset($this->options['truncate_lightbox_desc']) ? !empty($this->options['truncate_lightbox_desc']) : 1,
            'lightbox_desc_limit'     => isset($this->options['lightbox_desc_limit']) ? (int)$this->options['lightbox_desc_limit'] : 300,
            'user'                    => $this->cms_user,
        ]);
        $html .= ob_get_clean();

        return $html;
    }

    private function canSelect() {
        if (!$this->cms_user->id) { return false; }
        if ($this->cms_user->is_admin) { return true; }
        try {
            if (cmsCore::isModelExists('moderation')) {
                $mod = cmsCore::getModel('moderation');
                if ($mod && method_exists($mod, 'userIsContentModerator')) {
                    return $mod->userIsContentModerator('galleryplus', $this->cms_user->id);
                }
            }
        } catch (\Throwable $e) {}
        return false;
    }

}
