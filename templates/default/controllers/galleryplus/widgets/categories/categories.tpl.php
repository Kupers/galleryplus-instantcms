<?php
/**
 * Template Name: Horizontal (tabs)
 * Template Type: widget
 */
$this->addTplCSS('controllers/galleryplus/styles');
$mode_param = $current_mode ? '&mode=' . urlencode($current_mode) : '';
$mode_all = $current_mode ? '?mode=' . urlencode($current_mode) : '';
?>

<div class="galleryplus-category-tabs">
    <a href="<?php echo href_to('galleryplus') . $mode_all; ?>" class="galleryplus-category-tab<?php echo empty($current_category) ? ' active' : ''; ?>"><?php echo LANG_GALLERYPLUS_ALL_CATEGORIES ?? 'All'; ?></a>
    <?php foreach ($categories as $cat): ?>
        <a href="<?php echo href_to('galleryplus') . '?mode=' . urlencode($current_mode ?: 'albums') . '&category=' . urlencode($cat['slug']); ?>" class="galleryplus-category-tab<?php echo (!empty($current_category) && $current_category['id'] == $cat['id']) ? ' active' : ''; ?>"><?php echo htmlspecialchars($cat['title']); ?><?php if ($show_counts): ?><span class="galleryplus-category-tab-count"><?php echo $cat['items_count']; ?></span><?php endif; ?></a>
    <?php endforeach; ?>
</div>
