<?php
    $this->addTplCSSName('galleryplus');
    $this->addTplJSName('galleryplus');
    $this->addTplJSName('jquery-cookie');
    $this->setPageTitle($album['title']);
    $this->setPageDescription($album['content'] ?? '');
?>

<div class="galleryplus album-view">
    <div class="galleryplus-header">
        <div>
            <h1><?php html($album['title']); ?></h1>
            <?php if (!empty($album['content'])) { ?>
                <p class="text-muted galleryplus-album-description"><?php echo nl2br(htmlspecialchars($album['content'])); ?></p>
            <?php } ?>
            <div class="text-muted small galleryplus-album-meta">
                <?php if (!empty($album['user'])) { ?>
                    <a href="<?php echo href_to('users', $album['user']['id']); ?>" class="galleryplus-album-user">
                        <?php echo htmlspecialchars($album['user']['nickname'] ?? ''); ?>
                    </a>
                    &middot;
                <?php } ?>
                <?php echo $album['photo_count'] ?? 0; ?> <?php echo LANG_GALLERYPLUS_PHOTOS ?? 'photos'; ?>
                <?php if (!empty($category)) { ?>
                    &middot; <?php echo LANG_GALLERYPLUS_CATEGORY ?? 'Категория'; ?>: <a href="<?php echo $category['url']; ?>"><?php echo htmlspecialchars($category['title']); ?></a>
                <?php } ?>
            </div>
            <?php if (!empty($album_tags)) { ?>
                <div class="tags_bar mt-3">
                    <?php echo html_tags_bar($album_tags, 'galleryplus-album', 'btn btn-outline-secondary btn-sm icms-btn-tag', ''); ?>
                </div>
            <?php } ?>
        </div>
        <div class="galleryplus-toolbar">
            <?php if (!empty($can_upload)) { ?>
                <a href="<?php echo href_to('galleryplus', 'upload') . '?album_id=' . $album['id']; ?>" class="galleryplus-btn galleryplus-upload-btn">+ <?php echo LANG_GALLERYPLUS_UPLOAD ?? 'Upload'; ?></a>
            <?php } ?>
            <?php if ($user->is_logged && ($is_owner || $user->is_admin || !empty($can_select))) { ?>
                <a href="<?php echo href_to('galleryplus', 'album', ['edit', $album['slug']]) . '.html'; ?>" class="galleryplus-btn">&#9998; <?php echo LANG_GALLERYPLUS_ALBUM_EDIT; ?></a>
            <?php } ?>
        </div>
    </div>

    <?php if (!empty($locked)) { ?>
        <div class="galleryplus-album-locked">
            <div class="galleryplus-album-locked-icon">&#128274;</div>
            <h2><?php echo LANG_GALLERYPLUS_ALBUM_LOCKED; ?></h2>
            <p><?php echo LANG_GALLERYPLUS_ALBUM_LOCKED_HINT; ?></p>
            <?php if ($album['privacy'] === 'password') { ?>
                <form action="<?php echo href_to('galleryplus', 'album', [$album['slug']]) . '.html'; ?>" method="post" class="galleryplus-album-password-form">
                    <input type="hidden" name="csrf_token" value="<?php echo cmsForm::getCSRFToken(); ?>">
                    <input type="password" name="album_password" class="form-control" placeholder="<?php echo LANG_GALLERYPLUS_ALBUM_PASSWORD; ?>" required>
                    <button type="submit" class="btn btn-primary"><?php echo LANG_SUBMIT; ?></button>
                </form>
            <?php } else { ?>
                <p><a href="<?php echo href_to('auth', 'login'); ?>"><?php echo LANG_LOG_IN; ?></a></p>
            <?php } ?>
        </div>
    <?php } else { ?>

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

        <div class="galleryplus-grid<?php echo (!empty($album['privacy']) && $album['privacy'] !== 'public' && empty($is_owner)) ? ' galleryplus-grid-blurred' : ''; ?>" id="galleryplus-grid" data-page="<?php echo $page + 1; ?>" data-has-next="<?php echo $has_next ? '1' : '0'; ?>" data-url="<?php echo href_to('galleryplus', 'album', [$album['slug']]) . '.html'; ?>" data-is-guest="<?php echo !$user->id ? '1' : '0'; ?>" data-login-url="<?php echo href_to('auth', 'login'); ?>">
            <?php foreach ($photos as $photo) {
                $title = htmlspecialchars($photo['title'] ?: ($photo['filename'] ?? ''));
                $author = htmlspecialchars($photo['user']['nickname'] ?? '');
                $avatar = $photo['user']['avatar'] ?? '';
                $is_adult = !empty($photo['is_adult']);
                $likes_count = $photo['likes_count'] ?? 0;
                $comments_count = $photo['comments'] ?? 0;
                $is_liked = !empty($photo['is_liked']);
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
                    'owner_id' => $photo['user_id'],
                    'comments' => $comments_count,
                    'desc'     => $photo['content'] ?? '',
                ], JSON_UNESCAPED_UNICODE));
            ?>
                <div class="galleryplus-item<?php echo $is_adult ? ' galleryplus-item--adult' : ''; ?>" data-object="<?php echo $obj; ?>">
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
        </div>

        <?php if ($has_next) { ?>
            <div class="galleryplus-loading" id="galleryplus-loading">
                <div class="galleryplus-spinner"></div>
            </div>
        <?php } ?>

    <?php } ?>
</div>

<?php if (!empty($comments_widget)) { ?>
    <div class="galleryplus-comments-block">
        <?php echo $comments_widget; ?>
    </div>
<?php } ?>

<div id="galleryplus-viewer" class="galleryplus-viewer galleryplus-viewer--hide" data-cover="1" data-show-desc="<?php echo !empty($show_lightbox_desc) ? '1' : '0'; ?>" data-current-user="<?php echo $user->id; ?>">
    <div class="galleryplus-viewer-bg"></div>

    <div class="galleryplus-viewer-top">
        <div class="galleryplus-viewer-top-left"></div>
        <div class="galleryplus-viewer-top-right">
            <button class="galleryplus-viewer-close" title="<?php echo defined('LANG_CLOSE') ? LANG_CLOSE : 'Close'; ?>">&times;</button>
        </div>
    </div>

    <div class="galleryplus-viewer-content">
        <img src="" alt="" class="galleryplus-viewer-img">
    </div>

    <a href="" class="galleryplus-viewer-title" target="_blank">
        <svg class="galleryplus-viewer-title-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="3"/><path d="M8 8h8v8m-4-4l8-8"/></svg>
        <span class="galleryplus-viewer-title-text"></span>
    </a>

    <div class="galleryplus-viewer-desc"></div>

    <button class="galleryplus-viewer-nav galleryplus-viewer-prev" title="<?php echo LANG_GALLERYPLUS_PREV ?? 'Previous'; ?>"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg></button>
    <button class="galleryplus-viewer-nav galleryplus-viewer-next" title="<?php echo LANG_GALLERYPLUS_NEXT ?? 'Next'; ?>"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button>

    <div class="galleryplus-viewer-bottom">
        <div class="galleryplus-viewer-bottom-left">
            <img src="" class="galleryplus-viewer-avatar" alt="">
            <span class="galleryplus-viewer-author"></span>
        </div>
        <div class="galleryplus-viewer-bottom-right">
            <button class="galleryplus-viewer-like" data-target-id="" data-target-type="photo" title="<?php echo LANG_GALLERYPLUS_LIKE ?? 'Like'; ?>"><span class="galleryplus-viewer-like-icon">&#9825;</span> <span class="galleryplus-viewer-like-count">0</span></button>
            <button class="galleryplus-viewer-comments" title="<?php echo LANG_GALLERYPLUS_COMMENTS ?? 'Comments'; ?>"><span class="galleryplus-viewer-comments-icon">&#9993;</span> <span class="galleryplus-viewer-comments-count">0</span></button>
            <button class="galleryplus-viewer-share" title="<?php echo LANG_GALLERYPLUS_SHARE ?? 'Поделиться'; ?>"><svg class="galleryplus-share-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg></button>
        </div>
    </div>

    <div class="galleryplus-viewer-share-popup">
        <div class="galleryplus-viewer-share-popup-arrow"></div>
        <a class="galleryplus-viewer-share-option" href="#" target="_blank" data-share="pinterest"><svg viewBox="0 0 24 24" width="20" height="20" fill="#e60023"><path d="M12 0C5.373 0 0 5.373 0 12c0 5.084 3.163 9.426 7.627 11.174-.105-.949-.2-2.405.042-3.441.218-.936 1.407-5.965 1.407-5.965s-.359-.719-.359-1.782c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738.098.119.112.224.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.632-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg> Pinterest</a>
        <a class="galleryplus-viewer-share-option" href="#" target="_blank" data-share="vk"><svg viewBox="0 0 24 24" width="20" height="20" fill="#4a76a8"><path d="M15.684 0H8.316C2.755 0 0 2.755 0 8.316v7.368C0 21.245 2.755 24 8.316 24h7.368C21.245 24 24 21.245 24 15.684V8.316C24 2.755 21.245 0 15.684 0zm3.279 16.482h-1.967c-.606 0-.788-.454-.788-.454-1.333-1.667-3.636-3.242-3.636-3.242.06-.03.364-.5.364-.5s1.818-2.879 2.424-4.242c.303-.697.03-.97-.788-.97h-1.97c-.424 0-.666.212-.818.515-.152.364-1.151 2.545-1.151 2.545s-.091.152-.242.152c-.03 0-.152-.061-.152-.061s-1.03-1.243-1.697-2.03c-.212-.272-.545-.485-1.03-.485H7.393c-.424 0-.666.273-.666.515 0 .091.03.181.121.303 1.151 1.818 3.03 3.636 3.03 3.636s.03 0 .03.03c.03 0 .03.03 0 .061-.03 0-.03 0-.03.03-1.03.606-2.06 1.212-2.06 1.212-.212.152-.394.364-.394.788 0 .424.303.788.788.788h1.97c.424 0 .666-.212.666-.212s1.394-.97 1.394-1.09c.03-.03.121-.03.182 0 .03 0 .121.091.121.151 0 .03-.03.061-.03.061s-1.03 1.03-1.515 1.515c-.212.212-.121.364.091.364h.03c.818-.03 1.878-.03 1.878-.03s.576-.03.848.303c.182.212.182.576.182.576s.03.364.121.545c.091.212.333.242.515.242h1.212c.364 0 .606-.182.788-.424.182-.242.182-.606.182-.606s-.03-.97.515-1.151c.545-.182 1.333.97 2.121 1.394.606.333.97.242.97.242l1.636-.03c.333 0 .515-.182.454-.545-.061-.364-.666-1.03-1.151-1.515-.212-.212-.424-.454-.424-.545 0-.03.03-.121.03-.121s1.03-1.454 1.272-2.06c.03-.091.091-.212.091-.394.03-.212-.121-.394-.394-.394z"/></svg> VK</a>
        <a class="galleryplus-viewer-share-option" href="#" target="_blank" data-share="telegram"><svg viewBox="0 0 24 24" width="20" height="20" fill="#0088cc"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.127.087.496.087.496l-1.597 7.53s-.107.44-.607.44a.87.87 0 0 1-.473-.176l-3.148-2.386-1.174 1.09c-.257.227-.436.036-.436.036l.665-3.07s3.365-3.04 3.49-3.15c.125-.11.083-.177.083-.177.005-.112-.173 0-.173 0l-6.46 4.16-1.36-.454s-.497-.176-.548-.48c-.05-.306.46-.47.46-.47l12.232-4.747s.412-.148.412-.14z"/></svg> Telegram</a>
        <button type="button" class="galleryplus-viewer-share-option galleryplus-viewer-copy-link" data-share="copy"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg> <span><?php echo LANG_GALLERYPLUS_COPY_LINK ?? 'Copy link'; ?></span></button>
    </div>

    <div class="galleryplus-viewer-comments-overlay">
        <div class="galleryplus-viewer-comments-panel">
            <div class="galleryplus-viewer-comments-header">
                <span><?php echo LANG_GALLERYPLUS_COMMENTS ?? 'Comments'; ?></span>
                <button class="galleryplus-viewer-comments-close">&times;</button>
            </div>
            <div class="galleryplus-viewer-comments-body">
                <div class="galleryplus-viewer-comments-loading"><?php echo LANG_LOADING ?? 'Loading...'; ?></div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var loading = false;
    var grid = document.getElementById('galleryplus-grid');
    if (!grid) return;
    var page = parseInt(grid.dataset.page) || 2;
    var hasNext = grid.dataset.hasNext === '1';
    var baseUrl = grid.dataset.url;
    var el = document.getElementById('galleryplus-loading');

    function loadMore() {
        if (loading || !hasNext) return;
        loading = true;
        if (el) el.classList.add('active');

        var xhr = new XMLHttpRequest();
        xhr.open('GET', baseUrl + '?page=' + page, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function() {
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
})();
</script>

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
        var grid = document.getElementById('galleryplus-grid');
        if (n === 0) { bar.style.display = 'none'; if (grid) grid.classList.remove('galleryplus-selecting'); return; }
        bar.style.display = '';
        if (grid) grid.classList.add('galleryplus-selecting');
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

    var grid = document.getElementById('galleryplus-grid');
    if (grid) grid.addEventListener('change', function(e) {
        if (e.target.classList.contains('galleryplus-select-cb')) updateBar();
    });

    deleteBtn.addEventListener('click', function() {
        var cbs = getChecked();
        if (!cbs.length) return;
        if (!confirm('<?php echo addslashes(LANG_GALLERYPLUS_CONFIRM_DELETE ?? "Delete selected photos?"); ?>')) return;
        var ids = [];
        for (var i = 0; i < cbs.length; i++) ids.push(parseInt(cbs[i].getAttribute('data-id')));
        deleteBtn.disabled = true;
        var fd = new FormData();
        fd.append('action', 'delete_photos');
        fd.append('ids', ids.join(','));
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/galleryplus/save', true);
        xhr.onload = function() {
            deleteBtn.disabled = false;
            if (xhr.status === 200) {
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
            }
        };
        xhr.send(fd);
    });
})();
</script>
<?php } ?>
