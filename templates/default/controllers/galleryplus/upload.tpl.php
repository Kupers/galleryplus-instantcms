<?php
    $this->addTplCSSName('galleryplus');
    $this->addTplJSName('galleryplus');
    $this->setPageTitle(LANG_GALLERYPLUS_UPLOAD ?? 'Upload');
    $this->addBreadcrumb(LANG_GALLERYPLUS_TITLE ?? 'Gallery', href_to('galleryplus'));
    $this->addBreadcrumb(LANG_GALLERYPLUS_UPLOAD ?? 'Upload');
?>
<div class="galleryplus-upload">
    <div class="galleryplus-upload-header">
        <h1><?php echo LANG_GALLERYPLUS_UPLOAD ?? 'Upload images'; ?></h1>
    </div>

    <div class="galleryplus-upload-dropzone" id="galleryplus-dropzone">
        <div class="galleryplus-upload-icon">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
        </div>
        <p class="galleryplus-upload-text"><?php echo LANG_GALLERYPLUS_DROP_HERE ?? 'Drag & drop images here or click to browse'; ?></p>
        <p class="galleryplus-upload-hint">
    JPG, PNG, GIF, WebP
    <?php if (!empty($options['max_file_size'])) { ?>
        — <?php echo LANG_GALLERYPLUS_MAX_FILE_SIZE; ?>: <?php echo $options['max_file_size']; ?>MB
    <?php } ?>
    <?php if (!empty($options['max_width']) || !empty($options['max_height'])) { ?>
        — <?php echo LANG_GALLERYPLUS_MAX_DIMENSIONS; ?>: <?php echo !empty($options['max_width']) ? $options['max_width'] : '&infin;'; ?>x<?php echo !empty($options['max_height']) ? $options['max_height'] : '&infin;'; ?>px
    <?php } ?>
</p>
        <input type="file" id="galleryplus-file-input" multiple accept="image/jpeg,image/png,image/gif,image/webp" style="display:none">
    </div>

    <div class="galleryplus-upload-progress" id="galleryplus-upload-progress" style="display:none">
        <div class="galleryplus-upload-progress-bar" id="galleryplus-progress-bar"></div>
    </div>

    <form action="<?php echo href_to('galleryplus', 'save'); ?>" method="post" id="galleryplus-upload-form" autocomplete="off">
        <div class="galleryplus-upload-album-select"<?php echo !empty($album_id) ? ' style="display:none"' : ''; ?>>
            <label><?php echo LANG_GALLERYPLUS_ALBUM ?? 'Album'; ?>:</label>
            <div class="galleryplus-album-search-wrap">
                <input type="text" id="galleryplus-album-search" class="form-control" autocomplete="nope" placeholder="<?php echo defined('LANG_GALLERYPLUS_ALBUM_SEARCH_PLACEHOLDER') ? LANG_GALLERYPLUS_ALBUM_SEARCH_PLACEHOLDER : 'Type album name...'; ?>">
                <input type="hidden" name="album_id" id="galleryplus-album-id" value="<?php echo (int)$album_id; ?>">
                <div class="galleryplus-album-dropdown" id="galleryplus-album-dropdown"></div>
            </div>
            <button type="button" id="galleryplus-new-album-btn" class="btn btn-secondary"><?php echo LANG_GALLERYPLUS_NEW_ALBUM ?? '+ New album'; ?></button>
        </div>



        <div id="galleryplus-selected-album" class="galleryplus-selected-album" style="display:none">
            <div class="galleryplus-selected-album-info" id="galleryplus-selected-album-info"></div>
            <button type="button" id="galleryplus-album-edit-btn" class="btn btn-secondary btn-sm galleryplus-album-edit-btn" style="display:none"><?php echo LANG_GALLERYPLUS_ALBUM_EDIT ?? 'Edit album'; ?></button>
            <div class="galleryplus-album-upload-settings" id="galleryplus-album-settings">
                <div class="galleryplus-settings-section">
                    <label class="galleryplus-settings-label"><?php echo LANG_GALLERYPLUS_ALBUM_TITLE ?? 'Album name'; ?></label>
                    <input type="text" id="album-settings-title" class="form-control" value="">
                </div>
                <div class="galleryplus-settings-section">
                    <label class="galleryplus-settings-label"><?php echo LANG_GALLERYPLUS_ALBUM_DESC ?? 'Album description'; ?></label>
                    <textarea id="album-settings-content" class="form-control" rows="3"></textarea>
                </div>
                <?php if (!empty($use_album_tags)) { ?>
                <div class="galleryplus-settings-section">
                    <label class="galleryplus-settings-label"><?php echo LANG_TAGS; ?></label>
                    <input type="text" id="album-settings-tags" class="form-control" placeholder="<?php echo LANG_GALLERYPLUS_TAGS_HINT ?? 'Keywords, comma separated'; ?>">
                </div>
                <?php } ?>
                <?php if (!empty($categories)) { ?>
                <div class="galleryplus-settings-section">
                    <label class="galleryplus-settings-label"><?php echo LANG_GALLERYPLUS_CATEGORY ?? 'Category'; ?></label>
                    <select id="settings-category" class="form-control">
                        <option value="0"><?php echo LANG_GALLERYPLUS_NO_CATEGORY ?? 'No category'; ?></option>
                        <?php foreach ($categories as $cat) { ?>
                            <option value="<?php echo $cat['id']; ?>"><?php html($cat['title']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <?php } ?>
                <div class="galleryplus-settings-section">
                    <label class="galleryplus-settings-label"><?php echo LANG_GALLERYPLUS_ALBUM_PRIVACY ?? 'Privacy'; ?></label>
                    <div class="galleryplus-privacy-options">
                        <div class="custom-control custom-radio">
                            <input type="radio" id="priv-public" name="settings-privacy" value="public" class="custom-control-input" checked>
                            <label class="custom-control-label" for="priv-public"><strong><?php echo LANG_GALLERYPLUS_PRIVACY_PUBLIC ?? 'Public'; ?></strong></label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" id="priv-private" name="settings-privacy" value="private" class="custom-control-input">
                            <label class="custom-control-label" for="priv-private"><strong><?php echo LANG_GALLERYPLUS_PRIVACY_PRIVATE ?? 'Only me'; ?></strong></label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" id="priv-friends" name="settings-privacy" value="friends" class="custom-control-input">
                            <label class="custom-control-label" for="priv-friends"><strong><?php echo LANG_GALLERYPLUS_PRIVACY_FRIENDS ?? 'Friends'; ?></strong></label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" id="priv-users" name="settings-privacy" value="users" class="custom-control-input">
                            <label class="custom-control-label" for="priv-users"><strong><?php echo LANG_GALLERYPLUS_PRIVACY_USERS ?? 'Selected users'; ?></strong></label>
                        </div>
                        <div class="galleryplus-privacy-sub-input" id="priv-users-input" style="display:none">
                            <input type="text" id="settings-privacy-users" class="form-control" placeholder="<?php echo LANG_GALLERYPLUS_PRIVACY_USERS_PLACEHOLDER ?? 'Enter usernames, comma separated'; ?>">
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" id="priv-password" name="settings-privacy" value="password" class="custom-control-input">
                            <label class="custom-control-label" for="priv-password"><strong><?php echo LANG_GALLERYPLUS_PRIVACY_PASSWORD ?? 'Password'; ?></strong></label>
                        </div>
                        <div class="galleryplus-privacy-sub-input" id="priv-password-input" style="display:none">
                            <input type="password" id="settings-password" class="form-control" placeholder="<?php echo LANG_GALLERYPLUS_PRIVACY_PASSWORD_PLACEHOLDER ?? 'Enter password'; ?>">
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" id="priv-adult" name="settings-privacy" value="adult" class="custom-control-input">
                            <label class="custom-control-label" for="priv-adult"><strong><?php echo LANG_GALLERYPLUS_PRIVACY_ADULT ?? '18+'; ?></strong></label>
                        </div>
                    </div>
                </div>
                <div class="galleryplus-settings-section">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" id="settings-allow-upload" value="1" class="custom-control-input">
                        <label class="custom-control-label" for="settings-allow-upload">
                            <strong><?php echo LANG_GALLERYPLUS_ALBUM_ALLOW_UPLOAD ?? 'Allow others to upload'; ?></strong>
                            <br><small class="text-muted"><?php echo LANG_GALLERYPLUS_ALBUM_ALLOW_UPLOAD_HINT ?? 'Other users can upload photos to this album'; ?></small>
                        </label>
                    </div>
                </div>
                <div class="galleryplus-settings-actions">
                    <button type="button" id="album-settings-save" class="btn btn-primary"><?php echo LANG_SAVE; ?></button>
                    <span class="galleryplus-settings-status" id="album-settings-status"></span>
                </div>
            </div>
        </div>

        <div class="galleryplus-upload-list" id="galleryplus-upload-list"></div>

        <div class="galleryplus-upload-actions" id="galleryplus-upload-actions" style="display:none">
            <input type="hidden" name="form_action" value="save">
            <input type="submit" name="submit" value="<?php echo LANG_SAVE; ?>" class="galleryplus-btn-primary">
        </div>
    </form>
</div>

<script>
(function() {
    var dropzone = document.getElementById('galleryplus-dropzone');
    var fileInput = document.getElementById('galleryplus-file-input');
    var uploadList = document.getElementById('galleryplus-upload-list');
    var progressBar = document.getElementById('galleryplus-progress-bar');
    var progressWrap = document.getElementById('galleryplus-upload-progress');
    var uploadActions = document.getElementById('galleryplus-upload-actions');
    var albumSearch = document.getElementById('galleryplus-album-search');
    var albumIdInput = document.getElementById('galleryplus-album-id');
    var albumDropdown = document.getElementById('galleryplus-album-dropdown');
    var newAlbumBtn = document.getElementById('galleryplus-new-album-btn');
    var selectedAlbumDiv = document.getElementById('galleryplus-selected-album');
    var selectedAlbumInfo = document.getElementById('galleryplus-selected-album-info');
    var albumEditBtn = document.getElementById('galleryplus-album-edit-btn');
    var albumSettings = document.getElementById('galleryplus-album-settings');
    var settingsTitle = document.getElementById('album-settings-title');
    var settingsContent = document.getElementById('album-settings-content');
    var settingsSave = document.getElementById('album-settings-save');
    var settingsStatus = document.getElementById('album-settings-status');
    var settingsAllowUpload = document.getElementById('settings-allow-upload');
    var uploadedIds = [];
    var uploading = 0;
    var uploadSubmitBtn = uploadActions ? uploadActions.querySelector('input[type="submit"]') : null;

    function updateSaveButton() {
        if (!uploadSubmitBtn) { return; }
        uploadSubmitBtn.disabled = uploading > 0;
    }

    var allAlbums = <?php echo $albums_json; ?> || [];
    var activeAlbum = null; // {id, title, isNew, nickname}

    // Prevent browser autofill
    albumSearch.setAttribute('readonly', 'readonly');
    albumSearch.addEventListener('focus', function() {
        this.removeAttribute('readonly');
    });
    albumSearch.addEventListener('blur', function() {
        if (!this.value) {
            this.setAttribute('readonly', 'readonly');
        }
    });

    // Privacy sub-inputs
    var privRadios = document.querySelectorAll('#galleryplus-album-settings input[name="settings-privacy"]');
    var privUsersInput = document.getElementById('priv-users-input');
    var privPasswordInput = document.getElementById('priv-password-input');

    privRadios.forEach(function(r) {
        r.addEventListener('change', function() {
            privUsersInput.style.display = this.value === 'users' ? '' : 'none';
            privPasswordInput.style.display = this.value === 'password' ? '' : 'none';
        });
    });

    // Preselect album from URL param
    var preselectedId = parseInt(<?php echo (int)$album_id; ?>);
    if (preselectedId) {
        for (var i = 0; i < allAlbums.length; i++) {
            if (allAlbums[i].id === preselectedId) {
                selectAlbum(allAlbums[i].id, allAlbums[i].title, false, allAlbums[i].label || allAlbums[i].title);
                albumSearch.value = allAlbums[i].title;
                break;
            }
        }
    }

    function filterAlbums(query) {
        if (!query) { return []; }
        var q = query.toLowerCase();
        return allAlbums.filter(function(a) {
            return a.title.toLowerCase().indexOf(q) === 0;
        });
    }

    function renderDropdown(query) {
        var matches = filterAlbums(query);
        if (!matches.length) {
            albumDropdown.style.display = 'none';
            return;
        }
        var html = '';
        for (var i = 0; i < matches.length; i++) {
            var label = matches[i].label || matches[i].title;
            html += '<div class="galleryplus-album-dropdown-item" data-id="' + matches[i].id + '" data-title="' + htmlspecialchars(matches[i].title) + '" data-label="' + htmlspecialchars(label) + '">' + htmlspecialchars(label) + '</div>';
        }
        albumDropdown.innerHTML = html;
        albumDropdown.style.display = 'block';
    }

    function htmlspecialchars(s) {
        if (s === null || s === undefined) return '';
        return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function selectAlbum(id, title, isNew, label) {
        activeAlbum = {id: id, title: title, isNew: isNew};
        albumIdInput.value = id || '';
        var info = '<strong>' + htmlspecialchars(title) + '</strong>';
        if (label && label !== title) {
            info += ' <span class="galleryplus-selected-album-owner">— ' + htmlspecialchars(label.replace(title + ' — ', '')) + '</span>';
        }
        if (!isNew) {
            info = '<div class="galleryplus-selected-album-addto"><?php echo LANG_GALLERYPLUS_ALBUM ?? 'Album'; ?>: ' + info + '</div>';
        }
        selectedAlbumInfo.innerHTML = info;
        selectedAlbumDiv.style.display = 'block';
        if (albumEditBtn) { albumEditBtn.style.display = 'none'; }
        if (isNew) {
            albumSettings.style.display = 'block';
            settingsTitle.value = title || '';
            settingsContent.value = '';
            var settingsTags = document.getElementById('album-settings-tags');
            if (settingsTags) { settingsTags.value = ''; }
            var settingsCategory = document.getElementById('settings-category');
            if (settingsCategory) { settingsCategory.value = '0'; }
            var pubRadio = document.getElementById('priv-public');
            if (pubRadio) { pubRadio.checked = true; }
            if (settingsAllowUpload) { settingsAllowUpload.checked = false; }
            privUsersInput.style.display = 'none';
            privPasswordInput.style.display = 'none';
        } else {
            albumSettings.style.display = 'none';
        }
    }

    function deselectAlbum() {
        activeAlbum = null;
        albumIdInput.value = '';
        selectedAlbumDiv.style.display = 'none';
        if (albumEditBtn) { albumEditBtn.style.display = 'none'; }
        albumSearch.value = '';
    }

    albumSearch.addEventListener('input', function() {
        var val = this.value.trim();
        if (!val) {
            albumDropdown.style.display = 'none';
            deselectAlbum();
            return;
        }
        if (activeAlbum && !activeAlbum.isNew) {
            activeAlbum = null;
            albumIdInput.value = '';
            selectedAlbumDiv.style.display = 'none';
            if (albumEditBtn) { albumEditBtn.style.display = 'none'; }
        }
        renderDropdown(val);
    });

    albumDropdown.addEventListener('click', function(e) {
        var item = e.target.closest('.galleryplus-album-dropdown-item');
        if (!item) return;
        albumSearch.value = item.dataset.title;
        selectAlbum(parseInt(item.dataset.id), item.dataset.title, false, item.dataset.label);
        albumDropdown.style.display = 'none';
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.galleryplus-album-search-wrap')) {
            albumDropdown.style.display = 'none';
        }
    });

    albumSearch.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var first = albumDropdown.querySelector('.galleryplus-album-dropdown-item');
            if (first) { first.click(); }
        }
        if (e.key === 'Escape') {
            albumDropdown.style.display = 'none';
        }
    });

    newAlbumBtn.addEventListener('click', function() {
        deselectAlbum();
        var searchVal = albumSearch.value.trim();
        selectAlbum(null, searchVal || '', true, null);
        settingsTitle.focus();
        albumSearch.disabled = true;
    });

    albumEditBtn.addEventListener('click', function() {
        albumSettings.style.display = 'block';
        if (albumEditBtn) { albumEditBtn.style.display = 'none'; }
        settingsTitle.focus();
    });

    // Save album settings
    settingsSave.addEventListener('click', function() {
        if (!activeAlbum) { return; }

        var title = settingsTitle.value.trim();
        if (!title) { return; }

        settingsStatus.textContent = '<?php echo LANG_GALLERYPLUS_LOADING ?? 'Loading...'; ?>';
        settingsStatus.className = 'galleryplus-settings-status';

        var data = new FormData();
        data.append('title', title);
        data.append('content', settingsContent.value.trim());
        data.append('allow_upload', settingsAllowUpload.checked ? '1' : '0');
        var settingsCategory = document.getElementById('settings-category');
        if (settingsCategory) { data.append('category_id', settingsCategory.value); }
        var settingsTags = document.getElementById('album-settings-tags');
        if (settingsTags) { data.append('tags', settingsTags.value.trim()); }

        var checkedPrivacy = document.querySelector('#galleryplus-album-settings input[name="settings-privacy"]:checked');
        if (checkedPrivacy) {
            data.append('privacy', checkedPrivacy.value);
            if (checkedPrivacy.value === 'users') {
                data.append('privacy_users', document.getElementById('settings-privacy-users').value);
            }
            if (checkedPrivacy.value === 'password') {
                data.append('password', document.getElementById('settings-password').value);
            }
        }

        if (activeAlbum.isNew) {
            data.append('action', 'ajax_create_album');
        } else {
            data.append('action', 'ajax_save_album');
            data.append('album_id', activeAlbum.id);
        }

        var xhr = new XMLHttpRequest();
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r.error) {
                        settingsStatus.textContent = r.error;
                        settingsStatus.className = 'galleryplus-settings-status error';
                        return;
                    }
                    settingsStatus.textContent = '<?php echo LANG_GALLERYPLUS_ALBUM_SAVED ?? 'Album saved'; ?>';
                    settingsStatus.className = 'galleryplus-settings-status success';
                    if (activeAlbum.isNew) {
                        activeAlbum.id = parseInt(r.id);
                        activeAlbum.isNew = false;
                        activeAlbum.title = r.title || title;
                        albumIdInput.value = activeAlbum.id;
                        albumSearch.disabled = false;
                        albumSearch.value = activeAlbum.title;
                    } else {
                        activeAlbum.title = r.title || settingsTitle.value;
                    }
                    selectedAlbumInfo.innerHTML = '<div class="galleryplus-selected-album-addto"><?php echo LANG_GALLERYPLUS_ALBUM ?? 'Album'; ?>: <strong>' + htmlspecialchars(activeAlbum.title) + '</strong></div>';
                    albumSettings.style.display = 'none';
                    if (albumEditBtn) { albumEditBtn.style.display = 'inline-block'; }
                } catch(e) {
                    settingsStatus.textContent = 'Error';
                    settingsStatus.className = 'galleryplus-settings-status error';
                }
            } else {
                settingsStatus.textContent = 'Server error';
                settingsStatus.className = 'galleryplus-settings-status error';
            }
            setTimeout(function() { settingsStatus.textContent = ''; }, 3000);
        };
        xhr.open('POST', '<?php echo href_to('galleryplus', 'upload'); ?>', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send(data);
    });

    dropzone.addEventListener('click', function() { fileInput.click(); });

    dropzone.addEventListener('dragover', function(e) {
        e.preventDefault();
        dropzone.classList.add('dragover');
    });
    dropzone.addEventListener('dragleave', function() {
        dropzone.classList.remove('dragover');
    });
    dropzone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropzone.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    });

    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
        this.value = '';
    });

    function truncateTitle(name, maxLen) {
        if (name.length <= maxLen) return name;
        return name.substring(0, maxLen) + '...';
    }

    function handleFiles(files) {
        var valid = [];
        for (var i = 0; i < files.length; i++) {
            if (files[i].type.match(/^image\/(jpeg|png|gif|webp)$/)) {
                valid.push(files[i]);
            }
        }
        if (!valid.length) { return; }
        dropzone.style.display = 'none';
        uploadActions.style.display = 'block';
        valid.forEach(function(file) { uploadFile(file); });
    }

    function getFileTitle(file) {
        var name = file.name;
        var dot = name.lastIndexOf('.');
        if (dot > 0) { name = name.substring(0, dot); }
        name = name.replace(/[-_]+/g, ' ').trim();
        return truncateTitle(name, 15);
    }

    function uploadFile(file) {
        uploading++;
        progressWrap.style.display = 'block';
        updateSaveButton();

        var autoTitle = getFileTitle(file);

        var item = document.createElement('div');
        item.className = 'galleryplus-upload-item';
        var photoTagId = 'galleryplus-photo-tags-' + Date.now();
        var tagsField = <?php echo !empty($use_photo_tags) ? 'true' : 'false'; ?> ? '<input type="text" id="' + photoTagId + '" class="galleryplus-upload-item-tags" placeholder="' + (<?php echo json_encode(LANG_GALLERYPLUS_TAGS_HINT ?? 'Tags'); ?>) + '">' : '';
        var tagsFieldInit = <?php echo !empty($use_photo_tags) ? 'true' : 'false'; ?>;
        item.innerHTML = '<div class="galleryplus-upload-item-preview"><div class="galleryplus-upload-item-spinner"></div></div>'
            + '<div class="galleryplus-upload-item-info">'
            + '<input type="text" name="title[' + Date.now() + ']" class="galleryplus-upload-item-title" placeholder="' + (<?php echo json_encode(LANG_GALLERYPLUS_PHOTO_TITLE ?? 'Photo title'); ?>) + '" value="' + htmlspecialchars(autoTitle) + '">'
            + tagsField
            + '<div class="galleryplus-upload-item-error"></div>'
            + '<div class="galleryplus-upload-item-notice" style="display:none"></div>'
            + '</div>'
            + '<div class="galleryplus-upload-item-progress"><div class="galleryplus-upload-item-bar"></div></div>';
        uploadList.appendChild(item);
        if (tagsFieldInit && typeof initAutocomplete === 'function') {
            initAutocomplete(photoTagId, false, '/tags/autocomplete', false, ', ');
        }

        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = item.querySelector('.galleryplus-upload-item-preview');
            preview.innerHTML = '<img src="' + e.target.result + '" class="galleryplus-upload-item-thumb">';
        };
        reader.readAsDataURL(file);

        var xhr = new XMLHttpRequest();
        var formData = new FormData();
        formData.append('file', file);
        formData.append('album_id', albumIdInput.value || '');

        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                var pct = (e.loaded / e.total) * 100;
                item.querySelector('.galleryplus-upload-item-bar').style.width = pct + '%';
            }
        };

            xhr.onload = function() {
            uploading--;
            if (uploading === 0) { progressWrap.style.display = 'none'; }
            updateSaveButton();

            if (xhr.status === 200) {
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r.success !== false && r.id) {
                        uploadedIds.push(r.id);
                        var preview = item.querySelector('.galleryplus-upload-item-preview');
                        preview.innerHTML = '<img src="' + (r.thumb || r.url) + '" class="galleryplus-upload-item-thumb">';
                        var input = item.querySelector('.galleryplus-upload-item-title');
                        input.name = 'title[' + r.id + ']';
                        var tagsInput = item.querySelector('.galleryplus-upload-item-tags');
                        if (tagsInput) { tagsInput.name = 'tags_' + r.id; }
                        var hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'photos[]';
                        hidden.value = r.id;
                        item.appendChild(hidden);
                        item.querySelector('.galleryplus-upload-item-bar').style.width = '100%';
                        item.querySelector('.galleryplus-upload-item-bar').style.background = '#4caf50';
                        if (r.pending) {
                            var notice = item.querySelector('.galleryplus-upload-item-notice');
                            notice.style.display = 'block';
                            notice.textContent = <?php echo json_encode(LANG_GALLERYPLUS_PENDING_NOTICE ?? 'Photo will be published after moderation'); ?>;
                        }
                        return;
                    }
                    if (r.error) {
                        item.querySelector('.galleryplus-upload-item-error').textContent = r.error;
                    }
                } catch(e) {}
            }
            item.querySelector('.galleryplus-upload-item-bar').style.background = '#f44336';
        };

        xhr.onerror = function() {
            uploading--;
            if (uploading === 0) { progressWrap.style.display = 'none'; }
            updateSaveButton();
            item.querySelector('.galleryplus-upload-item-bar').style.background = '#f44336';
        };

        xhr.open('POST', '<?php echo href_to('galleryplus', 'upload'); ?>', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send(formData);
    }
})();
</script>
<?php if (!empty($use_album_tags) || !empty($use_photo_tags)) { ?>
<?php $this->addTplJSName('jquery-ui'); $this->addTplCSSName('jquery-ui'); $this->addTplJSName('fields/string_input'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($use_album_tags)) { ?>
    if (typeof initAutocomplete === 'function') {
        initAutocomplete('album-settings-tags', false, '/tags/autocomplete', false, ', ');
    }
    <?php } ?>
});
</script>
<?php } ?>
