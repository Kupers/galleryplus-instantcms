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
    var cols = isMobile ? 2 : Math.max(1, Math.round((gridWidth + gap) / (260 + gap)));

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
    function relayoutGrids() {
        var grids = document.querySelectorAll('.galleryplus-grid, .galleryplus-albums-grid');
        for (var i = 0; i < grids.length; i++) {
            grids[i].classList.remove('jsly');
            var isAlbum = grids[i].classList.contains('galleryplus-albums-grid');
            galleryplusMasonry(grids[i], isAlbum ? '.galleryplus-album-card' : '.galleryplus-item');
        }
    }

    relayoutGrids();

    var resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(relayoutGrids, 150);
    });

    window.addEventListener('load', function() {
        setTimeout(relayoutGrids, 50);
    });

    if (window.ResizeObserver) {
        var ro = new ResizeObserver(function(entries) {
            var changed = false;
            for (var i = 0; i < entries.length; i++) {
                var w = Math.round(entries[i].contentRect.width);
                if (typeof entries[i].target._gpWidth === 'number' && entries[i].target._gpWidth !== w) {
                    changed = true;
                }
                entries[i].target._gpWidth = w;
            }
            if (changed) { relayoutGrids(); }
        });
        var observed = document.querySelectorAll('.galleryplus-grid, .galleryplus-albums-grid');
        for (var j = 0; j < observed.length; j++) { ro.observe(observed[j]); }
    }

    // ---- Tag filter (сортировка по тегам) ----
    function initTagFilter() {

        var root = document.getElementById('galleryplus-tagfilter');
        if (!root) return;

        var input = root.querySelector('.galleryplus-tagfilter-input');
        var suggestBox = root.querySelector('.galleryplus-tagfilter-suggest');
        var addBtn = root.querySelector('.galleryplus-tagfilter-add');
        if (!input || !suggestBox) return;

        var acUrl = root.getAttribute('data-autocomplete') || '/tags/autocomplete';
        var baseUrl = root.getAttribute('data-base') || '';
        var removeTitle = root.getAttribute('data-remove-title') || '';
        var addTitle = root.getAttribute('data-add-title') || '';
        var resetTitle = root.getAttribute('data-reset-title') || '';

        var tags = (root.getAttribute('data-tags') || '').split(',').map(function(t) {
            return t.trim();
        }).filter(Boolean);

        var timer = null;
        var cache = {};
        var xhr = null;
        var items = [];
        var activeIndex = -1;
        var filterBusy = false;

        function escHtml(s) {
            return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function buildUrl(list) {
            if (!list.length) return baseUrl;
            return baseUrl + (baseUrl.indexOf('?') === -1 ? '?' : '&') +
                'tags=' + encodeURIComponent(list.join(','));
        }

        function go(list) {
            applyFilter(buildUrl(list));
        }

        // --- AJAX-фильтрация: страница не перезагружается, карточки просто исчезают ---
        function reloadPage(url) {
            window.location.href = url;
        }

        function renderFilter(data, url) {
            var grid = document.getElementById('galleryplus-grid');
            if (!grid) { reloadPage(url); return; }

            grid.innerHTML = data.empty ? (data.empty_html || '') : (data.html || '');

            var paged = document.getElementById('galleryplus-pagination');
            if (paged) {
                paged.innerHTML = data.pagination || '';
                paged.style.display = data.pagination ? '' : 'none';
            }

            if (window.history && window.history.pushState) {
                window.history.pushState({ galleryplusTags: 1 }, '', url);
            }

            syncChips(url);
            relayoutGrids();

            window.dispatchEvent(new CustomEvent('galleryplus:filter-applied', {
                detail: { page: data.page || 2, has_next: data.has_next ? 1 : 0 }
            }));
        }

        function applyFilter(url) {
            var grid = document.getElementById('galleryplus-grid');
            if (filterBusy || !grid || !window.XMLHttpRequest) { reloadPage(url); return; }

            filterBusy = true;
            root.classList.add('galleryplus-tagfilter--loading');

            var xhrF = new XMLHttpRequest();
            xhrF.open('GET', url + (url.indexOf('?') === -1 ? '?' : '&') + 'gp_filter=1', true);
            xhrF.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhrF.onload = function() {
                filterBusy = false;
                root.classList.remove('galleryplus-tagfilter--loading');
                if (xhrF.status !== 200) { reloadPage(url); return; }
                var data = null;
                try { data = JSON.parse(xhrF.responseText); } catch (e) {}
                if (!data || typeof data.html === 'undefined') { reloadPage(url); return; }
                renderFilter(data, url);
            };
            xhrF.onerror = function() {
                filterBusy = false;
                root.classList.remove('galleryplus-tagfilter--loading');
                reloadPage(url);
            };
            xhrF.send();
        }

        // Перерисовывает чипы/кнопки/«Сбросить» под новое состояние URL
        function syncChips(url) {
            var qs = String(url).split('?')[1] || '';
            var next = [];
            var parts = qs.split('&');
            for (var i = 0; i < parts.length; i++) {
                var kv = parts[i].split('=');
                if (kv[0] === 'tags') {
                    var raw = decodeURIComponent(kv.slice(1).join('=') || '');
                    var names = raw.split(',');
                    for (var j = 0; j < names.length; j++) {
                        if (names[j].trim()) { next.push(names[j].trim()); }
                    }
                }
            }

            tags = next;
            root.setAttribute('data-tags', next.join(','));

            var chips = root.querySelector('.galleryplus-tagfilter-chips');
            if (chips) {
                var html = '';
                for (var k = 0; k < next.length; k++) {
                    html += '<span class="galleryplus-tagfilter-chip" data-tag="' + escHtml(next[k]) + '">' +
                        '<span class="galleryplus-tagfilter-chip-name">#' + escHtml(next[k]) + '</span>' +
                        '<button type="button" class="galleryplus-tagfilter-chip-remove" data-tag="' + escHtml(next[k]) + '" title="' +
                        escHtml(removeTitle) + '">&times;</button></span>';
                }
                if (next.length) {
                    html += '<button type="button" class="galleryplus-tagfilter-add" id="galleryplus-tagfilter-add" title="' +
                        escHtml(addTitle) + '">+</button>';
                }
                chips.innerHTML = html;
            }

            var reset = root.querySelector('.galleryplus-tagfilter-reset');
            if (next.length) {
                if (!reset) {
                    reset = document.createElement('a');
                    reset.className = 'galleryplus-tagfilter-reset';
                    reset.href = baseUrl;
                    root.appendChild(reset);
                }
                reset.href = baseUrl;
                reset.textContent = resetTitle;
            } else if (reset && reset.parentNode) {
                reset.parentNode.removeChild(reset);
            }
        }

        function hasTag(name) {
            var low = name.toLowerCase();
            for (var i = 0; i < tags.length; i++) {
                if (tags[i].toLowerCase() === low) return true;
            }
            return false;
        }

        function addTag(name) {
            name = (name || '').trim().replace(/^#+/, '').trim();
            if (!name) return;
            hideSuggest();
            input.value = '';
            if (hasTag(name)) return;
            go(tags.concat([name]));
        }

        function removeTag(name) {
            var low = (name || '').toLowerCase();
            var next = [];
            for (var i = 0; i < tags.length; i++) {
                if (tags[i].toLowerCase() !== low) next.push(tags[i]);
            }
            go(next);
        }

        function hideSuggest() {
            suggestBox.innerHTML = '';
            suggestBox.classList.remove('open');
            items = [];
            activeIndex = -1;
        }

        function markActive(index) {
            for (var i = 0; i < items.length; i++) {
                if (i === index) items[i].classList.add('active');
                else items[i].classList.remove('active');
            }
            activeIndex = index;
        }

        function renderSuggest(list) {
            if (!list.length) { hideSuggest(); return; }
            var html = '';
            for (var i = 0; i < list.length; i++) {
                html += '<button type="button" class="galleryplus-tagfilter-suggest-item" data-value="' +
                    escHtml(list[i]) + '">#' + escHtml(list[i]) + '</button>';
            }
            suggestBox.innerHTML = html;
            suggestBox.classList.add('open');
            items = [];
            var found = suggestBox.querySelectorAll('.galleryplus-tagfilter-suggest-item');
            for (var j = 0; j < found.length; j++) { items.push(found[j]); }
            activeIndex = -1;
        }

        function request(term) {
            if (term.length < 1) { hideSuggest(); return; }
            if (cache[term]) { renderSuggest(cache[term]); return; }
            if (xhr) { xhr.abort(); }
            xhr = new XMLHttpRequest();
            xhr.open('GET', acUrl + '?term=' + encodeURIComponent(term), true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onload = function() {
                if (xhr.status !== 200) return;
                var list = [];
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data && data.length) {
                        for (var i = 0; i < data.length && i < 10; i++) {
                            var name = data[i].label || data[i].value || '';
                            if (name && !hasTag(name)) list.push(name);
                        }
                    }
                } catch(e) {}
                cache[term] = list;
                renderSuggest(list);
            };
            xhr.send();
        }

        input.addEventListener('input', function() {
            var term = input.value.replace(/^#+/, '').trim();
            clearTimeout(timer);
            timer = setTimeout(function() { request(term); }, 250);
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowDown' && items.length) {
                e.preventDefault();
                markActive(activeIndex + 1 >= items.length ? 0 : activeIndex + 1);
                return;
            }
            if (e.key === 'ArrowUp' && items.length) {
                e.preventDefault();
                markActive(activeIndex - 1 < 0 ? items.length - 1 : activeIndex - 1);
                return;
            }
            if (e.key === 'Escape') { hideSuggest(); return; }
            if (e.key === 'Enter') {
                e.preventDefault();
                var val = (input.value || '').trim().replace(/^#+/, '').trim();
                var picked = '';
                if (activeIndex > -1 && items[activeIndex]) {
                    picked = items[activeIndex].getAttribute('data-value');
                }
                if (!picked && val) {
                    for (var s = 0; s < items.length; s++) {
                        if ((items[s].getAttribute('data-value') || '').toLowerCase() === val.toLowerCase()) {
                            picked = items[s].getAttribute('data-value');
                            break;
                        }
                    }
                }
                if (picked) { addTag(picked); return; }
                if (val) {
                    request(val);
                    input.classList.add('galleryplus-tagfilter-input--error');
                    setTimeout(function() { input.classList.remove('galleryplus-tagfilter-input--error'); }, 1200);
                }
                return;
            }
            if (e.key === 'Backspace' && input.value === '' && tags.length) {
                removeTag(tags[tags.length - 1]);
            }
        });

        suggestBox.addEventListener('mousedown', function(e) {
            var btn = e.target.closest('.galleryplus-tagfilter-suggest-item');
            if (!btn) return;
            e.preventDefault();
            addTag(btn.getAttribute('data-value'));
        });

        root.addEventListener('click', function(e) {
            var rm = e.target.closest('.galleryplus-tagfilter-chip-remove');
            if (rm) {
                e.preventDefault();
                removeTag(rm.getAttribute('data-tag'));
                return;
            }
            if (e.target.closest('.galleryplus-tagfilter-add')) {
                e.preventDefault();
                input.focus();
            }
        });

        if (addBtn) {
            addBtn.setAttribute('aria-label', addBtn.getAttribute('title') || '');
        }

        // «Сбросить» — и в шапке, и в пустом состоянии внутри сетки
        document.addEventListener('click', function(e) {
            var rst = e.target.closest('.galleryplus-tagfilter-reset');
            if (!rst) return;
            var href = rst.getAttribute('href');
            if (!href) return;
            e.preventDefault();
            applyFilter(href);
        });

        document.addEventListener('click', function(e) {
            if (!root.contains(e.target)) hideSuggest();
        });

        input.addEventListener('blur', function() {
            setTimeout(hideSuggest, 150);
        });
    }

    initTagFilter();

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

    // ---- Favorites toggle (cards & single view) ----
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.galleryplus-fav-btn:not(.disabled)');
        if (!btn) return;
        var photoId = btn.dataset.photoId;
        if (!photoId) return;
        e.preventDefault();
        e.stopPropagation();

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/galleryplus/favorite', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function() {
            if (xhr.status !== 200) return;
            try {
                var r = JSON.parse(xhr.responseText);
                if (r.error) return;
                var fav = r.status === 'favorited';
                btn.classList.toggle('favorited', fav);
                btn.innerHTML = fav ? '\u2605' : '\u2606';
                btn.title = fav ? '\u0412 \u0438\u0437\u0431\u0440\u0430\u043d\u043d\u043e\u0435' : '\u0412 \u0438\u0437\u0431\u0440\u0430\u043d\u043d\u043e\u0435';
                // Sync the lightbox favorite button if open
                var vf = document.querySelector('.galleryplus-viewer-fav');
                if (vf && vf.dataset.photoId === photoId) {
                    vf.classList.toggle('favorited', fav);
                }
                // On favorites page: unfavoriting removes the card
                var item = btn.closest('.galleryplus-item');
                if (item && !fav && /\/favorites\/?$/.test(window.location.pathname)) {
                    item.remove();
                    if (typeof galleryplusMasonry === 'function') galleryplusMasonry(document.getElementById('galleryplus-grid'));
                }
            } catch(e) {}
        };
        xhr.send('photo_id=' + encodeURIComponent(photoId));
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
    var currentUserId = viewer.dataset.currentUser || '0';

    var viewerImg = viewer.querySelector('.galleryplus-viewer-img');
    var viewerTitle = viewer.querySelector('.galleryplus-viewer-title');
    var viewerDesc = viewer.querySelector('.galleryplus-viewer-desc');
    var viewerDescText = viewerDesc ? viewerDesc.querySelector('.galleryplus-viewer-desc-text') : null;
    var viewerDescToggle = viewerDesc ? viewerDesc.querySelector('.galleryplus-viewer-desc-toggle') : null;
    var viewerAuthor = viewer.querySelector('.galleryplus-viewer-author');
    var viewerAvatar = viewer.querySelector('.galleryplus-viewer-avatar');
    var viewerClose = viewer.querySelector('.galleryplus-viewer-close');
    var viewerPrev = viewer.querySelector('.galleryplus-viewer-prev');
    var viewerNext = viewer.querySelector('.galleryplus-viewer-next');
    var viewerBg = viewer.querySelector('.galleryplus-viewer-bg');
    var viewerLikeBtn = viewer.querySelector('.galleryplus-viewer-like');
    var viewerLikeCount = viewerLikeBtn ? viewerLikeBtn.querySelector('.galleryplus-viewer-like-count') : null;
    var viewerFavBtn = viewer.querySelector('.galleryplus-viewer-fav');
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
    var pendingNext = false;

    // ---- Auto light/dark theme + text bounds in the lightbox ----
    function getLightboxBrightness(img) {
        try {
            var nw = img.naturalWidth, nh = img.naturalHeight;
            if (!nw || !nh) return null;
            var cw = 48, ch = 48;
            if (nw > nh) { ch = Math.max(8, Math.round(nh * cw / nw)); }
            else { cw = Math.max(8, Math.round(nw * ch / nh)); }
            var canvas = document.createElement('canvas');
            canvas.width = cw; canvas.height = ch;
            var ctx = canvas.getContext('2d');
            if (!ctx) return null;
            ctx.drawImage(img, 0, 0, cw, ch);
            var data = ctx.getImageData(0, 0, cw, ch).data;
            var sum = 0, count = 0, startRow = Math.floor(ch * 0.6);
            for (var i = 0; i < data.length; i += 4) {
                var row = Math.floor((i / 4) / cw);
                if (row < startRow) continue;
                sum += 0.2126 * data[i] + 0.7152 * data[i + 1] + 0.0722 * data[i + 2];
                count++;
            }
            return count ? sum / count : null;
        } catch (e) { return null; }
    }

    function getDrawnImageWidth(img) {
        var nw = img.naturalWidth, nh = img.naturalHeight;
        if (!nw || !nh) return 0;
        var boxW = img.clientWidth, boxH = img.clientHeight;
        if (!boxW || !boxH) return 0;
        var scale = Math.min(boxW / nw, boxH / nh);
        return Math.round(nw * scale);
    }

    function applyLightboxBounds() {
        var w = getDrawnImageWidth(viewerImg);
        if (!w) return;
        viewerTitle.style.width = w + 'px';
        viewerTitle.style.maxWidth = w + 'px';
        viewerTitle.style.margin = '0 auto';
        if (viewerDesc) {
            viewerDesc.style.width = w + 'px';
            viewerDesc.style.maxWidth = w + 'px';
        }
    }

    function resetLightboxAutoStyles() {
        viewer.classList.remove('galleryplus-viewer--light');
        viewerTitle.style.width = '';
        viewerTitle.style.maxWidth = '';
        viewerTitle.style.margin = '';
        if (viewerDesc) {
            viewerDesc.style.width = '';
            viewerDesc.style.maxWidth = '';
        }
    }

    function applyLightboxBrightness() {
        var lum = getLightboxBrightness(viewerImg);
        viewer.classList.toggle('galleryplus-viewer--light', lum !== null && lum > 150);
    }

    function onViewerImageReady() {
        applyLightboxBrightness();
        applyLightboxBounds();
    }

    viewerImg.addEventListener('load', onViewerImageReady);
    window.addEventListener('resize', function() {
        if (viewer.classList.contains('galleryplus-viewer--show')) {
            applyLightboxBounds();
        }
    });

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

    var viewerStatePushed = false;

    function openViewer(item) {
        currentItem = item;
        var obj = getObject(item);
        if (!obj) return;
        window.__gpViewerOpen = true;
        if (!viewerStatePushed && window.history && history.pushState) {
            history.pushState({ galleryplusViewer: true }, '', window.location.href);
            viewerStatePushed = true;
        }
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
        resetLightboxAutoStyles();
        if (viewerImg.complete && viewerImg.naturalWidth) {
            onViewerImageReady();
        }
        viewerTitle.querySelector('.galleryplus-viewer-title-text').textContent = obj.title;
        viewerTitle.href = obj.url || '#';
        if (viewerDesc) {
            var desc = obj.desc || '';
            var showDesc = viewer.dataset.showDesc === '1';
            var truncate = viewer.dataset.truncateDesc === '1';
            var limit = parseInt(viewer.dataset.descLimit || '0', 10);
            viewerDesc.dataset.full = desc;
            viewerDesc.style.display = (showDesc && desc) ? '' : 'none';
            if (viewerDescToggle) {
                viewerDescToggle.style.display = 'none';
            }
            if (showDesc && desc && truncate && limit > 0 && desc.length > limit) {
                viewerDescText.textContent = desc.slice(0, limit) + '\u2026';
                if (viewerDescToggle) {
                    viewerDescToggle.dataset.collapsed = '1';
                    viewerDescToggle.style.display = 'block';
                }
            } else {
                viewerDescText.textContent = desc;
            }
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
            if (viewerLikeCount) viewerLikeCount.textContent = count;
            viewerLikeBtn.classList.toggle('liked', liked);
            viewerLikeBtn.classList.toggle('disabled', isOwner);
            viewerLikeBtn.title = isOwner ? 'Cannot like your own photo' : 'Like';
        }
        // Update viewer favorite button
        if (viewerFavBtn) {
            viewerFavBtn.dataset.photoId = obj.id || '';
            var fav = obj.favorite ? true : false;
            viewerFavBtn.classList.toggle('favorited', fav);
            viewerFavBtn.classList.toggle('disabled', !currentUserId);
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
        prefetchNext();
    }

    function closeViewer(fromPopstate) {
        if (!window.__gpViewerOpen) return;
        var needBack = viewerStatePushed && !fromPopstate;
        viewerStatePushed = false;
        window.__gpViewerOpen = false;
        var closingItem = currentItem;
        viewer.classList.remove('galleryplus-viewer--show');
        viewer.classList.add('galleryplus-viewer--hide');
        document.body.classList.remove('galleryplus-viewer-open');
        currentItem = null;
        if (idleTimer) { clearTimeout(idleTimer); idleTimer = null; }
        document.documentElement.removeAttribute('data-idle');
        closeCommentsPanel();
        viewerSharePopup.classList.remove('galleryplus-viewer-share-popup--show');
        // Keep the feed scrolled to the photo that was open
        if (closingItem && closingItem.isConnected) {
            var stickyOffset = 80;
            setTimeout(function() {
                var top = closingItem.getBoundingClientRect().top + window.pageYOffset;
                window.scrollTo({ top: Math.max(0, top - stickyOffset), behavior: 'smooth' });
            }, 60);
        }
        if (needBack && window.history) {
            window.__gpViewerClosing = true;
            history.back();
        }
    }

    // ---- Description expand/collapse ----
    if (viewerDescToggle) {
        viewerDescToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            if (!viewerDesc || !viewerDescText) return;
            var collapsed = viewerDescToggle.dataset.collapsed === '1';
            var full = viewerDesc.dataset.full || '';
            if (collapsed) {
                viewerDescText.textContent = full;
                viewerDescToggle.textContent = viewerDescToggle.dataset.collapseLabel || 'Collapse';
                viewerDescToggle.dataset.collapsed = '0';
            } else {
                var limit = parseInt(viewer.dataset.descLimit || '0', 10);
                viewerDescText.textContent = (limit > 0 && full.length > limit) ? full.slice(0, limit) + '\u2026' : full;
                viewerDescToggle.textContent = viewerDescToggle.dataset.fullLabel || 'Show full';
                viewerDescToggle.dataset.collapsed = '1';
            }
        });
    }

    function updateNavButtons() {
        var prev = currentItem ? currentItem.previousElementSibling : null;
        var next = currentItem ? currentItem.nextElementSibling : null;
        while (prev && !prev.hasAttribute('data-object')) { prev = prev.previousElementSibling; }
        while (next && !next.hasAttribute('data-object')) { next = next.nextElementSibling; }
        viewer.classList.toggle('galleryplus-viewer--nav-prev', !!prev);
        viewer.classList.toggle('galleryplus-viewer--nav-next', !!next);
    }

    function hasNextPage() {
        var g = document.getElementById('galleryplus-grid');
        return !!(g && g.dataset.hasNext === '1' && typeof window.galleryplusLoadMore === 'function');
    }

    function lastGridItem() {
        var g = document.getElementById('galleryplus-grid');
        if (!g) return null;
        var items = g.querySelectorAll('.galleryplus-item');
        return items.length ? items[items.length - 1] : null;
    }

    // Preload the next page from inside the lightbox when near the end
    function prefetchNext() {
        if (!currentItem || !hasNextPage()) return;
        var last = lastGridItem();
        if (last && (currentItem === last || currentItem.nextElementSibling === null)) {
            window.galleryplusLoadMore();
        }
    }

    function navigate(dir) {
        if (!currentItem) return;
        var sibling = dir === 'prev' ? currentItem.previousElementSibling : currentItem.nextElementSibling;
        while (sibling && !sibling.hasAttribute('data-object')) {
            sibling = dir === 'prev' ? sibling.previousElementSibling : sibling.nextElementSibling;
        }
        if (sibling) {
            openViewer(sibling);
        } else if (dir === 'next' && hasNextPage()) {
            pendingNext = true;
            window.galleryplusLoadMore();
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

    // Viewer favorite button
    if (viewerFavBtn) {
        viewerFavBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (viewerFavBtn.classList.contains('disabled')) return;
            var photoId = viewerFavBtn.dataset.photoId;
            if (!photoId) return;
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '/galleryplus/favorite', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onload = function() {
                if (xhr.status !== 200) return;
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r.error) return;
                    var fav = r.status === 'favorited';
                    viewerFavBtn.classList.toggle('favorited', fav);
                    viewerFavBtn.title = fav ? '\u0412 \u0438\u0437\u0431\u0440\u0430\u043d\u043d\u043e\u0435' : '\u0412 \u0438\u0437\u0431\u0440\u0430\u043d\u043d\u043e\u0435';
                    // Update data-object on current item to keep state on navigate
                    if (currentItem) {
                        try {
                            var obj = JSON.parse(currentItem.getAttribute('data-object'));
                            obj.favorite = fav;
                            currentItem.setAttribute('data-object', JSON.stringify(obj));
                        } catch(e) {}
                    }
                    // Sync card button
                    var cardBtn = document.querySelector('.galleryplus-fav-btn[data-photo-id="' + photoId + '"]');
                    if (cardBtn) {
                        cardBtn.classList.toggle('favorited', fav);
                        cardBtn.innerHTML = fav ? '\u2605' : '\u2606';
                    }
                    // On favorites page: unfavoriting removes the card
                    var item = currentItem;
                    if (item && !fav && /\/favorites\/?$/.test(window.location.pathname)) {
                        item.remove();
                        if (typeof galleryplusMasonry === 'function') galleryplusMasonry(document.getElementById('galleryplus-grid'));
                    }
                } catch(e) {}
            };
            xhr.send('photo_id=' + encodeURIComponent(photoId));
        });
    }

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
    viewerClose.addEventListener('click', function() { closeViewer(false); });

    // Background click → close
    viewerBg.addEventListener('click', function() { closeViewer(false); });

    // Prev/Next
    viewerPrev.addEventListener('click', function(e) { e.stopPropagation(); navigate('prev'); });
    viewerNext.addEventListener('click', function(e) { e.stopPropagation(); navigate('next'); });

    // Reset idle on viewer move
    viewer.addEventListener('mousemove', resetIdle);
    viewer.addEventListener('mousedown', resetIdle);

    // Continue navigation / refresh arrows after the next page is appended
    window.addEventListener('galleryplus:more-loaded', function() {
        updateNavButtons();
        if (pendingNext) {
            pendingNext = false;
            navigate('next');
        }
    });

    // Keyboard
    document.addEventListener('keydown', function(e) {
        if (!viewer.classList.contains('galleryplus-viewer--show')) return;
        if (e.key === 'Escape') { closeViewer(false); return; }
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

    // Hardware/software Back button: close the lightbox instead of leaving the page.
    // We push a history state when the viewer opens; this catches the resulting popstate.
    window.addEventListener('popstate', function(e) {
        if (window.__gpViewerClosing) {
            window.__gpViewerClosing = false;
            return;
        }
        if (window.__gpViewerOpen) {
            closeViewer(true);
        }
    });

})();
