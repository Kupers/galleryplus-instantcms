<?php $this->addBreadcrumb($tab['title']); ?>

<?php if (empty($albums)) { ?>
    <div class="galleryplus-empty"><?php echo LANG_GALLERYPLUS_NO_ALBUMS ?? 'No albums yet.'; ?></div>
<?php } else { ?>
    <div class="galleryplus-albums-grid jsly" id="galleryplus-albums-grid">
        <?php foreach ($albums as $a) {
            $privacy = $a['privacy'] ?? '';
            $is_adult_album = $privacy === 'adult';
            $is_protected = !empty($a['is_protected']);
            $show_blur = !$is_owner && $is_protected;
            $privacy_labels = [
                'password' => LANG_GALLERYPLUS_PRIVACY_PASSWORD ?? 'By password',
                'friends'  => LANG_GALLERYPLUS_PRIVACY_FRIENDS ?? 'Friends',
                'users'    => LANG_GALLERYPLUS_PRIVACY_USERS ?? 'Selected users',
                'private'  => LANG_GALLERYPLUS_PRIVACY_PRIVATE ?? 'Only me',
            ];
            $lock_label = $privacy_labels[$privacy] ?? LANG_GALLERYPLUS_ALBUM_LOCKED ?? 'Protected';
        ?>
            <a href="<?php echo $a['url']; ?>" class="galleryplus-album-card<?php echo $show_blur ? ' galleryplus-album-card--protected' . ($is_adult_album ? ' galleryplus-album-card--adult' : '') : ''; ?>" data-album-id="<?php echo $a['id']; ?>">
                    <?php if ($is_owner) { ?>
                        <label class="galleryplus-checkbox-wrap galleryplus-checkbox-wrap--album">
                            <input type="checkbox" class="galleryplus-select-cb galleryplus-select-cb--album" data-id="<?php echo $a['id']; ?>">
                            <span class="galleryplus-checkbox"></span>
                        </label>
                    <?php } ?>
                <div class="galleryplus-album-cover">
                    <?php if ($show_blur) { ?>
                        <img src="<?php echo $a['cover_url'] ?: ''; ?>" alt="" loading="lazy" class="galleryplus-blurred" data-width="<?php echo $a['cover_width'] ?? 0; ?>" data-height="<?php echo $a['cover_height'] ?? 0; ?>" style="<?php echo $a['cover_url'] ? '' : 'display:none;'; ?>">
                        <?php if ($is_adult_album) { ?>
                            <div class="galleryplus-adult-badge">18+</div>
                        <?php } else { ?>
                            <div class="galleryplus-album-lock-badge">&#128274;</div>
                        <?php } ?>
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
                            <?php if ($is_adult_album) { ?>
                                <span class="galleryplus-album-adult-label">18+</span>
                            <?php } else { ?>
                                <span class="galleryplus-album-lock-label">&#128274; <?php echo $lock_label; ?></span>
                            <?php } ?>
                        <?php } ?>
                        <span class="galleryplus-album-count"><?php echo $a['photo_count']; ?> <?php echo LANG_GALLERYPLUS_PHOTOS ?? 'photos'; ?></span>
                        <span class="galleryplus-album-likes">&#10084; <?php echo $a['likes_count'] ?? 0; ?></span>
                        <?php if (!empty($a['user']['nickname'])) { ?>
                            <span class="galleryplus-album-user"><?php echo htmlspecialchars($a['user']['nickname']); ?></span>
                        <?php } ?>
                    </div>
                </div>
            </a>
        <?php } ?>
    </div>
<?php } ?>
