<?php $this->setPageTitle(LANG_GALLERYPLUS_CATEGORIES); ?>

<h1><?php echo LANG_GALLERYPLUS_CATEGORIES; ?></h1>

<p>
    <a class="btn btn-primary" href="<?php echo href_to('admin', 'controllers', ['edit', 'galleryplus', 'category_add']); ?>">
        + <?php echo LANG_GALLERYPLUS_CATEGORY_ADD; ?>
    </a>
</p>

<?php if ($total > 0) { ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th><?php echo LANG_TITLE; ?></th>
                <th><?php echo LANG_GALLERYPLUS_CATEGORY_SLUG; ?></th>
                <th><?php echo LANG_GALLERYPLUS_SORTING_ORDER; ?></th>
                <th><?php echo LANG_GALLERYPLUS_ALBUMS; ?></th>
                <th><?php echo LANG_GALLERYPLUS_IS_HIDDEN; ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $cat) { ?>
                <tr>
                    <td><?php echo $cat['id']; ?></td>
                    <td><?php html($cat['title']); ?></td>
                    <td><code><?php html($cat['slug']); ?></code></td>
                    <td><?php echo $cat['ordering']; ?></td>
                    <td><?php echo $cat['items_count']; ?></td>
                    <td><?php echo $cat['is_hidden'] ? LANG_YES : LANG_NO; ?></td>
                    <td class="actions">
                        <a class="btn btn-primary" href="<?php echo href_to('admin', 'controllers', ['edit', 'galleryplus', 'category_edit', $cat['id']]); ?>">
                            <?php echo LANG_GALLERYPLUS_EDIT; ?>
                        </a>
                        <a class="btn btn-danger" href="<?php echo href_to('admin', 'controllers', ['edit', 'galleryplus', 'category_delete', $cat['id']]); ?>" onclick="return confirm('<?php echo LANG_GALLERYPLUS_CATEGORY_DELETE_CONFIRM; ?>')">
                            <?php echo LANG_DELETE; ?>
                        </a>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <?php echo html_pagebar($page, $perpage, $total, href_to('admin', 'controllers', ['edit', 'galleryplus', 'categories', '%s'])); ?>

<?php } else { ?>
    <p><?php echo LANG_LIST_EMPTY; ?></p>
<?php } ?>
