<style>
    .icms-menu-hovered li.galleryplus-cleanup-tab > a {
        color: #dc3545 !important;
    }
    .icms-menu-hovered li.galleryplus-cleanup-tab.active > a {
        color: #fff !important;
        background-color: #dc3545 !important;
    }
    .galleryplus-cleanup-desc {
        font-size: 0.9em;
        color: #666;
    }
</style>

<div class="card">
    <div class="card-body">
        <h3 class="text-danger"><?php echo LANG_GALLERYPLUS_CLEANUP; ?></h3>
        <p class="galleryplus-cleanup-desc"><?php echo LANG_GALLERYPLUS_CLEANUP_HINT; ?></p>

        <div class="row mb-4">
            <div class="col-sm-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <div class="h2 mb-0"><?php echo $stats['photos']; ?></div>
                        <small><?php echo LANG_GALLERYPLUS_PHOTOS; ?></small>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <div class="h2 mb-0"><?php echo $stats['albums']; ?></div>
                        <small><?php echo LANG_GALLERYPLUS_ALBUMS; ?></small>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <div class="h2 mb-0"><?php echo $stats['likes']; ?></div>
                        <small><?php echo LANG_GALLERYPLUS_LIKES; ?></small>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <div class="h2 mb-0"><?php echo $total_size ? files_format_bytes($total_size) : '0 ' . LANG_GALLERYPLUS_CLEANUP_BYTES; ?></div>
                        <small><?php echo LANG_GALLERYPLUS_CLEANUP_DISK; ?></small>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($stats['photos'] || $stats['albums'] || $stats['likes']) { ?>
            <form action="" method="post">
                <?php echo html_csrf_token(); ?>

                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="delete_photos" name="delete_photos" value="1">
                        <label class="custom-control-label" for="delete_photos">
                            <strong class="text-danger"><?php echo LANG_GALLERYPLUS_CLEANUP_DELETE_PHOTOS; ?></strong>
                            <br><small class="text-muted"><?php echo sprintf(LANG_GALLERYPLUS_CLEANUP_DELETE_PHOTOS_HINT, $stats['photos']); ?></small>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="delete_albums" name="delete_albums" value="1">
                        <label class="custom-control-label" for="delete_albums">
                            <strong class="text-danger"><?php echo LANG_GALLERYPLUS_CLEANUP_DELETE_ALBUMS; ?></strong>
                            <br><small class="text-muted"><?php echo sprintf(LANG_GALLERYPLUS_CLEANUP_DELETE_ALBUMS_HINT, $stats['albums']); ?></small>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="delete_likes" name="delete_likes" value="1">
                        <label class="custom-control-label" for="delete_likes">
                            <strong class="text-danger"><?php echo LANG_GALLERYPLUS_CLEANUP_DELETE_LIKES; ?></strong>
                            <br><small class="text-muted"><?php echo sprintf(LANG_GALLERYPLUS_CLEANUP_DELETE_LIKES_HINT, $stats['likes']); ?></small>
                        </label>
                    </div>
                </div>

                <hr>

                <button type="submit" name="submit" class="btn btn-danger" onclick="return confirm('<?php echo LANG_GALLERYPLUS_CLEANUP_CONFIRM; ?>')">
                    <?php echo LANG_GALLERYPLUS_CLEANUP_EXECUTE; ?>
                </button>
            </form>
        <?php } else { ?>
            <p class="text-muted"><?php echo LANG_GALLERYPLUS_CLEANUP_EMPTY; ?></p>
        <?php } ?>
    </div>
</div>
