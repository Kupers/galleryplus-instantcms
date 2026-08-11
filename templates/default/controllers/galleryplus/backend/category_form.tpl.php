<?php
    $editing = $editing ?? false;
    $title = $editing ? LANG_GALLERYPLUS_CATEGORY_EDIT : LANG_GALLERYPLUS_CATEGORY_ADD;
    $this->setPageTitle($title);

    $back_url = href_to('admin', 'controllers', ['edit', 'galleryplus', 'categories']);
?>

<h1><?php echo $title; ?></h1>

<?php $this->renderForm($form, $data, [
    'action' => '',
    'method' => 'post',
    'cancel' => ['show' => true, 'href' => $back_url],
], $errors ?? false); ?>
