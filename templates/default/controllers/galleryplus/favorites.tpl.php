<?php
    $this->addTplCSSName('galleryplus');
    $this->addTplJSName('galleryplus');
    $this->setPageTitle(defined('LANG_GALLERYPLUS_FAVORITES') ? LANG_GALLERYPLUS_FAVORITES : 'Избранное');
?>

<div class="galleryplus favorites-view">
    <div class="galleryplus-header">
        <div>
            <h1><?php echo defined('LANG_GALLERYPLUS_FAVORITES') ? LANG_GALLERYPLUS_FAVORITES : 'Избранное'; ?></h1>
            <div class="text-muted small galleryplus-album-meta">
                <?php echo $total; ?> <?php echo defined('LANG_GALLERYPLUS_PHOTOS') ? LANG_GALLERYPLUS_PHOTOS : 'photos'; ?>
            </div>
        </div>
    </div>

    <?php if (empty($photos)) { ?>
        <div class="galleryplus-empty"><?php echo defined('LANG_GALLERYPLUS_NO_FAVORITES') ? LANG_GALLERYPLUS_NO_FAVORITES : 'В избранном пока пусто.'; ?></div>
    <?php } else { ?>
        <div class="galleryplus-grid" id="galleryplus-grid" data-page="<?php echo $page + 1; ?>" data-has-next="<?php echo $has_next ? '1' : '0'; ?>" data-url="<?php echo href_to('galleryplus', 'favorites'); ?>" data-is-guest="<?php echo !$user->id ? '1' : '0'; ?>" data-login-url="<?php echo href_to('auth', 'login'); ?>">
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
        <span class="galleryplus-viewer-title-text"></span>
    </a>
    <div class="galleryplus-viewer-desc"></div>
    <button class="galleryplus-viewer-nav galleryplus-viewer-prev" title="<?php echo LANG_GALLERYPLUS_PREV ?? 'Previous'; ?>">&#8592;</button>
    <button class="galleryplus-viewer-nav galleryplus-viewer-next" title="<?php echo LANG_GALLERYPLUS_NEXT ?? 'Next'; ?>">&#8594;</button>
    <div class="galleryplus-viewer-bottom">
        <div class="galleryplus-viewer-bottom-left">
            <img src="" class="galleryplus-viewer-avatar" alt="">
            <span class="galleryplus-viewer-author"></span>
        </div>
        <div class="galleryplus-viewer-bottom-right">
            <button class="galleryplus-viewer-like" data-target-id="" data-target-type="photo" title="<?php echo LANG_GALLERYPLUS_LIKE ?? 'Like'; ?>"><span class="galleryplus-viewer-like-icon">&#9825;</span> <span class="galleryplus-viewer-like-count">0</span></button>
            <button class="galleryplus-viewer-fav" title="<?php echo defined('LANG_GALLERYPLUS_FAVORITE') ? LANG_GALLERYPLUS_FAVORITE : 'В избранное'; ?>">&#9734;</button>
            <button class="galleryplus-viewer-comments" title="<?php echo LANG_GALLERYPLUS_COMMENTS ?? 'Comments'; ?>"><span class="galleryplus-viewer-comments-icon">&#9993;</span> <span class="galleryplus-viewer-comments-count">0</span></button>
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
