<?php
    $this->addTplCSSName('galleryplus');
    $this->addTplJSName('galleryplus');
    $this->setPageTitle($photo['title']);
    $this->setPageDescription($photo['content'] ?: $photo['title']);

    $photo_url  = $photo['url'] ?? '';
    $photo_img  = $is_paid_blurred ? ($photo['url_thumb'] ?? '') : (($photo['url_big'] ?? $photo['url_thumb'] ?? ''));
    $photo_desc = $photo['content'] ?: ($photo['title'] ?? '');

    $this->addHead('<meta property="og:type" content="article">');
    $this->addHead('<meta property="og:title" content="' . htmlspecialchars($photo['title'] ?? '') . '">');
    $this->addHead('<meta property="og:description" content="' . htmlspecialchars($photo_desc) . '">');
    $this->addHead('<meta property="og:image" content="' . htmlspecialchars($photo_img) . '">');
    $this->addHead('<meta property="og:url" content="' . htmlspecialchars($photo_url) . '">');
    $this->addHead('<meta property="og:site_name" content="' . htmlspecialchars($this->site_config->sitename) . '">');
    $this->addHead('<meta name="twitter:card" content="summary_large_image">');
    $this->addHead('<meta name="twitter:title" content="' . htmlspecialchars($photo['title'] ?? '') . '">');
    $this->addHead('<meta name="twitter:description" content="' . htmlspecialchars($photo_desc) . '">');
    $this->addHead('<meta name="twitter:image" content="' . htmlspecialchars($photo_img) . '">');

    if ($gps_lat !== null && $gps_lon !== null && empty($hide_map)) {
        $this->addHead('<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">');
        $this->addHead('<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>');
    }

    $has_embed = !empty($photo['url_original']) || !empty($photo['url_big']) || !empty($photo['url_thumb']);
    $has_exif = !empty($photo['exif_formatted']);

?>

<div class="galleryplus-view">
    <div class="galleryplus-view-image <?php echo $is_blurred ? 'galleryplus-view-image--adult' : ($is_paid_blurred ? 'galleryplus-view-image--paid-blur' : ''); ?>">
        <img src="<?php echo $is_paid_blurred ? (($photo['url_thumb'] ?? '') ?: ($photo['url_big'] ?? '')) : (($photo['url_big'] ?? '') ?: ($photo['url_original'] ?? '')); ?>" alt="<?php html($photo['title'] ?? ''); ?>" id="galleryplus-view-img" data-nocrop="<?php echo !$can_original ? '' : htmlspecialchars($photo['url_nocrop'] ?? ''); ?>" <?php echo ($is_blurred || $is_paid_blurred) ? 'class="galleryplus-blurred"' : ''; ?>>
        <?php if ($is_blurred) { ?>
            <div class="galleryplus-adult-badge galleryplus-adult-badge--view">18+</div>
            <div class="galleryplus-view-adult-overlay">
                <p><?php echo defined('LANG_GALLERYPLUS_ADULT_LOGIN') ? LANG_GALLERYPLUS_ADULT_LOGIN : 'Войдите, чтобы посмотреть это фото'; ?></p>
                <a href="<?php echo href_to('auth', 'login'); ?>" class="button-submit"><?php echo defined('LANG_LOGIN') ? LANG_LOGIN : 'Войти'; ?></a>
            </div>
        <?php } ?>
        <?php if ($is_paid_blurred) { ?>
            <div class="galleryplus-paid-lock-icon galleryplus-paid-lock-icon--view">&#128274;</div>
            <div class="galleryplus-view-adult-overlay">
                <p><?php echo defined('LANG_GALLERYPLUS_BILLING_ALBUM_LOCKED') ? LANG_GALLERYPLUS_BILLING_ALBUM_LOCKED : 'Фото платное'; ?></p>
                <a href="<?php echo $buy_album_url; ?>" class="galleryplus-btn galleryplus-buy-btn"><?php echo defined('LANG_GALLERYPLUS_BILLING_BUY_ALBUM') ? LANG_GALLERYPLUS_BILLING_BUY_ALBUM : 'Купить просмотр'; ?> (<?php echo $album_view_price_spell; ?>)</a>
            </div>
        <?php } ?>
    </div>

    <div class="galleryplus-view-sidebar">
        <div class="galleryplus-view-nav galleryplus-view-nav--sidebar">
            <?php $has_prev = !empty($prev_photo); $has_next = !empty($next_photo); ?>
            <?php if ($has_prev || $has_next) { ?>
                <?php if ($has_prev) { ?>
                    <a href="<?php echo $prev_photo['url']; ?>" class="galleryplus-nav-btn galleryplus-nav-prev" title="<?php html($prev_photo['title']); ?>">&#8592; <?php echo LANG_GALLERYPLUS_PREV ?? 'Назад'; ?></a>
                <?php } ?>
                <?php if ($has_next) { ?>
                    <a href="<?php echo $next_photo['url']; ?>" class="galleryplus-nav-btn galleryplus-nav-next" title="<?php html($next_photo['title']); ?>"><?php echo LANG_GALLERYPLUS_NEXT ?? 'Вперед'; ?> &#8594;</a>
                <?php } ?>
            <?php } ?>
        </div>

        <?php if (!empty($photo_tags)) { ?>
            <div class="tags_bar mt-3">
                <?php echo html_tags_bar($photo_tags, 'galleryplus-photo', 'btn btn-outline-secondary btn-sm icms-btn-tag', ''); ?>
            </div>
        <?php } ?>

        <div class="galleryplus-view-meta">
            <div class="galleryplus-view-author">
                <span class="galleryplus-view-avatar"><?php echo html_avatar_image($photo['user']['avatar'] ?? '', 'micro', $photo['user']['nickname'] ?? ''); ?></span>
                <a href="<?php echo href_to_profile($photo['user']); ?>"><?php html($photo['user']['nickname'] ?? ''); ?></a>
            </div>
            <div class="galleryplus-view-stats">
                <span><?php echo $photo['width']; ?> &times; <?php echo $photo['height']; ?></span>
                <span><?php echo $photo['hits_count']; ?> <?php echo defined('LANG_GALLERYPLUS_VIEWS') ? LANG_GALLERYPLUS_VIEWS : 'просмотров'; ?></span>
                <span><?php echo date('d.m.Y', strtotime($photo['date_pub'])); ?></span>
            </div>
        </div>

        <div class="galleryplus-view-actions">
            <?php if (!empty($is_owner) || $user->is_admin) { ?>
                <a href="<?php echo href_to('galleryplus', 'edit', [$photo['id']]); ?>" class="galleryplus-action-btn galleryplus-action-btn--edit"><?php echo defined('LANG_GALLERYPLUS_EDIT') ? LANG_GALLERYPLUS_EDIT : 'Редактировать'; ?></a>
            <?php } ?>
            <?php if ($is_blurred) { ?>
                <a href="<?php echo href_to('auth', 'login'); ?>" class="button-submit galleryplus-view-login-btn"><?php echo defined('LANG_GALLERYPLUS_VIEW_FULL') ? LANG_GALLERYPLUS_VIEW_FULL : 'Войти для просмотра'; ?></a>
            <?php } elseif ($is_paid_blurred) { ?>
                <a href="<?php echo $buy_album_url; ?>" class="galleryplus-btn galleryplus-buy-btn"><?php echo defined('LANG_GALLERYPLUS_BILLING_BUY_ALBUM') ? LANG_GALLERYPLUS_BILLING_BUY_ALBUM : 'Купить просмотр'; ?> (<?php echo $album_view_price_spell; ?>)</a>
            <?php } else { ?>
                <button class="galleryplus-like-btn <?php echo $user_liked ? 'liked' : ''; ?> <?php echo (!$user->id || !empty($is_owner)) ? 'disabled' : ''; ?>" data-target-id="<?php echo $photo['id']; ?>" data-target-type="photo">
                    <span class="galleryplus-like-icon"><?php echo $user_liked ? '♥' : '♡'; ?></span>
                    <span class="galleryplus-like-count"><?php echo $likes_count; ?></span>
                </button>
                <button class="galleryplus-fav-btn galleryplus-fav-btn--view <?php echo $is_favorite ? 'favorited' : ''; ?> <?php echo !$user->id ? 'disabled' : ''; ?>" data-photo-id="<?php echo $photo['id']; ?>" title="<?php echo defined('LANG_GALLERYPLUS_FAVORITE_TOGGLE') ? LANG_GALLERYPLUS_FAVORITE_TOGGLE : 'В избранное'; ?>">
                    <?php echo $is_favorite ? '★' : '☆'; ?>
                </button>
                <?php if (!$can_original && $original_price > 0) { ?>
                <a href="<?php echo $buy_original_url; ?>" class="galleryplus-dl-btn galleryplus-dl-btn--buy"><?php echo defined('LANG_GALLERYPLUS_BUY_ORIGINAL') ? LANG_GALLERYPLUS_BUY_ORIGINAL : 'Купить оригинал'; ?> (<?php echo $original_price_spell; ?>)</a>
                <?php } else { $dl_url = $photo['url_nocrop'] ?: ($photo['url_original'] ?: ''); if ($dl_url) { ?>
                <a href="<?php echo $dl_url; ?>" class="galleryplus-dl-btn" download><?php echo defined('LANG_GALLERYPLUS_DOWNLOAD') ? LANG_GALLERYPLUS_DOWNLOAD : 'Скачать'; ?></a>
                <?php } } ?>
            <?php } ?>
        </div>

        <?php if (!empty($photo['album'])) { ?>
            <div class="galleryplus-view-album">
                <a href="<?php echo $photo['album']['url']; ?>"><?php echo LANG_GALLERYPLUS_IN_ALBUM ?? 'In album'; ?>: <?php html($photo['album']['title']); ?></a>
            </div>
        <?php } ?>
    </div>
</div>

    <?php if (!$is_blurred && !$is_paid_blurred) { ?>
    <div class="galleryplus-tabs">
        <div class="galleryplus-tabs-nav">
            <button class="active" data-tab="about"><?php echo LANG_GALLERYPLUS_TAB_ABOUT ?? 'About'; ?></button>
            <?php if ($has_embed && !empty($show_embed_codes)) { ?>
                <button data-tab="embed"><?php echo LANG_GALLERYPLUS_EMBED ?? 'Embed'; ?></button>
            <?php } ?>
            <?php if ($has_exif && empty($hide_exif)) { ?>
                <button data-tab="exif"><?php echo LANG_GALLERYPLUS_TAB_EXIF ?? 'EXIF'; ?></button>
            <?php } ?>
        </div>
    </div>

    <div class="galleryplus-tabs-content">
        <div class="galleryplus-tab-pane active" data-tab="about">
            <?php if (!empty($photo['content'])) { ?>
                <p><?php echo $photo['content']; ?></p>
            <?php } else { ?>
                <p class="text-muted"><?php echo LANG_GALLERYPLUS_NO_DESC ?? 'No description'; ?></p>
            <?php } ?>
        </div>

        <?php if ($has_embed && !empty($show_embed_codes)) { ?>
        <div class="galleryplus-tab-pane" data-tab="embed">
            <?php
                $site_host = cmsConfig::get('host');
                $to_abs = function($p) use ($site_host) {
                    if (!$p || strpos($p, '://') !== false) { return $p; }
                    return $site_host . $p;
                };
                $embed_url  = $to_abs($photo['url']);
                $embed_original = $can_original ? ($photo['url_original'] ?: ($photo['url_nocrop'] ?: '')) : '';
                $sizes = ['Original' => $embed_original, 'Full' => $photo['url_big'], 'Medium' => $photo['url_thumb']];
                $types = [
                    'HTML with link' => function($u, $i, $t) { return '<a href="' . $u . '"><img src="' . $i . '" alt="' . $t . '"></a>'; },
                    'HTML' => function($u, $i, $t) { return '<img src="' . $i . '" alt="' . $t . '">'; },
                    'BBCode with link' => function($u, $i, $t) { return '[url=' . $u . '][img]' . $i . '[/img][/url]'; },
                    'BBCode' => function($u, $i, $t) { return '[img]' . $i . '[/img]'; },
                    'Markdown' => function($u, $i, $t) { return '[![' . $t . '](' . $i . ')](' . $u . ')'; },
                ];
                $img_title = htmlspecialchars($photo['title'] ?? '');
            ?>
            <table class="galleryplus-embed-table">
                <thead><tr><th></th><th>Original</th><th>Full</th><th>Medium</th></tr></thead>
                <tbody>
                <?php foreach ($types as $label => $fmt) { ?>
                    <tr>
                        <td class="galleryplus-embed-label"><?php echo $label; ?></td>
                        <?php foreach ($sizes as $sk => $sv) { $sv = $to_abs($sv); if (!$sv) { echo '<td>&mdash;</td>'; continue; } ?>
                            <td><textarea class="galleryplus-embed-code" rows="1" readonly onclick="this.select();navigator.clipboard.writeText(this.value)"><?php echo htmlspecialchars($fmt($embed_url, $sv, $img_title)); ?></textarea></td>
                        <?php } ?>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
            <div class="galleryplus-direct-links">
                <?php foreach ($sizes as $sk => $sv) { $sv = $to_abs($sv); if (!$sv) continue; ?>
                    <div><code><?php echo $sk; ?>:</code> <input type="text" readonly value="<?php echo htmlspecialchars($sv); ?>" onclick="this.select();navigator.clipboard.writeText(this.value)"></div>
                <?php } ?>
            </div>
        </div>
        <?php } ?>

        <?php if ($has_exif && empty($hide_exif)) { ?>
        <div class="galleryplus-tab-pane" data-tab="exif">
            <dl class="galleryplus-exif-list">
            <?php foreach ($photo['exif_formatted'] as $item) { ?>
                <dt><?php echo $item['name']; ?></dt>
                <dd><?php echo $item['value']; ?></dd>
            <?php } ?>
            </dl>
        </div>
        <?php } ?>
    </div>

    <?php if (($gps_lat !== null && $gps_lon !== null) && empty($hide_map)) { ?>
        <div class="galleryplus-map-wrap">
            <div id="galleryplus-map" class="galleryplus-map"></div>
        </div>
    <?php } ?>
<?php } ?>

<?php if (!empty($similar_photos)) { ?>
    <div class="galleryplus-similar">
        <div class="galleryplus-similar-title"><?php echo defined('LANG_GALLERYPLUS_SIMILAR') ? LANG_GALLERYPLUS_SIMILAR : 'Похожие фото'; ?></div>
        <div class="galleryplus-grid galleryplus-similar-grid" id="galleryplus-similar-grid">
            <?php foreach ($similar_photos as $sp) {
                $spt = htmlspecialchars($sp['title'] ?: ($sp['filename'] ?? ''));
                $similar_paid_locked = !empty($paid_locked) && empty($sp['url_big']) && empty($sp['album_preview']);
            ?>
                <?php if ($similar_paid_locked) { ?>
                    <div class="galleryplus-item galleryplus-item--paid-blur">
                        <a href="#galleryplus-paywall" class="galleryplus-paid-lock">
                            <img src="<?php echo $sp['url_thumb']; ?>" alt="<?php echo $spt; ?>" loading="lazy" width="<?php echo $sp['width'] ?? 0; ?>" height="<?php echo $sp['height'] ?? 0; ?>" class="galleryplus-blurred">
                            <div class="galleryplus-paid-lock-icon">&#128274;</div>
                        </a>
                    </div>
                <?php } else { ?>
                <div class="galleryplus-item" data-object="<?php echo htmlspecialchars(json_encode([
                    'id'       => $sp['id'],
                    'url'      => $sp['url'],
                    'src'      => $sp['url_big'],
                    'nocrop'   => $sp['url_nocrop'] ?: '',
                    'thumb'    => $sp['url_thumb'],
                    'title'    => $spt,
                    'author'   => htmlspecialchars($sp['user']['nickname'] ?? ''),
                    'avatar'   => $sp['user']['avatar'] ?? '',
                    'adult'    => false,
                    'likes'    => 0,
                    'liked'    => false,
                    'favorite' => false,
                    'owner_id' => $sp['user_id'],
                    'comments' => $sp['comments'] ?? 0,
                    'desc'     => $sp['content'] ?? '',
                ], JSON_UNESCAPED_UNICODE)); ?>">
                    <a href="<?php echo $sp['url']; ?>" class="galleryplus-viewer-link">
                        <img src="<?php echo $sp['url_thumb']; ?>" alt="<?php echo $spt; ?>" loading="lazy" width="<?php echo $sp['width'] ?? 0; ?>" height="<?php echo $sp['height'] ?? 0; ?>">
                    </a>
                    <div class="galleryplus-item-overlay">
                        <a href="<?php echo $sp['url']; ?>" class="galleryplus-item-overlay-title"><?php echo $spt; ?></a>
                    </div>
                </div>
                <?php } ?>
            <?php } ?>
        </div>
    </div>
<?php } ?>

<?php if (!empty($comments_widget)) { ?>
    <div class="galleryplus-comments-block">
        <?php echo $comments_widget; ?>
    </div>
<?php } ?>

<!-- Fullscreen viewer overlay -->
<div id="galleryplus-original-viewer" class="galleryplus-original-viewer" style="display:none">
    <div class="galleryplus-original-viewer-bg"></div>
    <div class="galleryplus-original-viewer-close">&times;</div>
    <div class="galleryplus-original-viewer-wrap">
        <img src="" alt="" id="galleryplus-original-viewer-img" class="galleryplus-original-viewer-img">
    </div>
</div>

<script>
(function() {
    var tabs = document.querySelectorAll('.galleryplus-tabs-nav button');
    var galleryplusMap = null;

    tabs.forEach(function(btn) {
        btn.addEventListener('click', function() {
            tabs.forEach(function(b) { b.classList.remove('active'); });
            var panes = document.querySelectorAll('.galleryplus-tab-pane');
            panes.forEach(function(p) { p.classList.remove('active'); });
            btn.classList.add('active');
            var pane = document.querySelector('.galleryplus-tab-pane[data-tab="' + btn.dataset.tab + '"]');
            if (pane) pane.classList.add('active');
        });
    });

    /* ---- Fullscreen original viewer ---- */
    var viewImg = document.getElementById('galleryplus-view-img');
    var origViewer = document.getElementById('galleryplus-original-viewer');
    var origViewerImg = document.getElementById('galleryplus-original-viewer-img');
    var origViewerClose = origViewer.querySelector('.galleryplus-original-viewer-close');
    var origViewerBg = origViewer.querySelector('.galleryplus-original-viewer-bg');
    var origViewerWrap = origViewer.querySelector('.galleryplus-original-viewer-wrap');
    var origZoomed = false;

    if (viewImg && origViewer && !viewImg.classList.contains('galleryplus-blurred')) {
        viewImg.addEventListener('click', function() {
            var origUrl = viewImg.getAttribute('data-nocrop') || <?php echo $can_original ? json_encode($photo['url_original']) : 'null'; ?>;
            if (!origUrl) return;
            origViewerImg.src = origUrl;
            origViewer.style.display = '';
            origZoomed = false;
            fitToWindow();
        });

        function fitToWindow() {
            origViewerImg.style.maxWidth = '95vw';
            origViewerImg.style.maxHeight = '95vh';
            origViewerImg.style.width = 'auto';
            origViewerImg.style.height = 'auto';
            origViewerImg.style.cursor = 'zoom-in';
            origViewerWrap.style.cursor = '';
            origZoomed = false;
        }

        function zoomToOriginal() {
            origViewerImg.style.maxWidth = 'none';
            origViewerImg.style.maxHeight = 'none';
            origViewerImg.style.width = origViewerImg.naturalWidth + 'px';
            origViewerImg.style.height = origViewerImg.naturalHeight + 'px';
            origViewerImg.style.cursor = 'zoom-out';
            origViewerWrap.style.cursor = 'crosshair';
            origZoomed = true;
        }

        origViewerImg.addEventListener('click', function(e) {
            e.stopPropagation();
            if (origZoomed) {
                fitToWindow();
            } else {
                zoomToOriginal();
            }
        });

        function closeOrigViewer() {
            origViewer.style.display = 'none';
            origViewerImg.src = '';
        }

        origViewerClose.addEventListener('click', closeOrigViewer);
        origViewerBg.addEventListener('click', closeOrigViewer);
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && origViewer.style.display !== 'none') {
                closeOrigViewer();
            }
        });
    }
})();
</script>

<?php if (($gps_lat !== null && $gps_lon !== null) && empty($hide_map)) { ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var mapContainer = document.getElementById('galleryplus-map');
    if (!mapContainer || typeof L === 'undefined') return;
    var map = L.map('galleryplus-map').setView([<?php echo $gps_lat; ?>, <?php echo $gps_lon; ?>], 15);
    window.galleryplusMap = map;
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);
    var popupContent = '<a href="#" onclick="var e=document.getElementById(\'galleryplus-view-img\');if(e)e.click();return false;"><img src="<?php echo htmlspecialchars($photo['url_thumb'] ?? $photo['url_big'] ?? ''); ?>" class="galleryplus-map-popup-thumb" alt="<?php echo htmlspecialchars(addslashes($photo['title'] ?? '')); ?>"></a>';
    L.popup({className: 'leaflet-popup leaflet-zoom-animated galleryplus-map-popup'})
        .setLatLng([<?php echo $gps_lat; ?>, <?php echo $gps_lon; ?>])
        .setContent(popupContent)
        .openOn(map);
});
</script>
<?php } ?>
