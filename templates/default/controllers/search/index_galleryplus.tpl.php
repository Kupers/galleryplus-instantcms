<?php

    $this->addTplCSSName('galleryplus');

    $this->addBreadcrumb(LANG_SEARCH_TITLE, $this->href_to(''));
    if ($query) {
        $this->addBreadcrumb($query);
    }

    $uri_query = http_build_query([
        'q'    => $query,
        'type' => $type,
        'date' => $date
    ]);

    if ($results) {

        $content_menu = [];

        foreach ($results as $result) {

            $content_menu[] = [
                'title'    => $result['title'],
                'url'      => $this->href_to($result['name']) . '?' . $uri_query,
                'url_mask' => $this->href_to($result['name']),
                'counter'  => $result['count']
            ];

            if ($result['items']) {
                $search_data = $result;
            }
        }

        $content_menu[0]['url']      = href_to('search') . '?' . $uri_query;
        $content_menu[0]['url_mask'] = href_to('search');

        $this->addMenuItems('results_tabs', $content_menu);
    }

?>

<h1>
    <?php $this->pageH1(); ?>
</h1>

<div id="search_form">
    <form action="<?php echo href_to('search'); ?>" method="get">
        <?php echo html_input('text', 'q', $query, ['placeholder' => LANG_SEARCH_QUERY_INPUT]); ?>
        <?php echo html_select('type', [
            'words' => LANG_SEARCH_TYPE_WORDS,
            'exact' => LANG_SEARCH_TYPE_EXACT
        ], $type); ?>
        <?php echo html_select('date', [
            'all' => LANG_SEARCH_DATES_ALL,
            'w'   => LANG_SEARCH_DATES_W,
            'm'   => LANG_SEARCH_DATES_M,
            'y'   => LANG_SEARCH_DATES_Y
        ], $date); ?>
        <?php echo html_submit(LANG_FIND); ?>
    </form>
</div>

<?php if ($query && empty($search_data)) { ?>
    <p id="search_no_results"><?php echo LANG_SEARCH_NO_RESULTS; ?></p>
<?php } ?>

<?php if (!empty($search_data)) { ?>

    <div id="search_results_pills">
        <?php $this->menu('results_tabs', true, 'pills-menu-small'); ?>
    </div>

    <div class="galleryplus-search-results">
        <div class="galleryplus-grid">
            <?php foreach ($search_data['items'] as $photo) { ?>
                <div class="galleryplus-item">
                    <a href="<?php echo $photo['url']; ?>" class="galleryplus-item-link">
                        <img src="<?php echo $photo['url_thumb']; ?>" alt="<?php echo htmlspecialchars($photo['title']); ?>" loading="lazy">
                        <div class="galleryplus-item-overlay">
                            <span class="galleryplus-item-title"><?php echo htmlspecialchars($photo['title']); ?></span>
                        </div>
                    </a>
                </div>
            <?php } ?>
        </div>
    </div>

    <?php if ($search_data['count'] > $perpage) { ?>
        <?php echo html_pagebar($page, $perpage, $search_data['count'], $page_url, $uri_query); ?>
    <?php } ?>

<?php } ?>