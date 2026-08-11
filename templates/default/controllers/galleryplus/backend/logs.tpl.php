<?php $this->setPageTitle(LANG_GALLERYPLUS_LOGS); ?>

<h1><?php echo LANG_GALLERYPLUS_LOGS; ?></h1>

<?php if ($logging_enabled) { ?>
    <div class="alert alert-info">
        <?php echo LANG_GALLERYPLUS_LOG_ENABLED; ?>
    </div>
<?php } else { ?>
    <div class="alert alert-warning">
        <?php echo LANG_GALLERYPLUS_LOG_DISABLED; ?>
        <a href="<?php echo href_to('admin', 'controllers', ['edit', 'galleryplus', 'options']); ?>"><?php echo LANG_GALLERYPLUS_LOG_SETTINGS_LINK; ?></a>
    </div>
<?php } ?>

<?php if ($total > 0) { ?>

    <form action="<?php echo href_to('admin', 'controllers', ['edit', 'galleryplus', 'log_delete_all']); ?>" method="post" class="mb-3">
        <?php echo html_csrf_token(); ?>
        <button type="submit" name="submit" class="btn btn-danger" onclick="return confirm('<?php echo LANG_GALLERYPLUS_LOG_DELETE_ALL_CONFIRM; ?>')">
            <?php echo LANG_GALLERYPLUS_LOG_DELETE_ALL; ?>
        </button>
    </form>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th><?php echo LANG_GALLERYPLUS_LOG_COL_ACTION; ?></th>
                <th><?php echo LANG_GALLERYPLUS_LOG_COL_OBJECT; ?></th>
                <th><?php echo LANG_GALLERYPLUS_LOG_COL_TITLE; ?></th>
                <th><?php echo LANG_GALLERYPLUS_LOG_COL_OWNER; ?></th>
                <th><?php echo LANG_GALLERYPLUS_LOG_COL_USER; ?></th>
                <th><?php echo LANG_GALLERYPLUS_LOG_COL_DATE; ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log) {
                $action_label = $log['action'] === 'delete'
                    ? '<span class="text-danger">' . LANG_GALLERYPLUS_LOG_ACTION_DELETE . '</span>'
                    : '<span class="text-primary">' . LANG_GALLERYPLUS_LOG_ACTION_EDIT . '</span>';
                $object_label = $log['target_type'] === 'album'
                    ? LANG_GALLERYPLUS_LOG_OBJECT_ALBUM
                    : LANG_GALLERYPLUS_LOG_OBJECT_PHOTO;
            ?>
                <tr>
                    <td><?php echo $log['id']; ?></td>
                    <td><?php echo $action_label; ?></td>
                    <td><?php echo $object_label; ?><?php if ($log['target_id']) { ?> #<?php echo (int)$log['target_id']; ?><?php } ?></td>
                    <td><?php html($log['title'] ?: '—'); ?></td>
                    <td><?php html($log['owner_name']); ?></td>
                    <td><?php html($log['user_name']); ?></td>
                    <td><?php echo html_date($log['date_pub'], true); ?></td>
                    <td class="actions">
                        <a class="btn btn-danger" href="<?php echo href_to('admin', 'controllers', ['edit', 'galleryplus', 'log_delete', $log['id']]); ?>" onclick="return confirm('<?php echo LANG_GALLERYPLUS_LOG_DELETE_CONFIRM; ?>')">
                            <?php echo LANG_DELETE; ?>
                        </a>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <?php echo html_pagebar($page, $perpage, $total, href_to('admin', 'controllers', ['edit', 'galleryplus', 'logs', '%s'])); ?>

<?php } else { ?>
    <p><?php echo LANG_GALLERYPLUS_LOG_EMPTY; ?></p>
<?php } ?>
