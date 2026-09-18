<?php
    $this->addTplCSSName('galleryplus');
    $this->setPageTitle(sprintf(LANG_GALLERYPLUS_ALBUM_EDIT, $album['title']));
    $this->addBreadcrumb(LANG_GALLERYPLUS_TITLE, href_to('galleryplus'));
    $this->addBreadcrumb($album['title'], href_to('galleryplus', 'album', [$album['slug']]) . '.html');
    $this->addBreadcrumb(LANG_GALLERYPLUS_ALBUM_EDIT);

    $privacy = $album['privacy'] ?? 'public';
    if (!empty($album['is_paid'])) { $privacy = 'paid'; }
    $privacy_password = '';
    $privacy_users = '';
    if ($privacy === 'users' && !empty($album['privacy_users'])) {
        $ids = explode(',', $album['privacy_users']);
        $names = [];
        $users_model = cmsCore::getModel('users');
        foreach ($ids as $uid) {
            $u = $users_model->getUser($uid);
            if ($u) { $names[] = $u['nickname']; }
        }
        $privacy_users = implode(', ', $names);
    }
?>
<div class="galleryplus-album-edit">
    <h1><?php echo sprintf(LANG_GALLERYPLUS_ALBUM_EDIT, htmlspecialchars($album['title'] ?? '')); ?></h1>

    <form action="<?php echo href_to('galleryplus', 'album', ['edit', $album['slug']]) . '.html'; ?>" method="post" class="galleryplus-album-edit-form">
        <?php echo html_csrf_token(); ?>

        <div class="form-group">
            <label for="title"><?php echo LANG_GALLERYPLUS_ALBUM_TITLE; ?></label>
            <input type="text" id="title" name="title" class="form-control" value="<?php html($album['title']); ?>" required>
        </div>

        <div class="form-group">
            <label for="content"><?php echo LANG_GALLERYPLUS_ALBUM_DESC; ?></label>
            <textarea id="content" name="content" class="form-control" rows="4"><?php html($album['content'] ?? ''); ?></textarea>
        </div>

        <?php if (!empty($categories)) { ?>
        <div class="form-group">
            <label for="category_id"><?php echo LANG_GALLERYPLUS_CATEGORY; ?></label>
            <select id="category_id" name="category_id" class="form-control">
                <option value="0"><?php echo LANG_GALLERYPLUS_NO_CATEGORY; ?></option>
                <?php foreach ($categories as $cat) { ?>
                    <option value="<?php echo $cat['id']; ?>"<?php echo ($album['category_id'] ?? 0) == $cat['id'] ? ' selected' : ''; ?>><?php html($cat['title']); ?></option>
                <?php } ?>
            </select>
        </div>
        <?php } ?>

        <?php if (!empty($use_album_tags)) { ?>
        <div class="form-group">
            <label for="tags"><?php echo LANG_TAGS; ?></label>
            <input type="text" id="tags" name="tags" class="form-control" value="<?php echo htmlspecialchars(implode(', ', $album_tags ?? [])); ?>" placeholder="<?php echo LANG_GALLERYPLUS_TAGS_HINT; ?>">
        </div>
        <?php } ?>

        <div class="form-group">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" id="allow_upload" name="allow_upload" value="1" class="custom-control-input" <?php echo !empty($album['allow_upload']) ? 'checked' : ''; ?>>
                <label class="custom-control-label" for="allow_upload">
                    <strong><?php echo LANG_GALLERYPLUS_ALBUM_ALLOW_UPLOAD; ?></strong>
                    <br><small class="text-muted"><?php echo LANG_GALLERYPLUS_ALBUM_ALLOW_UPLOAD_HINT; ?></small>
                </label>
            </div>
        </div>

        <div class="form-group galleryplus-album-edit-privacy">
            <label><?php echo LANG_GALLERYPLUS_ALBUM_PRIVACY; ?></label>

            <div class="custom-control custom-radio">
                <input type="radio" id="privacy-public" name="privacy" value="public" class="custom-control-input galleryplus-privacy-radio" <?php echo $privacy === 'public' ? 'checked' : ''; ?>>
                <label class="custom-control-label" for="privacy-public">
                    <strong><?php echo LANG_GALLERYPLUS_PRIVACY_PUBLIC; ?></strong>
                    <br><small class="text-muted"><?php echo LANG_GALLERYPLUS_PRIVACY_PUBLIC_HINT; ?></small>
                </label>
            </div>

            <div class="custom-control custom-radio">
                <input type="radio" id="privacy-private" name="privacy" value="private" class="custom-control-input galleryplus-privacy-radio" <?php echo $privacy === 'private' ? 'checked' : ''; ?>>
                <label class="custom-control-label" for="privacy-private">
                    <strong><?php echo LANG_GALLERYPLUS_PRIVACY_PRIVATE; ?></strong>
                    <br><small class="text-muted"><?php echo LANG_GALLERYPLUS_PRIVACY_PRIVATE_HINT; ?></small>
                </label>
            </div>

            <div class="custom-control custom-radio">
                <input type="radio" id="privacy-friends" name="privacy" value="friends" class="custom-control-input galleryplus-privacy-radio" <?php echo $privacy === 'friends' ? 'checked' : ''; ?>>
                <label class="custom-control-label" for="privacy-friends">
                    <strong><?php echo LANG_GALLERYPLUS_PRIVACY_FRIENDS; ?></strong>
                    <br><small class="text-muted"><?php echo LANG_GALLERYPLUS_PRIVACY_FRIENDS_HINT; ?></small>
                </label>
            </div>

            <div class="custom-control custom-radio">
                <input type="radio" id="privacy-users" name="privacy" value="users" class="custom-control-input galleryplus-privacy-radio" <?php echo $privacy === 'users' ? 'checked' : ''; ?>>
                <label class="custom-control-label" for="privacy-users">
                    <strong><?php echo LANG_GALLERYPLUS_PRIVACY_USERS; ?></strong>
                    <br><small class="text-muted"><?php echo LANG_GALLERYPLUS_PRIVACY_USERS_HINT; ?></small>
                </label>
            </div>

            <div class="galleryplus-privacy-users-input" id="galleryplus-privacy-users-input" style="<?php echo $privacy === 'users' ? '' : 'display:none;'; ?>">
                <input type="text" name="privacy_users" id="galleryplus-privacy-users" class="form-control" value="<?php html($privacy_users); ?>" placeholder="<?php echo LANG_GALLERYPLUS_PRIVACY_USERS_PLACEHOLDER; ?>">
                <small class="text-muted"><?php echo LANG_GALLERYPLUS_PRIVACY_USERS_HELP; ?></small>
            </div>

            <div class="custom-control custom-radio">
                <input type="radio" id="privacy-password" name="privacy" value="password" class="custom-control-input galleryplus-privacy-radio" <?php echo $privacy === 'password' ? 'checked' : ''; ?>>
                <label class="custom-control-label" for="privacy-password">
                    <strong><?php echo LANG_GALLERYPLUS_PRIVACY_PASSWORD; ?></strong>
                    <br><small class="text-muted"><?php echo LANG_GALLERYPLUS_PRIVACY_PASSWORD_HINT; ?></small>
                </label>
            </div>

            <div class="galleryplus-privacy-password-input" id="galleryplus-privacy-password-input" style="<?php echo $privacy === 'password' ? '' : 'display:none;'; ?>">
                <input type="password" name="password" class="form-control" placeholder="<?php echo LANG_GALLERYPLUS_PRIVACY_PASSWORD_PLACEHOLDER; ?>">
                <small class="text-muted"><?php echo LANG_GALLERYPLUS_PRIVACY_PASSWORD_HELP; ?></small>
            </div>

            <div class="custom-control custom-radio">
                <input type="radio" id="privacy-adult" name="privacy" value="adult" class="custom-control-input galleryplus-privacy-radio" <?php echo $privacy === 'adult' ? 'checked' : ''; ?>>
                <label class="custom-control-label" for="privacy-adult">
                    <strong><?php echo LANG_GALLERYPLUS_PRIVACY_ADULT; ?></strong>
                    <br><small class="text-muted"><?php echo LANG_GALLERYPLUS_PRIVACY_ADULT_HINT; ?></small>
                </label>
            </div>

            <div class="custom-control custom-radio">
                <input type="radio" id="privacy-paid" name="privacy" value="paid" class="custom-control-input galleryplus-privacy-radio" <?php echo $privacy === 'paid' ? 'checked' : ''; ?>>
                <label class="custom-control-label" for="privacy-paid">
                    <strong><?php echo LANG_GALLERYPLUS_PRIVACY_PAID; ?></strong>
                    <br><small class="text-muted"><?php echo LANG_GALLERYPLUS_PRIVACY_PAID_HINT; ?></small>
                </label>
            </div>

            <div class="galleryplus-album-edit-previews" id="galleryplus-album-edit-previews" style="<?php echo $privacy === 'paid' ? '' : 'display:none;'; ?>">
                <div class="form-group">
                    <label><?php echo defined('LANG_GALLERYPLUS_BILLING_ALBUM_PREVIEW_LABEL') ? LANG_GALLERYPLUS_BILLING_ALBUM_PREVIEW_LABEL : 'Какие фото показывать без блюра'; ?></label>
                    <?php if (!empty($edit_photos)) { ?>
                        <div class="galleryplus-edit-previews-grid">
                            <?php foreach ($edit_photos as $fp) { ?>
                                <label class="galleryplus-edit-preview-photo<?php echo !empty($fp['album_preview']) ? ' is-preview' : ''; ?>">
                                    <img src="<?php echo $fp['url_thumb']; ?>" alt="" loading="lazy">
                                    <span class="galleryplus-edit-preview-check">
                                        <input type="checkbox" name="preview_photos[]" value="<?php echo $fp['id']; ?>" <?php echo !empty($fp['album_preview']) ? 'checked' : ''; ?>>
                                        <span class="galleryplus-checkbox"></span>
                                    </span>
                                </label>
                            <?php } ?>
                        </div>
                        <small class="text-muted"><?php echo defined('LANG_GALLERYPLUS_BILLING_ALBUM_PREVIEW_HINT') ? LANG_GALLERYPLUS_BILLING_ALBUM_PREVIEW_HINT : 'Отмеченные фото будут видны всем, остальные — под блюром до покупки доступа'; ?></small>
                    <?php } else { ?>
                        <small class="text-muted"><?php echo defined('LANG_GALLERYPLUS_BILLING_ALBUM_PREVIEW_EMPTY') ? LANG_GALLERYPLUS_BILLING_ALBUM_PREVIEW_EMPTY : 'В альбоме пока нет фото. После загрузки вернитесь сюда, чтобы выбрать открытые фото.'; ?></small>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="galleryplus-album-edit-actions">
            <button type="submit" name="submit" class="btn btn-primary"><?php echo LANG_SAVE; ?></button>
            <a href="<?php echo href_to('galleryplus', 'album', [$album['slug']]) . '.html'; ?>" class="btn btn-secondary"><?php echo LANG_CANCEL; ?></a>
        </div>
    </form>

    <hr>

    <div class="galleryplus-album-edit-delete">
        <form action="<?php echo href_to('galleryplus', 'album', ['edit', $album['slug']]) . '.html'; ?>" method="post" onsubmit="return confirm('<?php echo LANG_GALLERYPLUS_ALBUM_DELETE_CONFIRM; ?>')">
            <?php echo html_csrf_token(); ?>
            <input type="hidden" name="action" value="delete">
            <button type="submit" name="submit" class="btn btn-danger"><?php echo LANG_GALLERYPLUS_ALBUM_DELETE; ?></button>
        </form>
    </div>
</div>

<script>
(function() {
    var radios = document.querySelectorAll('.galleryplus-privacy-radio');
    var usersInput = document.getElementById('galleryplus-privacy-users-input');
    var passwordInput = document.getElementById('galleryplus-privacy-password-input');
    var previewsBlock = document.getElementById('galleryplus-album-edit-previews');

    function toggleFields() {
        var checked = document.querySelector('.galleryplus-privacy-radio:checked');
        if (!checked) return;
        var val = checked.value;
        usersInput.style.display = val === 'users' ? '' : 'none';
        passwordInput.style.display = val === 'password' ? '' : 'none';
        if (previewsBlock) {
            previewsBlock.style.display = val === 'paid' ? '' : 'none';
        }
    }

    radios.forEach(function(r) {
        r.addEventListener('change', toggleFields);
    });
})();
</script>
<?php if (!empty($use_album_tags)) { ?>
<?php $this->addTplJSName('jquery-ui'); $this->addTplCSSName('jquery-ui'); $this->addTplJSName('fields/string_input'); ?>
<?php ob_start(); ?>
<script>
initAutocomplete('tags', true, '/tags/autocomplete', false, ', ');
</script>
<?php $this->addBottom(ob_get_clean()); ?>
<?php } ?>
