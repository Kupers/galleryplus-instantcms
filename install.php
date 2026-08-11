<?php

function install_package() {

    $upload_dir = cmsConfig::get('upload_path') . 'galleryplus';
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0755, true);
    }

    try {
        $menu_model = cmsCore::getModel('menu');
        if ($menu_model) {
            $existing = $menu_model->db->getRow(
                'menu_items', "menu_id = 1 AND url = 'galleryplus'", 'id'
            );
            if (!$existing) {
                $menu_model->addMenuItem([
                    'menu_id'     => 1,
                    'parent_id'   => 0,
                    'is_enabled'  => 1,
                    'title'       => 'Галерея',
                    'url'         => 'galleryplus',
                    'options'     => [
                        'target' => '_self',
                        'class'  => '',
                    ],
                    'groups_view' => [0],
                    'groups_hide' => null,
                ]);
            }
        }
    } catch (\Exception $e) {}

    return true;
}

function after_install_package() {

    $db = \cmsDatabase::getInstance();

    // Set default controller options
    try {
        $options_yaml = "---\npreset_small: galleryplus_thumb\npreset_big: galleryplus_big\nordering: date_pub\norderto: desc\nlimit: 24\nview_all: [ ]\nlike: [ ]\nseo_h1: \"\"\nseo_title: \"\"\nseo_keys: \"\"\nseo_desc: \"\"\nnaming_scheme: mixed\nmax_file_size: 0\nmax_width: 0\nmax_height: 0\ndefault_mode: infinite\nshow_adult_in_feed: 1\nshow_adult_to_guests: 1\nis_comments_photo: 1\nis_comments_album: null\nshow_original: 1\npreset_nocrop: galleryplus_nocrop\nuse_categories: 1\nuse_album_tags: 1\nuse_photo_tags: 1\nupload_karma: 0\nadult_karma: 0\nadult_rating: 0\nhide_empty_albums: 1\nhide_exif: null\nshow_embed_codes: 1\nhide_map: null\nshow_lightbox_desc: 1\nlogging_enabled: 1
map_center_lat: 59.938933
map_center_lng: 30.315721\n";
        @$db->query("UPDATE `{#}controllers` SET `options` = '" . $db->escape($options_yaml) . "' WHERE `name` = 'galleryplus'");
    } catch (\Throwable $e) {}

    // DB migrations
    try {
        $table_fields = $db->getTableFields('galleryplus_albums');
        $has = function ($col) use ($table_fields) { return in_array($col, $table_fields); };

        if (!$has('privacy')) {
            @$db->query("ALTER TABLE `{#}galleryplus_albums` ADD `privacy` varchar(32) NOT NULL DEFAULT 'public' AFTER `ordering`");
        }
        if (!$has('privacy_password')) {
            @$db->query("ALTER TABLE `{#}galleryplus_albums` ADD `privacy_password` varchar(255) DEFAULT NULL AFTER `privacy`");
        }
        if (!$has('privacy_users')) {
            @$db->query("ALTER TABLE `{#}galleryplus_albums` ADD `privacy_users` text AFTER `privacy_password`");
        }
        if (!$has('comments_count')) {
            @$db->query("ALTER TABLE `{#}galleryplus_albums` ADD `comments_count` int(11) unsigned DEFAULT '0' AFTER `photos_count`");
        }
        if (!$has('category_id')) {
            @$db->query("ALTER TABLE `{#}galleryplus_albums` ADD `category_id` int(11) unsigned NOT NULL DEFAULT '0' AFTER `ordering`");
        }
        if (!$has('allow_upload')) {
            @$db->query("ALTER TABLE `{#}galleryplus_albums` ADD `allow_upload` tinyint(1) NOT NULL DEFAULT '0' AFTER `privacy_users`");
        }

        @$db->query("CREATE TABLE IF NOT EXISTS `{#}galleryplus_categories` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `title` varchar(100) NOT NULL,
            `slug` varchar(100) NOT NULL,
            `description` text,
            `image` text,
            `ordering` int(11) unsigned NOT NULL DEFAULT '0',
            `is_hidden` tinyint(1) NOT NULL DEFAULT '0',
            `items_count` int(11) unsigned NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            UNIQUE KEY `slug` (`slug`),
            KEY `ordering` (`ordering`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        @$db->query("CREATE TABLE IF NOT EXISTS `{#}galleryplus_updates` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `latest_version` varchar(32) DEFAULT NULL,
            `release_url` varchar(255) DEFAULT NULL,
            `notified_version` varchar(32) DEFAULT NULL,
            `checked_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        @$db->query("CREATE TABLE IF NOT EXISTS `{#}galleryplus_logs` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `action` varchar(16) NOT NULL,
            `target_type` varchar(16) NOT NULL,
            `target_id` int(11) unsigned NOT NULL DEFAULT '0',
            `title` varchar(255) DEFAULT NULL,
            `user_id` int(11) unsigned NOT NULL DEFAULT '0',
            `owner_id` int(11) unsigned NOT NULL DEFAULT '0',
            `date_pub` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `action` (`action`),
            KEY `target_type` (`target_type`),
            KEY `target_id` (`target_id`),
            KEY `user_id` (`user_id`),
            KEY `owner_id` (`owner_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    } catch (\Throwable $e) {}

    // Widget bindings: categories, map
    try {
        $widgets_to_bind = [
            ['name' => 'categories', 'title' => 'Категории',   'pages' => ['all']],
            ['name' => 'map',        'title' => 'Карта фото',  'pages' => ['galleryplus.albums', 'galleryplus.photos']],
        ];

        foreach ($widgets_to_bind as $wdef) {
            $widget = $db->query("SELECT `id` FROM `{#}widgets` WHERE `name` = '" . $db->escape($wdef['name']) . "' AND `controller` = 'galleryplus'")->fetchAssoc();
            if (!$widget) { continue; }

            $bind = $db->query("SELECT `id` FROM `{#}widgets_bind` WHERE `widget_id` = " . (int)$widget['id'])->fetchAssoc();
            if (!$bind) {
                $db->query("INSERT INTO `{#}widgets_bind` (`widget_id`, `title`, `is_title`, `is_cacheable`) VALUES (" . (int)$widget['id'] . ", '" . $db->escape($wdef['title']) . "', 1, 1)");
                $bind_id = $db->insertId();
            } else {
                $bind_id = $bind['id'];
            }

            if (!$bind_id) { continue; }

            foreach ($wdef['pages'] as $page_name) {
                $page = $db->query("SELECT `id` FROM `{#}widgets_pages` WHERE `controller` = 'galleryplus' AND `name` = '" . $db->escape($page_name) . "'")->fetchAssoc();
                if (!$page) { continue; }

                $exists = $db->query("SELECT `id` FROM `{#}widgets_bind_pages` WHERE `bind_id` = " . (int)$bind_id . " AND `page_id` = " . (int)$page['id'])->fetchAssoc();
                if (!$exists) {
                    $pos = 'pos_33';
                    $ordering = ($page_name === 'all' && $wdef['name'] === 'categories') ? 0 : 1;
                    $db->query("INSERT INTO `{#}widgets_bind_pages` (`bind_id`, `template`, `is_enabled`, `page_id`, `position`, `ordering`) VALUES (" . (int)$bind_id . ", 'modern', 1, " . (int)$page['id'] . ", '" . $db->escape($pos) . "', " . (int)$ordering . ")");
                }
            }
        }
    } catch (\Throwable $e) {}

    // Preset migration: update galleryplus_big to WebP height 700
    try {
        $preset_exists = $db->query("SELECT id FROM `{#}images_presets` WHERE `name` = 'galleryplus_big'")->fetchAssoc();
        if ($preset_exists) {
            @$db->query("UPDATE `{#}images_presets` SET `width` = NULL, `height` = 700, `quality` = 85, `convert_format` = 'webp' WHERE `name` = 'galleryplus_big'");
        }
    } catch (\Throwable $e) {}

    // Preset cleanup: remove obsolete galleryplus_album / galleryplus_thumb_h presets
    try {
        foreach (['galleryplus_album', 'galleryplus_thumb_h'] as $obsolete_preset) {
            $found = $db->query("SELECT id FROM `{#}images_presets` WHERE `name` = '" . $obsolete_preset . "'")->fetchAssoc();
            if ($found) {
                @$db->query("DELETE FROM `{#}images_presets` WHERE `name` = '" . $obsolete_preset . "'");
            }
        }
    } catch (\Throwable $e) {}

    // Remove obsolete auto_approve option
    try {
        @$db->query("UPDATE `{#}controllers` SET `options` = REPLACE(`options`, 'auto_approve: 1\n', '') WHERE `name` = 'galleryplus'");
        @$db->query("UPDATE `{#}controllers` SET `options` = REPLACE(`options`, 'auto_approve: 0\n', '') WHERE `name` = 'galleryplus'");
        @$db->query("UPDATE `{#}controllers` SET `options` = REPLACE(`options`, 'auto_approve: null\n', '') WHERE `name` = 'galleryplus'");
    } catch (\Throwable $e) {}

    return true;
}
