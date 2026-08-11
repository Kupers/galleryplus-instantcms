<?php
/**
 * Template Name: Gallery+ Albums
 * Template Type: widget
 */

$this->addTplCSS('controllers/galleryplus/styles');
?>

<div class="galleryplus-albums-grid">
<?php foreach ($albums as $album): ?>
    <a href="<?php echo $album['url']; ?>" class="galleryplus-album-card<?php if (!empty($album['is_protected'])) { ?> galleryplus-album-card--protected<?php } ?>">
        <div class="galleryplus-album-cover">
            <?php if ($album['cover_url']): ?>
                <?php if (!empty($album['is_protected'])): ?>
                    <img class="galleryplus-blurred" src="<?php echo $album['cover_url']; ?>" alt="" />
                <?php else: ?>
                    <img src="<?php echo $album['cover_url']; ?>" alt="<?php echo htmlspecialchars($album['title']); ?>" />
                <?php endif; ?>
            <?php else: ?>
                <div class="galleryplus-album-cover-empty">0 <?php echo LANG_GALLERYPLUS_PHOTOS; ?></div>
            <?php endif; ?>
            <?php if (!empty($album['is_protected']) && $album['privacy'] !== 'public'): ?>
                <span class="galleryplus-adult-badge">&#128274;</span>
            <?php endif; ?>
        </div>
        <div class="galleryplus-album-info">
            <div class="galleryplus-album-title"><?php echo htmlspecialchars($album['title']); ?></div>
            <div class="galleryplus-album-count"><?php echo $album['photo_count']; ?> <?php echo LANG_GALLERYPLUS_PHOTOS; ?></div>
            <div class="galleryplus-album-user">
                <?php if (!empty($album['user']['slug'])): ?>
                    <a class="galleryplus-item-author" href="<?php echo href_to('users', $album['user']['slug']); ?>"><?php echo htmlspecialchars($album['user']['nickname']); ?></a>
                <?php else: ?>
                    <span class="galleryplus-item-author"><?php echo htmlspecialchars($album['user']['nickname']); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </a>
<?php endforeach; ?>
</div>
