<?php
/**
 * Template Name: Vertical (list)
 * Template Type: widget
 */
$this->addTplCSS('controllers/galleryplus/styles');
?>

<div class="galleryplus-widget-categories">
    <ul class="galleryplus-widget-categories-list">
        <li class="galleryplus-widget-category-item">
            <a href="<?php echo href_to('galleryplus') . ($current_mode ? '?mode=' . urlencode($current_mode) : ''); ?>" class="galleryplus-widget-category-link<?php echo empty($current_category) ? ' active' : ''; ?>">
                <span class="galleryplus-widget-category-title"><?php echo LANG_GALLERYPLUS_ALL_CATEGORIES ?? 'All categories'; ?></span>
            </a>
        </li>
        <?php foreach ($categories as $cat): ?>
            <li class="galleryplus-widget-category-item">
                <a href="<?php echo href_to('galleryplus') . '?mode=' . urlencode($current_mode ?: 'albums') . '&category=' . urlencode($cat['slug']); ?>" class="galleryplus-widget-category-link<?php echo (!empty($current_category) && $current_category['id'] == $cat['id']) ? ' active' : ''; ?>">
                    <span class="galleryplus-widget-category-title"><?php echo htmlspecialchars($cat['title']); ?></span>
                    <?php if ($show_counts): ?>
                        <span class="galleryplus-widget-category-count"><?php echo $cat['items_count']; ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
