<?php $this->addBreadcrumb($tab['title']); ?>

<?php if (empty($albums)) { ?>
    <div class="galleryplus-empty"><?php echo LANG_GALLERYPLUS_NO_ALBUMS ?? 'No albums yet.'; ?></div>
<?php } else { ?>
    <div class="galleryplus-albums-grid jsly" id="galleryplus-albums-grid">
        <?php foreach ($albums as $a) {
            $is_adult_album = ($a['privacy'] ?? '') === 'adult';
            $show_blur = $is_adult_album && !$is_owner;
        ?>
            <a href="<?php echo $a['url']; ?>" class="galleryplus-album-card<?php echo $show_blur ? ' galleryplus-album-card--adult' : ''; ?>" data-album-id="<?php echo $a['id']; ?>">
                <label class="galleryplus-checkbox-wrap galleryplus-checkbox-wrap--album">
                    <input type="checkbox" class="galleryplus-select-cb galleryplus-select-cb--album" data-id="<?php echo $a['id']; ?>">
                    <span class="galleryplus-checkbox"></span>
                </label>
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
                    </div>
                </div>
            </a>
        <?php } ?>
    </div>
<?php } ?>
