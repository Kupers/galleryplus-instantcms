<?php
/**
 * Template Name: Gallery+ Latest Photos
 * Template Type: widget
 */

$this->addTplCSS('controllers/galleryplus/styles');
?>

<div class="galleryplus-grid">
<?php foreach ($photos as $photo): ?>
    <div class="galleryplus-item">
        <a href="<?php echo $photo['url']; ?>" class="galleryplus-item-link">
            <img src="<?php echo $photo['url_thumb']; ?>" alt="<?php echo htmlspecialchars($photo['title'] ?? $photo['filename']); ?>" />
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
