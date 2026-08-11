<?php $this->setPageTitle(LANG_GALLERYPLUS_PENDING); ?>

<h1><?php echo LANG_GALLERYPLUS_PENDING; ?></h1>

<?php if ($total > 0) { ?>

    <div class="galleryplus-pending-actions">
        <a class="btn btn-success" href="#" onclick="return approveSelected()"><?php echo LANG_GALLERYPLUS_APPROVE_SELECTED; ?></a>
        <a class="btn btn-danger" href="#" onclick="return rejectSelected()"><?php echo LANG_GALLERYPLUS_REJECT_SELECTED; ?></a>
    </div>

    <table class="table table-striped">
        <thead>
            <tr>
                <th style="width:32px"><input type="checkbox" id="gp-pending-check-all" title="<?php echo LANG_GALLERYPLUS_SELECT_ALL; ?>"></th>
                <th>ID</th>
                <th><?php echo LANG_PREVIEW; ?></th>
                <th><?php echo LANG_TITLE; ?></th>
                <th><?php echo LANG_AUTHOR; ?></th>
                <th><?php echo LANG_DATE_PUB; ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($photos as $photo) { ?>
                <tr id="photo-<?php echo $photo['id']; ?>">
                    <td><input type="checkbox" class="gp-pending-check" value="<?php echo $photo['id']; ?>"></td>
                    <td><?php echo $photo['id']; ?></td>
                    <td>
                        <?php if ($photo['url_thumb']) { ?>
                            <div class="galleryplus-pending-thumb">
                                <img src="<?php echo $photo['url_thumb']; ?>" alt="">
                            </div>
                        <?php } ?>
                    </td>
                    <td><?php html($photo['title'] ?: '—'); ?></td>
                    <td>
                        <a href="<?php echo href_to_profile($photo['user']); ?>">
                            <?php html($photo['user']['nickname']); ?>
                        </a>
                    </td>
                    <td><?php echo html_date($photo['date_pub'], true); ?></td>
                    <td class="actions">
                        <a class="btn btn-success" href="#" onclick="return approvePhoto(<?php echo $photo['id']; ?>)">
                            <?php echo LANG_GALLERYPLUS_APPROVE; ?>
                        </a>
                        <a class="btn btn-danger" href="#" onclick="return rejectPhoto(<?php echo $photo['id']; ?>)">
                            <?php echo LANG_GALLERYPLUS_REJECT; ?>
                        </a>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <?php echo html_pagebar($page, $perpage, $total, href_to('admin', 'controllers', ['edit', 'galleryplus', 'pending', '%s'])); ?>

    <script>
    var gpApproveUrl = '<?php echo href_to('admin', 'controllers', ['edit', 'galleryplus', 'approve']); ?>';
    var gpDeleteUrl  = '<?php echo href_to('admin', 'controllers', ['edit', 'galleryplus', 'delete']); ?>';

    function gpSend(url, id, done) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function() {
            if (xhr.status === 200) {
                var tr = document.getElementById('photo-' + id);
                if (tr) { tr.style.display = 'none'; }
                if (done) { done(); }
            }
        };
        xhr.send('id=' + id);
    }

    function approvePhoto(id) {
        gpSend(gpApproveUrl, id);
        return false;
    }

    function rejectPhoto(id) {
        if (!confirm('<?php echo LANG_GALLERYPLUS_CONFIRM_DELETE; ?>')) { return false; }
        gpSend(gpDeleteUrl, id);
        return false;
    }

    function gpGetSelected() {
        var selected = [];
        var boxes = document.querySelectorAll('.gp-pending-check:checked');
        for (var i = 0; i < boxes.length; i++) { selected.push(boxes[i].value); }
        return selected;
    }

    function gpBulk(url, need_confirm) {
        var ids = gpGetSelected();
        if (!ids.length) { return false; }
        if (need_confirm && !confirm('<?php echo LANG_GALLERYPLUS_CONFIRM_DELETE; ?>')) { return false; }
        for (var i = 0; i < ids.length; i++) {
            gpSend(url, ids[i]);
        }
        return false;
    }

    function approveSelected() {
        return gpBulk(gpApproveUrl, false);
    }

    function rejectSelected() {
        return gpBulk(gpDeleteUrl, true);
    }

    document.addEventListener('DOMContentLoaded', function() {
        var checkAll = document.getElementById('gp-pending-check-all');
        if (checkAll) {
            checkAll.addEventListener('change', function() {
                var boxes = document.querySelectorAll('.gp-pending-check');
                for (var i = 0; i < boxes.length; i++) { boxes[i].checked = checkAll.checked; }
            });
        }
    });
    </script>

<?php } else { ?>
    <p><?php echo LANG_LIST_EMPTY; ?></p>
<?php } ?>
