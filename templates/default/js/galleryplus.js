/* GalleryPlus - JS Masonry */
window.galleryplusMasonry = function(container, itemSelector) {
    var grid = typeof container === 'string' ? document.getElementById(container) : container;
    if (!grid) return;
    itemSelector = itemSelector || '.galleryplus-item';

    var items = grid.querySelectorAll(itemSelector);
    if (!items.length) { grid.classList.add('jsly'); return; }

    var isMobile = window.matchMedia && window.matchMedia('(max-width: 640px)').matches;
    var gap = isMobile ? 1 : 12;
    var gridWidth = grid.getBoundingClientRect().width;
    var cols = isMobile ? 2 : Math.max(1, Math.floor((gridWidth + gap) / (260 + gap)));

    var colHeights = [];
    for (var c = 0; c < cols; c++) colHeights[c] = 0;
    var itemWidth = (gridWidth - gap * (cols - 1)) / cols;

    for (var i = 0; i < items.length; i++) {
        var item = items[i];
        var img = item.querySelector('img');
        var w = 0;
        var h = 0;
        if (img) {
            w = parseInt(img.getAttribute('width')) || parseInt(img.getAttribute('data-width')) || 0;
            h = parseInt(img.getAttribute('height')) || parseInt(img.getAttribute('data-height')) || 0;
        }

        var itemH;
        var itemHCalc;
        if (w && h) {
            itemH = itemHCalc = itemWidth * (h / w);
        } else if (img && img.naturalWidth && img.naturalHeight) {
            itemH = itemHCalc = itemWidth * (img.naturalHeight / img.naturalWidth);
        } else if (img) {
            itemH = itemHCalc = itemWidth * 0.75;
        } else {
            itemH = itemHCalc = 0;
        }

        // альбомные карточки: обложка имеет min-height (cover) + бордеры карточки
        var cover = item.querySelector('.galleryplus-album-cover');
        if (grid.classList.contains('galleryplus-albums-grid') && cover) {
            var cs = getComputedStyle(cover);
            var minH = parseInt(cs.minHeight, 10) || 0;
            var itemCs = getComputedStyle(item);
            var borders = (parseInt(itemCs.borderTopWidth, 10) || 0) + (parseInt(itemCs.borderBottomWidth, 10) || 0);
            itemH = Math.max(itemH, minH) + borders;
        }

        if (img) {
            img.style.width = itemWidth + 'px';
            img.style.height = itemHCalc + 'px';
        }

        var shortest = 0;
        for (var c = 1; c < cols; c++) {
            if (colHeights[c] < colHeights[shortest]) shortest = c;
        }

        var left = shortest * (itemWidth + gap);
        var top = colHeights[shortest];

        item.style.display = 'inline-block';
        item.classList.add('position-absolute');
        item.style.width = itemWidth + 'px';
        item.style.left = left + 'px';
        item.style.top = top + 'px';

        colHeights[shortest] = top + itemH + gap;
    }

    var maxH = 0;
    for (var c = 0; c < cols; c++) {
        if (colHeights[c] > maxH) maxH = colHeights[c];
    }
    grid.style.height = maxH + 'px';
    grid.classList.add('jsly');
};

(function() {
    'use strict';

    // ---- Init masonry on page load ----
    var grids = document.querySelectorAll('.galleryplus-grid, .galleryplus-albums-grid');
    for (var i = 0; i < grids.length; i++) {
        var isAlbum = grids[i].classList.contains('galleryplus-albums-grid');
        galleryplusMasonry(grids[i], isAlbum ? '.galleryplus-album-card' : '.galleryplus-item');
    }

    var resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            var grids = document.querySelectorAll('.galleryplus-grid, .galleryplus-albums-grid');
            for (var i = 0; i < grids.length; i++) {
                grids[i].classList.remove('jsly');
                var isAlbum = grids[i].classList.contains('galleryplus-albums-grid');
                galleryplusMasonry(grids[i], isAlbum ? '.galleryplus-album-card' : '.galleryplus-item');
            }
        }, 150);
    });

    // ---- Tab switching ----
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.galleryplus-tabs-nav button[data-tab]');
        if (!btn) return;

        var nav = btn.closest('.galleryplus-tabs-nav');
        if (!nav) return;

        nav.querySelectorAll('button').forEach(function(b) { b.classList.remove('active'); });
        btn.classList.add('active');

        var tabsWrap = nav.closest('.galleryplus-tabs');
        var contentWrap = tabsWrap ? tabsWrap.nextElementSibling : null;
        if (!contentWrap || !contentWrap.classList.contains('galleryplus-tabs-content')) {
            contentWrap = nav.parentElement.nextElementSibling;
        }
        if (!contentWrap) return;
        var pane = contentWrap.querySelector('.galleryplus-tab-pane[data-tab="' + btn.dataset.tab + '"]');
        if (pane) {
            contentWrap.querySelectorAll('.galleryplus-tab-pane').forEach(function(p) { p.classList.remove('active'); });
            pane.classList.add('active');
        }
    });

    // ---- Like button ----
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.galleryplus-like-btn:not(.disabled)');
        if (!btn) return;

        var targetId = btn.dataset.targetId;
        var targetType = btn.dataset.targetType;
        if (!targetId || !targetType) return;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/galleryplus/like', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function() {
            if (xhr.status !== 200) return;
            try {
                var r = JSON.parse(xhr.responseText);
                if (r.error) return;
                btn.classList.toggle('liked', r.status === 'liked');
                var icon = btn.querySelector('.galleryplus-like-icon');
                if (icon) icon.textContent = r.status === 'liked' ? '\u2665' : '\u2661';
                var count = btn.querySelector('.galleryplus-like-count');
                if (count) count.textContent = r.count;
            } catch(e) {}
        };
        xhr.send('target_id=' + encodeURIComponent(targetId) + '&target_type=' + encodeURIComponent(targetType));
    });

    // ---- Embed code auto-select on click ----
    document.addEventListener('click', function(e) {
        var textarea = e.target.closest('.galleryplus-embed-code');
        if (!textarea) return;
        textarea.select();
        try { navigator.clipboard.writeText(textarea.value); } catch(e) {}
    });

    // ---- Viewer (lightbox) ----
    var viewer = document.getElementById('galleryplus-viewer');
    if (!viewer) return;

    var viewerImg = viewer.querySelector('.galleryplus-viewer-img');
    var viewerTitle = viewer.querySelector('.galleryplus-viewer-title');
    var viewerDesc = viewer.querySelector('.galleryplus-viewer-desc');
    var viewerAuthor = viewer.querySelector('.galleryplus-viewer-author');
    var viewerAvatar = viewer.querySelector('.galleryplus-viewer-avatar');
    var viewerClose = viewer.querySelector('.galleryplus-viewer-close');
    var viewerPrev = viewer.querySelector('.galleryplus-viewer-prev');
    var viewerNext = viewer.querySelector('.galleryplus-viewer-next');
    var viewerBg = viewer.querySelector('.galleryplus-viewer-bg');
    var viewerLikeBtn = viewer.querySelector('.galleryplus-viewer-like');
    var viewerLikeCount = viewerLikeBtn ? viewerLikeBtn.querySelector('.galleryplus-viewer-like-count') : null;
    var viewerCommentsBtn = viewer.querySelector('.galleryplus-viewer-comments');
    var viewerCommentsCount = viewerCommentsBtn ? viewerCommentsBtn.querySelector('.galleryplus-viewer-comments-count') : null;
    var viewerShareBtn = viewer.querySelector('.galleryplus-viewer-share');
    var viewerSharePopup = viewer.querySelector('.galleryplus-viewer-share-popup');
    var viewerCommentsOverlay = viewer.querySelector('.galleryplus-viewer-comments-overlay');
    var viewerCommentsPanel = viewer.querySelector('.galleryplus-viewer-comments-panel');
    var viewerCommentsBody = viewer.querySelector('.galleryplus-viewer-comments-body');
    var viewerCommentsClose = viewer.querySelector('.galleryplus-viewer-comments-close');

    var currentItem = null;
    var idleTimer = null;

    function getObject(item) {
        if (!item) return null;
        try { return JSON.parse(item.getAttribute('data-object')); } catch(e) { return null; }
    }

    function isGuest() {
        var grid = document.getElementById('galleryplus-grid');
        return grid ? grid.dataset.isGuest === '1' : true;
    }

    // ---- Share popup ----
    viewerShareBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        viewerSharePopup.classList.toggle('galleryplus-viewer-share-popup--show');
    });

    document.addEventListener('click', function(e) {
        if (viewerSharePopup && viewerSharePopup.classList.contains('galleryplus-viewer-share-popup--show')) {
            if (!viewerSharePopup.contains(e.target) && e.target !== viewerShareBtn && !viewerShareBtn.contains(e.target)) {
                viewerSharePopup.classList.remove('galleryplus-viewer-share-popup--show');
            }
        }
    });

    // Share links update on viewer open
    document.addEventListener('click', function(e) {
        var shareOpt = e.target.closest('.galleryplus-viewer-share-option');
        if (!shareOpt || !currentItem) return;
        e.preventDefault();
        var obj = getObject(currentItem);
        if (!obj) return;
        var url = obj.url || window.location.href;
        if (url && url.charAt(0) === '/') {
            url = location.origin + url;
        }
        var title = encodeURIComponent(obj.title || '');

        if (shareOpt.dataset.share === 'copy') {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() {
                    var span = shareOpt.querySelector('span');
                    if (span) {
                        var orig = span.textContent;
                        span.textContent = 'Copied!';
                        setTimeout(function() { span.textContent = orig; }, 1500);
                    }
                });
            } else {
                var ta = document.createElement('textarea');
                ta.value = url;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
            }
        } else {
            var shareUrl = '';
            switch (shareOpt.dataset.share) {
                case 'pinterest':
                    shareUrl = 'https://ru.pinterest.com/pin/create/button/?url=' + encodeURIComponent(url) + '&description=' + title;
                    break;
                case 'vk':
                    shareUrl = 'https://vk.com/share.php?url=' + encodeURIComponent(url) + '&title=' + title;
                    break;
                case 'telegram':
                    shareUrl = 'https://t.me/share/url?url=' + encodeURIComponent(url) + '&text=' + title;
                    break;
            }
            if (shareUrl) {
                window.open(shareUrl, '_blank', 'width=600,height=500');
            }
        }
        viewerSharePopup.classList.remove('galleryplus-viewer-share-popup--show');
    });

    // ---- Comments panel ----
    viewerCommentsBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        openCommentsPanel();
    });

    viewerCommentsClose.addEventListener('click', function(e) {
        closeCommentsPanel();
    });

    viewerCommentsOverlay.addEventListener('click', function(e) {
        if (e.target === viewerCommentsOverlay) {
            closeCommentsPanel();
        }
    });

    function openCommentsPanel() {
        if (!currentItem) return;
        var obj = getObject(currentItem);
        if (!obj) return;

        viewerCommentsOverlay.classList.add('galleryplus-viewer-comments-overlay--show');
        viewerCommentsBody.innerHTML = '<p style="text-align:center;padding:40px;color:#999">Loading...</p>';

        var xhr = new XMLHttpRequest();
        xhr.open('GET', '/galleryplus/comments_html?target_type=photo&target_id=' + obj.id, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r.html) {
                        viewerCommentsBody.innerHTML = r.html;
                        if (typeof icms !== 'undefined' && icms.comments && icms.comments.init) {
                            icms.comments.init(r.urls || {}, r.target || {});
                            icms.comments.onDocumentReady();
                        }
                        if (typeof r.count !== 'undefined' && viewerCommentsCount) {
                            viewerCommentsCount.textContent = r.count;
                        }
                    } else {
                        viewerCommentsBody.innerHTML = '<p style="text-align:center;padding:40px;color:#999">No comments</p>';
                    }
                } catch(e) {
                    viewerCommentsBody.innerHTML = xhr.responseText;
                    if (typeof icms !== 'undefined' && icms.comments && icms.comments.init) {
                        icms.comments.init({}, {});
                        icms.comments.onDocumentReady();
                    }
                }
            } else {
                viewerCommentsBody.innerHTML = '<p style="text-align:center;padding:40px;color:#999">Error loading comments</p>';
            }
        };
        xhr.onerror = function() {
            viewerCommentsBody.innerHTML = '<p style="text-align:center;padding:40px;color:#999">Error loading comments</p>';
        };
        xhr.send();
    }

    function closeCommentsPanel() {
        viewerCommentsOverlay.classList.remove('galleryplus-viewer-comments-overlay--show');
    }

    function openViewer(item) {
        currentItem = item;
        var obj = getObject(item);
        if (!obj) return;
        var guest = isGuest();
        if (obj.adult && guest) {
            viewerImg.src = obj.src;
            viewerImg.alt = obj.title;
            viewerImg.classList.add('galleryplus-viewer-blurred');
            viewer.classList.add('galleryplus-viewer--adult');
        } else {
            viewerImg.src = obj.src;
            viewerImg.alt = obj.title;
            viewerImg.classList.remove('galleryplus-viewer-blurred');
            viewer.classList.remove('galleryplus-viewer--adult');
        }
        viewerTitle.querySelector('.galleryplus-viewer-title-text').textContent = obj.title;
        viewerTitle.href = obj.url || '#';
        if (viewerDesc) {
            var desc = obj.desc || '';
            viewerDesc.textContent = desc;
            viewerDesc.style.display = (viewer.dataset.showDesc === '1' && desc) ? '' : 'none';
        }
        viewerAuthor.textContent = obj.author;
        if (obj.avatar) {
            viewerAvatar.src = obj.avatar;
            viewerAvatar.style.display = '';
        } else {
            viewerAvatar.style.display = 'none';
        }
        // Update viewer like button
        if (viewerLikeBtn) {
            var currentUserId = viewer.dataset.currentUser || '0';
            var isOwner = obj.owner_id && String(obj.owner_id) === currentUserId;
            viewerLikeBtn.dataset.targetId = obj.id || '';
            var liked = obj.liked ? true : false;
            var count = obj.likes || 0;
            var likeIcon = viewerLikeBtn.querySelector('.galleryplus-viewer-like-icon');
            if (likeIcon) likeIcon.textContent = liked ? '\u2665' : '\u2661';
            if (viewerLikeCount) viewerLikeCount.textContent = count;
            viewerLikeBtn.classList.toggle('liked', liked);
            viewerLikeBtn.classList.toggle('disabled', isOwner);
            viewerLikeBtn.title = isOwner ? 'Cannot like your own photo' : 'Like';
        }
        // Update comments count
        if (viewerCommentsCount) {
            viewerCommentsCount.textContent = obj.comments || 0;
        }

        viewer.classList.remove('galleryplus-viewer--hide');
        viewer.classList.add('galleryplus-viewer--show');
        document.body.classList.add('galleryplus-viewer-open');
        updateNavButtons();
        resetIdle();
    }

    function closeViewer() {
        viewer.classList.remove('galleryplus-viewer--show');
        viewer.classList.add('galleryplus-viewer--hide');
        document.body.classList.remove('galleryplus-viewer-open');
        currentItem = null;
        if (idleTimer) { clearTimeout(idleTimer); idleTimer = null; }
        document.documentElement.removeAttribute('data-idle');
        closeCommentsPanel();
        viewerSharePopup.classList.remove('galleryplus-viewer-share-popup--show');
    }

    function updateNavButtons() {
        var prev = currentItem ? currentItem.previousElementSibling : null;
        var next = currentItem ? currentItem.nextElementSibling : null;
        while (prev && !prev.hasAttribute('data-object')) { prev = prev.previousElementSibling; }
        while (next && !next.hasAttribute('data-object')) { next = next.nextElementSibling; }
        viewer.classList.toggle('galleryplus-viewer--nav-prev', !!prev);
        viewer.classList.toggle('galleryplus-viewer--nav-next', !!next);
    }

    function navigate(dir) {
        if (!currentItem) return;
        var sibling = dir === 'prev' ? currentItem.previousElementSibling : currentItem.nextElementSibling;
        while (sibling && !sibling.hasAttribute('data-object')) {
            sibling = dir === 'prev' ? sibling.previousElementSibling : sibling.nextElementSibling;
        }
        if (sibling) {
            openViewer(sibling);
        }
    }

    function resetIdle() {
        document.documentElement.removeAttribute('data-idle');
        if (idleTimer) { clearTimeout(idleTimer); }
        idleTimer = setTimeout(function() {
            document.documentElement.setAttribute('data-idle', '1');
        }, 2500);
    }

    // Click on image → open viewer (or redirect guests for adult content)
    document.addEventListener('click', function(e) {
        var link = e.target.closest('.galleryplus-viewer-link');
        if (!link) return;
        var item = link.closest('.galleryplus-item');
        if (!item) return;
        var obj = getObject(item);
        if (obj && obj.adult) {
            var grid = document.getElementById('galleryplus-grid');
            var isGuest = grid ? grid.dataset.isGuest === '1' : true;
            if (isGuest) {
                e.preventDefault();
                var loginUrl = grid ? (grid.dataset.loginUrl || '/auth/login') : '/auth/login';
                window.location.href = loginUrl;
                return;
            }
        }
        e.preventDefault();
        openViewer(item);
    });

    // Viewer like button
    if (viewerLikeBtn) {
        viewerLikeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (viewerLikeBtn.classList.contains('disabled')) return;
            var targetId = viewerLikeBtn.dataset.targetId;
            if (!targetId) return;
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '/galleryplus/like', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onload = function() {
                if (xhr.status !== 200) return;
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r.error) return;
                    var liked = r.status === 'liked';
                    viewerLikeBtn.classList.toggle('liked', liked);
                    var likeIcon = viewerLikeBtn.querySelector('.galleryplus-viewer-like-icon');
                    if (likeIcon) likeIcon.textContent = liked ? '\u2665' : '\u2661';
                    if (viewerLikeCount) viewerLikeCount.textContent = r.count;
                    // Update data-object on current item to keep state on navigate
                    if (currentItem) {
                        try {
                            var obj = JSON.parse(currentItem.getAttribute('data-object'));
                            obj.liked = liked;
                            obj.likes = r.count;
                            currentItem.setAttribute('data-object', JSON.stringify(obj));
                        } catch(e) {}
                    }
                } catch(e) {}
            };
            xhr.send('target_id=' + encodeURIComponent(targetId) + '&target_type=photo');
        });
    }

    // Close button
    viewerClose.addEventListener('click', closeViewer);

    // Background click → close
    viewerBg.addEventListener('click', closeViewer);

    // Prev/Next
    viewerPrev.addEventListener('click', function(e) { e.stopPropagation(); navigate('prev'); });
    viewerNext.addEventListener('click', function(e) { e.stopPropagation(); navigate('next'); });

    // Reset idle on viewer move
    viewer.addEventListener('mousemove', resetIdle);
    viewer.addEventListener('mousedown', resetIdle);

    // Keyboard
    document.addEventListener('keydown', function(e) {
        if (!viewer.classList.contains('galleryplus-viewer--show')) return;
        if (e.key === 'Escape') { closeViewer(); return; }
        if (e.key === 'ArrowLeft') { e.preventDefault(); navigate('prev'); return; }
        if (e.key === 'ArrowRight') { e.preventDefault(); navigate('next'); return; }
    });

    // Touch swipe support
    var touchStartX = 0;
    var touchStartY = 0;
    var touchMoved = false;

    viewer.addEventListener('touchstart', function(e) {
        if (e.target.closest('.galleryplus-viewer-comments-panel') || e.target.closest('.galleryplus-viewer-share-popup')) return;
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
        touchMoved = false;
    }, { passive: true });

    viewer.addEventListener('touchmove', function(e) {
        touchMoved = true;
    }, { passive: true });

    viewer.addEventListener('touchend', function(e) {
        if (!touchMoved) return;
        var dx = e.changedTouches[0].clientX - touchStartX;
        var dy = e.changedTouches[0].clientY - touchStartY;
        if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 50) {
            if (e.cancelable) e.preventDefault();
            if (dx < 0) {
                navigate('next');
            } else {
                navigate('prev');
            }
        }
    });

})();
