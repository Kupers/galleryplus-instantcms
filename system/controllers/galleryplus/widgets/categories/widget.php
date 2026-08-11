<?php
class widgetGalleryplusCategories extends cmsWidget {

    public function run() {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/galleryplus/album/#i', $uri)) {
            return false;
        }

        $model = cmsCore::getModel('galleryplus');

        $limit = $this->getOption('limit', 0);
        $show_counts = $this->getOption('show_counts', 1);

        $categories = $model->getCategoriesForWidget($limit);

        if (!$categories) { return false; }

        $category_slug = $_GET['category'] ?? '';
        $current_mode = $_GET['mode'] ?? '';
        $current_category = null;
        if ($category_slug) {
            $current_category = $model->getGalleryCategoryBySlug($category_slug);
        }

        if ($show_counts) {
            $user_id = cmsUser::get('id');
            foreach ($categories as &$cat) {
                $cat['items_count'] = $model->getAlbumsCountByCategory($cat['id'], $user_id);
            }
            unset($cat);
        }

        return [
            'categories'        => $categories,
            'show_counts'       => $show_counts,
            'current_category'  => $current_category,
            'current_mode'      => $current_mode,
        ];
    }
}
