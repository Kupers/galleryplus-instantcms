<?php
    $this->addTplCSSName('galleryplus');

    $preview_title = $title ?: (defined('LANG_GALLERYPLUS_SHARE_PREVIEW_TITLE') ? LANG_GALLERYPLUS_SHARE_PREVIEW_TITLE : 'Secret link');
    $preview_text  = !empty($description) ? $description : (defined('LANG_GALLERYPLUS_SHARE_PREVIEW_TEXT') ? LANG_GALLERYPLUS_SHARE_PREVIEW_TEXT : 'To open the content, click the button below.');
    $preview_open  = defined('LANG_GALLERYPLUS_SHARE_PREVIEW_OPEN') ? LANG_GALLERYPLUS_SHARE_PREVIEW_OPEN : 'Open';

    $this->setPageTitle($preview_title);
    $this->setPageDescription($preview_text);

    $this->addHead('<meta property="og:title" content="' . htmlspecialchars($preview_title) . '">');
    $this->addHead('<meta property="og:description" content="' . htmlspecialchars($preview_text) . '">');
    $this->addHead('<meta property="og:site_name" content="' . htmlspecialchars($this->site_config->sitename) . '">');
    if (!empty($image)) {
        $this->addHead('<meta property="og:type" content="article">');
        $this->addHead('<meta property="og:image" content="' . htmlspecialchars($image) . '">');
        $this->addHead('<meta name="twitter:card" content="summary_large_image">');
        $this->addHead('<meta name="twitter:title" content="' . htmlspecialchars($preview_title) . '">');
        $this->addHead('<meta name="twitter:image" content="' . htmlspecialchars($image) . '">');
    } else {
        $this->addHead('<meta property="og:type" content="website">');
        $this->addHead('<meta name="twitter:card" content="summary">');
    }
?>

<div class="galleryplus">
    <div class="galleryplus-album-locked">
        <?php if (!empty($image)) { ?>
            <div class="galleryplus-share-preview-image"><img src="<?php echo htmlspecialchars($image); ?>" alt=""></div>
        <?php } else { ?>
            <div class="galleryplus-album-locked-icon">&#128279;</div>
        <?php } ?>
        <h2><?php echo htmlspecialchars($preview_title); ?></h2>
        <p><?php echo htmlspecialchars($preview_text); ?></p>
        <p><a class="btn btn-primary" href="<?php echo htmlspecialchars($open_url); ?>" rel="nofollow"><?php echo htmlspecialchars($preview_open); ?></a></p>
    </div>
</div>
