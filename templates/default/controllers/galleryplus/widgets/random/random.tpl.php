<?php
/**
 * Template Name: Gallery+ Random Photos
 * Template Type: widget
 */

$this->addTplCSS('controllers/galleryplus/styles');
?>

<div class="galleryplus-grid">
<?php foreach ($photos as $photo):
    $widget_adult = !empty($photo['is_adult']);
?>
    <div class="galleryplus-item<?php echo $widget_adult ? ' galleryplus-item--adult' : ''; ?>">
        <a href="<?php echo $photo['url']; ?>" class="galleryplus-item-link">
            <img src="<?php echo $photo['url_thumb']; ?>" alt="<?php echo htmlspecialchars($photo['title'] ?? $photo['filename']); ?>" class="<?php echo $widget_adult ? 'galleryplus-blurred' : ''; ?>" />
            <?php if ($widget_adult): ?><div class="galleryplus-adult-badge">18+</div><?php endif; ?>
        </a>
        <div class="galleryplus-item-overlay">
            <a href="<?php echo $photo['url']; ?>" class="galleryplus-item-title"><?php echo htmlspecialchars($photo['title'] ?? $photo['filename']); ?></a>
            <div class="galleryplus-item-overlay-bottom">
                <div class="galleryplus-item-overlay-stats">
                    <span class="galleryplus-item-likes"><?php echo $photo['likes_count'] ?? 0; ?> &#9825;</span>
                </div>
                <?php if (!empty($photo['user']['slug'])): ?>
                    <a class="galleryplus-item-author" href="<?php echo href_to('users', $photo['user']['slug']); ?>"><?php echo htmlspecialchars($photo['user']['nickname']); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
