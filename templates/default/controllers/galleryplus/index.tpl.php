<?php
    $this->addTplCSSName('galleryplus');
    $this->addTplJSName('galleryplus');
    $this->addTplJSName('jquery-cookie');
    $current_category = $current_category ?? null;
    $categories = $categories ?? [];
    $use_categories = $use_categories ?? false;
    if ($current_category) {
        $this->setPageTitle($current_category['title'] . ' — ' . (LANG_GALLERYPLUS_TITLE ?? 'Gallery'));
        $this->setPageDescription($current_category['description'] ?: $current_category['title']);
    } else {
        $this->setPageTitle($mode === 'albums' ? (LANG_GALLERYPLUS_ALBUMS ?? 'Albums') : (LANG_GALLERYPLUS_TITLE ?? 'Gallery'));
        $this->setPageDescription(LANG_GALLERYPLUS_DESC ?? 'Explore all images');
    }
    $explore = $explore ?? 'recent';
    $mode = $mode ?? 'albums';
    $is_albums = $mode === 'albums';
    $is_infinite = $mode === 'infinite';
    $is_paged = $mode === 'paged';
?>

<div class="galleryplus">
    <?php if ($use_categories && $current_category) { ?>
        <div class="galleryplus-breadcrumbs">
            <a href="<?php echo href_to('galleryplus'); ?>"><?php echo LANG_GALLERYPLUS_TITLE ?? 'Gallery'; ?></a>
            <span class="galleryplus-breadcrumbs-sep">&raquo;</span>
            <span><?php html($current_category['title']); ?></span>
        </div>
    <?php } ?>

    <div class="galleryplus-header">
        <h1 class="galleryplus-title"><?php echo $current_category ? htmlspecialchars($current_category['title']) : ($is_albums ? (LANG_GALLERYPLUS_ALBUMS ?? 'Albums') : (LANG_GALLERYPLUS_TITLE ?? 'Gallery')); ?></h1>

        <div class="galleryplus-toolbar">
            <div class="galleryplus-tabs">
                <?php if (!$is_albums) { ?>
                    <a href="<?php echo href_to('galleryplus'); ?>" class="galleryplus-tab<?php echo $explore === 'recent' ? ' active' : ''; ?>"><?php echo LANG_GALLERYPLUS_RECENT ?? 'New'; ?></a>
                    <a href="<?php echo href_to('galleryplus', 'explore', ['popular']); ?>" class="galleryplus-tab<?php echo $explore === 'popular' ? ' active' : ''; ?>"><?php echo LANG_GALLERYPLUS_POPULAR ?? 'Popular'; ?></a>
                    <a href="<?php echo href_to('galleryplus', 'explore', ['trending']); ?>" class="galleryplus-tab<?php echo $explore === 'trending' ? ' active' : ''; ?>"><?php echo LANG_GALLERYPLUS_TRENDING ?? 'Trending'; ?></a>
                    <a href="<?php echo href_to('galleryplus', 'explore', ['liked']); ?>" class="galleryplus-tab<?php echo $explore === 'liked' ? ' active' : ''; ?>">&#10084;<span class="galleryplus-tab-label"> <?php echo LANG_GALLERYPLUS_LIKED ?? 'Liked'; ?></span></a>
                <?php } ?>
            </div>
            <div class="galleryplus-toolbar-right">
                <?php if ($user->is_logged) { ?>
                    <a href="<?php echo href_to('galleryplus', 'upload'); ?>" class="galleryplus-btn galleryplus-upload-btn">+ <?php echo defined('LANG_GALLERYPLUS_UPLOAD') ? LANG_GALLERYPLUS_UPLOAD : 'Upload'; ?></a>
                <?php } ?>
                <?php $cat_param = $current_category ? '&category=' . urlencode($current_category['slug']) : ''; ?>
                <a href="<?php echo href_to('galleryplus') . '?mode=albums' . $cat_param; ?>" class="galleryplus-btn <?php echo $is_albums ? 'active' : ''; ?>" title="<?php echo LANG_GALLERYPLUS_ALBUMS ?? 'Albums'; ?>">&#9776;</a>
                <a href="<?php echo href_to('galleryplus') . '?mode=infinite' . $cat_param; ?>" class="galleryplus-btn <?php echo $is_infinite ? 'active' : ''; ?>" title="<?php echo LANG_GALLERYPLUS_INFINITE ?? 'Infinite'; ?>">&#8734;</a>
                <a href="<?php echo href_to('galleryplus') . '?mode=paged' . $cat_param; ?>" class="galleryplus-btn <?php echo $is_paged ? 'active' : ''; ?>" title="<?php echo LANG_PAGE ?? 'Pages'; ?>">&#9776;1</a>
            </div>
        </div>
    </div>

    <?php if (!empty($show_tag_filter)) {
        $active_tag_names = array_map(function($t) { return $t['tag']; }, $active_tags);
        $tag_filter_placeholder = defined('LANG_GALLERYPLUS_TAG_FILTER') ? LANG_GALLERYPLUS_TAG_FILTER : '# сортировка по тегам';
        $tag_filter_add_title = defined('LANG_GALLERYPLUS_TAG_FILTER_ADD') ? LANG_GALLERYPLUS_TAG_FILTER_ADD : 'Добавить тег';
        $tag_filter_remove_title = defined('LANG_GALLERYPLUS_TAG_FILTER_REMOVE') ? LANG_GALLERYPLUS_TAG_FILTER_REMOVE : 'Убрать тег';
        $tag_filter_reset = defined('LANG_GALLERYPLUS_TAG_FILTER_RESET') ? LANG_GALLERYPLUS_TAG_FILTER_RESET : 'Сбросить';
        $tag_filter_all = implode(',', $active_tag_names);
    ?>
    <div class="galleryplus-tagfilter" id="galleryplus-tagfilter"
         data-autocomplete="<?php echo href_to('tags', 'autocomplete'); ?>"
         data-base="<?php echo htmlspecialchars($tag_filter_base); ?>"
         data-tags="<?php echo htmlspecialchars(implode(',', $active_tag_names)); ?>"
         data-placeholder="<?php echo htmlspecialchars($tag_filter_placeholder); ?>"
         data-add-title="<?php echo htmlspecialchars($tag_filter_add_title); ?>"
         data-remove-title="<?php echo htmlspecialchars($tag_filter_remove_title); ?>"
         data-reset-title="<?php echo htmlspecialchars($tag_filter_reset); ?>">

        <div class="galleryplus-tagfilter-chips">
            <?php foreach ($active_tags as $tag) { ?>
                <span class="galleryplus-tagfilter-chip" data-tag="<?php echo htmlspecialchars($tag['tag']); ?>">
                    <span class="galleryplus-tagfilter-chip-name">#<?php echo htmlspecialchars($tag['tag']); ?></span>
                    <button type="button" class="galleryplus-tagfilter-chip-remove" data-tag="<?php echo htmlspecialchars($tag['tag']); ?>" title="<?php echo htmlspecialchars($tag_filter_remove_title); ?>">&times;</button>
                </span>
            <?php } ?>
            <?php if ($active_tags) { ?>
                <button type="button" class="galleryplus-tagfilter-add" id="galleryplus-tagfilter-add" title="<?php echo htmlspecialchars($tag_filter_add_title); ?>">+</button>
            <?php } ?>
        </div>

        <div class="galleryplus-tagfilter-field">
            <input type="text" id="galleryplus-tagfilter-input" class="galleryplus-tagfilter-input" autocomplete="off" placeholder="<?php echo htmlspecialchars($tag_filter_placeholder); ?>">
            <div class="galleryplus-tagfilter-suggest" id="galleryplus-tagfilter-suggest"></div>
        </div>

        <?php if ($active_tags) { ?>
            <a class="galleryplus-tagfilter-reset" href="<?php echo $tag_filter_base; ?>"><?php echo htmlspecialchars($tag_filter_reset); ?></a>
        <?php } ?>
    </div>
    <?php } ?>

    <?php if ($can_select) { ?>
        <div class="galleryplus-selection-bar" id="galleryplus-selection-bar" style="display:none">
            <label class="galleryplus-select-all-label">
                <input type="checkbox" id="galleryplus-select-all">
                <span class="galleryplus-checkbox"></span>
                <span><?php echo LANG_GALLERYPLUS_SELECT_ALL ?? 'Select all'; ?></span>
            </label>
            <span class="galleryplus-selection-count" id="galleryplus-selection-count"></span>
            <button class="galleryplus-btn galleryplus-delete-btn" id="galleryplus-delete-btn" style="display:none">
                &#128465; <?php echo LANG_GALLERYPLUS_DELETE ?? 'Delete'; ?>
            </button>
        </div>
    <?php } ?>

    <?php if ($is_albums) { ?>

        <div class="galleryplus-albums-grid" id="galleryplus-albums-grid">
            <?php if (!empty($albums)) { ?>
                <?php foreach ($albums as $a) {
                    $is_adult_album = ($a['privacy'] ?? '') === 'adult';
                    $show_blur = $is_adult_album && empty($a['can_view_adult']); ?>
                    <a href="<?php echo $a['url']; ?>" class="galleryplus-album-card<?php echo $show_blur ? ' galleryplus-album-card--adult' : ''; ?>" data-album-id="<?php echo $a['id']; ?>">
                        <?php if ($can_select) { ?>
                            <label class="galleryplus-checkbox-wrap galleryplus-checkbox-wrap--album">
                                <input type="checkbox" class="galleryplus-select-cb galleryplus-select-cb--album" data-id="<?php echo $a['id']; ?>">
                                <span class="galleryplus-checkbox"></span>
                            </label>
                        <?php } ?>
                        <div class="galleryplus-album-cover">
                            <?php if ($show_blur) { ?>
                                <img src="<?php echo $a['cover_url'] ?: ''; ?>" alt="" loading="lazy" class="galleryplus-blurred" data-width="<?php echo $a['cover_width'] ?? 0; ?>" data-height="<?php echo $a['cover_height'] ?? 0; ?>" style="<?php echo $a['cover_url'] ? '' : 'display:none;'; ?>">
                                <div class="galleryplus-adult-badge">18+</div>
                                <?php if (!$a['cover_url']) { ?>
                                    <div class="galleryplus-album-cover-empty" style="position:relative;z-index:1;"><?php echo LANG_GALLERYPLUS_NO_PHOTOS ?? 'No photos'; ?></div>
                                <?php } ?>
                            <?php } elseif ($a['cover_url']) { ?>
                                <img src="<?php echo $a['cover_url']; ?>" alt="<?php html($a['title']); ?>" loading="lazy" data-width="<?php echo $a['cover_width'] ?? 0; ?>" data-height="<?php echo $a['cover_height'] ?? 0; ?>">
                            <?php } else { ?>
                                <div class="galleryplus-album-cover-empty"><?php echo LANG_GALLERYPLUS_NO_PHOTOS ?? 'No photos'; ?></div>
                            <?php } ?>
                            <div class="galleryplus-album-info">
                                <span class="galleryplus-album-title"><?php html($a['title']); ?></span>
                                <?php if ($show_blur) { ?>
                                    <span class="galleryplus-album-adult-label">18+</span>
                                <?php } ?>
                                <span class="galleryplus-album-count"><?php echo $a['photo_count']; ?> <?php echo LANG_GALLERYPLUS_PHOTOS ?? 'photos'; ?></span>
                                <span class="galleryplus-album-likes">&#10084; <?php echo $a['likes_count'] ?? 0; ?></span>
                                <?php if (!empty($a['user'])) { ?>
                                    <span class="galleryplus-album-user"><?php echo htmlspecialchars($a['user']['nickname'] ?? ''); ?></span>
                                <?php } ?>
                                <?php if (!empty($use_album_tags) && !empty($a['tags'])) { ?>
                                    <div class="galleryplus-album-tags">
                                        <?php foreach ($a['tags'] as $tag) { ?>
                                            <span class="galleryplus-tag-link"><?php echo htmlspecialchars(is_array($tag) ? ($tag['title'] ?? $tag['name'] ?? '') : $tag); ?></span>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </a>
                <?php } ?>
            <?php } else { ?>
                <div class="galleryplus-empty"><?php echo defined('LANG_GALLERYPLUS_EMPTY') ? LANG_GALLERYPLUS_EMPTY : 'No images yet.'; ?></div>
            <?php } ?>
        </div>

        <?php if ($total > $perpage) { ?>
            <div class="galleryplus-pagination">
                <?php echo html_pagebar($page, $perpage, $total, href_to('galleryplus'), array_merge(['mode' => 'albums'], $current_category ? ['category' => $current_category['slug']] : [])); ?>
            </div>
        <?php } ?>

    <?php } else { ?>

        <div class="galleryplus-grid" id="galleryplus-grid" data-page="<?php echo $page + 1; ?>" data-has-next="<?php echo $has_next ? '1' : '0'; ?>" data-mode="<?php echo $mode; ?>" data-is-guest="<?php echo !empty($is_guest) ? '1' : '0'; ?>" data-login-url="<?php echo href_to('auth', 'login'); ?>">
            <?php if ($photos) { ?>
                <?php foreach ($photos as $photo) {
                    $title = htmlspecialchars($photo['title'] ?: ($photo['filename'] ?? ''));
                    $author = htmlspecialchars($photo['user']['nickname'] ?? '');
                    $avatar = $photo['user']['avatar'] ?? '';
                    $is_adult = !empty($photo['is_adult']);
                    $likes_count = $photo['likes_count'] ?? 0;
                    $comments_count = $photo['comments'] ?? 0;
                    $is_liked = !empty($photo['is_liked']);
                    $is_favorite = !empty($photo['is_favorite']);
                    $obj = htmlspecialchars(json_encode([
                        'id'       => $photo['id'],
                        'url'      => $photo['url'],
                        'src'      => $photo['url_big'],
                        'nocrop'   => $photo['url_nocrop'] ?: '',
                        'thumb'    => $photo['url_thumb'],
                        'title'    => $title,
                        'author'   => $author,
                        'avatar'   => $avatar,
                        'adult'    => $is_adult,
                        'likes'    => $likes_count,
                        'liked'    => $is_liked,
                        'favorite' => $is_favorite,
                        'owner_id' => $photo['user_id'],
                        'comments' => $comments_count,
                        'desc'     => $photo['content'] ?? '',
                    ], JSON_UNESCAPED_UNICODE));
                ?>
                    <div class="galleryplus-item<?php echo $is_adult ? ' galleryplus-item--adult' : ''; ?>" data-object="<?php echo $obj; ?>">
                        <button class="galleryplus-fav-btn galleryplus-fav-btn--card<?php echo $is_favorite ? ' favorited' : ''; ?>" data-photo-id="<?php echo $photo['id']; ?>" title="<?php echo defined('LANG_GALLERYPLUS_FAVORITE') ? LANG_GALLERYPLUS_FAVORITE : 'В избранное'; ?>"><?php echo $is_favorite ? '&#9733;' : '&#9734;'; ?></button>
                        <?php if ($can_select) { ?>
                            <label class="galleryplus-checkbox-wrap">
                                <input type="checkbox" class="galleryplus-select-cb" data-id="<?php echo $photo['id']; ?>">
                                <span class="galleryplus-checkbox"></span>
                            </label>
                        <?php } ?>
                        <a href="<?php echo $photo['url']; ?>" class="galleryplus-viewer-link">
                            <img src="<?php echo $photo['url_thumb']; ?>" alt="<?php echo $title; ?>" loading="lazy" width="<?php echo $photo['width'] ?? 0; ?>" height="<?php echo $photo['height'] ?? 0; ?>" class="<?php echo $is_adult ? 'galleryplus-blurred' : ''; ?>">
                            <?php if ($is_adult) { ?><div class="galleryplus-adult-badge">18+</div><?php } ?>
                        </a>
                        <div class="galleryplus-item-overlay">
                            <a href="<?php echo $photo['url']; ?>" class="galleryplus-item-overlay-title"><?php echo $title; ?></a>
                            <div class="galleryplus-item-overlay-bottom">
                                <a href="<?php echo href_to('users', $photo['user']['id']); ?>" class="galleryplus-item-author"><?php echo $author; ?></a>
                                <div class="galleryplus-item-overlay-stats">
                                    <span class="galleryplus-item-likes">&#10084; <?php echo $likes_count; ?></span>
                                    <span class="galleryplus-item-comments">&#9993; <?php echo $comments_count; ?></span>
</div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="galleryplus-empty"><?php if (!empty($active_tags)) { ?>
                    <span class="galleryplus-empty-tagfilter"><?php echo defined('LANG_GALLERYPLUS_TAG_FILTER_EMPTY') ? LANG_GALLERYPLUS_TAG_FILTER_EMPTY : 'Ничего не найдено по этим тегам'; ?></span>
                    <a class="galleryplus-tagfilter-reset" href="<?php echo $tag_filter_base; ?>"><?php echo defined('LANG_GALLERYPLUS_TAG_FILTER_RESET') ? LANG_GALLERYPLUS_TAG_FILTER_RESET : 'Сбросить'; ?></a>
                <?php } else { ?>
                    <?php echo defined('LANG_GALLERYPLUS_EMPTY') ? LANG_GALLERYPLUS_EMPTY : 'No images yet.'; ?>
                <?php } ?></div>
            <?php } ?>
        </div>

        <?php if ($has_next && $is_infinite) { ?>
            <div class="galleryplus-loading" id="galleryplus-loading">
                <div class="galleryplus-spinner"></div>
            </div>
        <?php } ?>

        <?php if ($is_paged && $total > $perpage) { ?>
            <div class="galleryplus-pagination" id="galleryplus-pagination">
                <?php echo html_pagebar($page, $perpage, $total, href_to('galleryplus'), array_merge(['mode' => 'paged'], $current_category ? ['category' => $current_category['slug']] : [], !empty($tag_filter_all) ? ['tags' => $tag_filter_all] : [])); ?>
            </div>
        <?php } ?>

    <?php } ?>
</div>

<?php $this->renderChild('viewer', [
    'show_lightbox_desc'     => !empty($show_lightbox_desc),
    'truncate_lightbox_desc' => !empty($truncate_lightbox_desc),
    'lightbox_desc_limit'    => $lightbox_desc_limit,
    'user'                   => $user,
]); ?>

<?php if ($is_infinite) { ?>
<script>
(function() {
    var loading = false;
    var grid = document.getElementById('galleryplus-grid');
    if (!grid) return;
    var page = parseInt(grid.dataset.page) || 2;
    var hasNext = grid.dataset.hasNext === '1';
    var generation = 0;
    var pendingXhr = null;

    function loadUrl() {
        var parts = location.search.replace(/^\?/, '').split('&');
        var keep = [];
        for (var i = 0; i < parts.length; i++) {
            if (!parts[i]) continue;
            var key = parts[i].split('=')[0];
            if (key === 'page' || key === 'mode' || key === 'gp_filter') continue;
            keep.push(parts[i]);
        }
        keep.unshift('mode=infinite');
        keep.unshift('page=' + page);
        return location.pathname + '?' + keep.join('&');
    }

    function loadMore() {
        if (loading || !hasNext) return;
        loading = true;
        var gen = generation;
        var el = document.getElementById('galleryplus-loading');
        if (el) el.classList.add('active');

        var xhr = new XMLHttpRequest();
        pendingXhr = xhr;
        xhr.open('GET', loadUrl(), true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function() {
            if (gen !== generation) return;
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.html) {
                        grid.insertAdjacentHTML('beforeend', data.html);
                        page = data.page || (page + 1);
                        hasNext = data.has_next || false;
                        grid.dataset.page = page;
                        grid.dataset.hasNext = hasNext ? '1' : '0';
                        if (typeof galleryplusMasonry === 'function') galleryplusMasonry(grid);
                        window.dispatchEvent(new CustomEvent('galleryplus:more-loaded'));
                    }
                } catch(e) {}
            }
            loading = false;
            if (el) el.classList.remove('active');
            if (!hasNext && el) el.style.display = 'none';
        };
        xhr.onerror = function() { loading = false; if (el) el.classList.remove('active'); };
        xhr.send();
    }

    var sentinel = document.createElement('div');
    sentinel.style.height = '1px';
    grid.parentNode.insertBefore(sentinel, grid.nextSibling);

    var observer = new IntersectionObserver(function(entries) {
        if (entries[0].isIntersecting) loadMore();
    }, { rootMargin: '200px' });
    observer.observe(sentinel);

    window.galleryplusLoadMore = loadMore;

    window.addEventListener('galleryplus:filter-applied', function(e) {
        var d = e.detail || {};
        generation++;
        loading = false;
        if (pendingXhr) { pendingXhr.onload = null; pendingXhr.onerror = null; pendingXhr.abort(); pendingXhr = null; }
        page = parseInt(d.page) || 2;
        hasNext = d.has_next === true || d.has_next === 1;
        grid.dataset.page = page;
        grid.dataset.hasNext = hasNext ? '1' : '0';
        var el = document.getElementById('galleryplus-loading');
        if (el) {
            el.classList.remove('active');
            el.style.display = hasNext ? '' : 'none';
        }
    });

    window.addEventListener('popstate', function(e) {
        if (window.__gpViewerOpen || window.__gpViewerClosing) return;
        if (e.state && e.state.galleryplusTags) {
            window.location.reload();
        }
    });
})();
</script>
<?php } ?>

<?php if ($can_select) { ?>
<script>
(function() {
    var bar = document.getElementById('galleryplus-selection-bar');
    if (!bar) return;
    var selectAll = document.getElementById('galleryplus-select-all');
    var countEl = document.getElementById('galleryplus-selection-count');
    var deleteBtn = document.getElementById('galleryplus-delete-btn');

    function getChecked() { return document.querySelectorAll('.galleryplus-select-cb:checked'); }

    function updateBar() {
        var n = getChecked().length;
        if (n === 0) { bar.style.display = 'none'; document.body.classList.remove('galleryplus-selecting'); return; }
        bar.style.display = '';
        document.body.classList.add('galleryplus-selecting');
        countEl.textContent = n;
        deleteBtn.style.display = n > 0 ? '' : 'none';
    }

    bar.addEventListener('change', function(e) {
        if (e.target === selectAll) {
            var cbs = document.querySelectorAll('.galleryplus-select-cb');
            for (var i = 0; i < cbs.length; i++) cbs[i].checked = selectAll.checked;
        }
        updateBar();
    });

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('galleryplus-select-cb')) updateBar();
    });

    deleteBtn.addEventListener('click', function() {
        var cbs = getChecked();
        if (!cbs.length) return;

        var albumCbs = document.querySelectorAll('.galleryplus-select-cb--album:checked');
        var photoCbs = document.querySelectorAll('.galleryplus-select-cb:not(.galleryplus-select-cb--album):checked');

        if (photoCbs.length && albumCbs.length) {
            alert('<?php echo addslashes("Нельзя удалять фото и альбомы одновременно."); ?>');
            return;
        }

        if (albumCbs.length) {
            deleteAlbums(albumCbs);
        } else if (photoCbs.length) {
            deletePhotos(photoCbs);
        }
    });

    function deleteAlbums(cbs) {
        if (!confirm('<?php echo addslashes(LANG_GALLERYPLUS_CONFIRM_DELETE_ALBUMS ?? "Удалить выбранные альбомы?"); ?>')) return;
        var ids = [];
        for (var i = 0; i < cbs.length; i++) ids.push(parseInt(cbs[i].getAttribute('data-id')));
        deleteBtn.disabled = true;
        var fd = new FormData();
        fd.append('action', 'delete_albums');
        fd.append('ids', ids.join(','));
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo href_to('galleryplus', 'save'); ?>', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function() {
            deleteBtn.disabled = false;
            if (xhr.status === 200) {
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r.success) {
                        for (var i = 0; i < cbs.length; i++) {
                            var card = cbs[i].closest('.galleryplus-album-card');
                            if (card) card.remove();
                        }
                        selectAll.checked = false;
                        updateBar();
                    }
                } catch(e) {}
            }
        };
        xhr.send(fd);
    }

    function deletePhotos(cbs) {
        if (!confirm('<?php echo addslashes(LANG_GALLERYPLUS_CONFIRM_DELETE ?? "Delete selected photos?"); ?>')) return;
        var ids = [];
        for (var i = 0; i < cbs.length; i++) ids.push(parseInt(cbs[i].getAttribute('data-id')));
        deleteBtn.disabled = true;
        var fd = new FormData();
        fd.append('action', 'delete_photos');
        fd.append('ids', ids.join(','));
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo href_to('galleryplus', 'save'); ?>', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function() {
            deleteBtn.disabled = false;
            if (xhr.status === 200) {
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r.success) {
                        for (var i = 0; i < cbs.length; i++) {
                            var item = cbs[i].closest('.galleryplus-item');
                            if (item) item.remove();
                        }
                        selectAll.checked = false;
                        updateBar();
                        if (typeof galleryplusMasonry === 'function') galleryplusMasonry(document.getElementById('galleryplus-grid'));
                    }
                } catch(e) {}
            }
        };
        xhr.send(fd);
    }
})();
</script>
<?php } ?>
