<?php
    $this->addTplCSSName('galleryplus');
    $this->setPageTitle(defined('LANG_GALLERYPLUS_SHARE_BLOCKED_TITLE') ? LANG_GALLERYPLUS_SHARE_BLOCKED_TITLE : 'Ссылка недоступна');
?>

<div class="galleryplus">
    <div class="galleryplus-album-locked">
        <div class="galleryplus-album-locked-icon">&#128274;</div>
        <h2><?php echo defined('LANG_GALLERYPLUS_SHARE_BLOCKED_TITLE') ? LANG_GALLERYPLUS_SHARE_BLOCKED_TITLE : 'Ссылка недоступна'; ?></h2>
        <p><?php echo htmlspecialchars($message ?? ''); ?></p>
    </div>
</div>
