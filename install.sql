CREATE TABLE IF NOT EXISTS `{#}galleryplus_albums` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL,
  `content` text,
  `slug` varchar(100) DEFAULT NULL,
  `date_pub` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_last_modified` timestamp NULL DEFAULT NULL,
  `is_pub` tinyint(1) NOT NULL DEFAULT '1',
  `is_approved` tinyint(1) DEFAULT '1',
  `approved_by` int(11) DEFAULT NULL,
  `date_approved` timestamp NULL DEFAULT NULL,
  `hits_count` int(11) DEFAULT '0',
  `user_id` int(11) unsigned DEFAULT NULL,
  `cover_image` text,
  `photos_count` int(11) NOT NULL DEFAULT '0',
  `comments_count` int(11) unsigned DEFAULT '0',
  `ordering` int(11) unsigned NOT NULL DEFAULT '0',
  `category_id` int(11) unsigned NOT NULL DEFAULT '0',
  `privacy` varchar(32) NOT NULL DEFAULT 'public',
  `privacy_password` varchar(255) DEFAULT NULL,
  `privacy_users` text,
  `allow_upload` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `slug` (`slug`),
  KEY `user_id` (`user_id`,`date_pub`),
  KEY `date_pub` (`is_pub`,`is_approved`,`date_pub`),
  KEY `privacy` (`privacy`),
  KEY `category_id` (`category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{#}galleryplus_photos` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `album_id` int(11) unsigned DEFAULT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  `date_pub` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `title` varchar(128) DEFAULT NULL,
  `content` text,
  `image` text NOT NULL,
  `exif` text,
  `height` smallint(5) unsigned NOT NULL DEFAULT '0',
  `width` smallint(5) unsigned NOT NULL DEFAULT '0',
  `sizes` text,
  `rating` int(11) NOT NULL DEFAULT '0',
  `comments` int(11) unsigned DEFAULT '0',
  `hits_count` int(11) unsigned NOT NULL DEFAULT '0',
  `orientation` enum('square','landscape','portrait','') DEFAULT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `is_private` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_approved` tinyint(1) DEFAULT '1',
  `approved_by` int(11) DEFAULT NULL,
  `date_approved` timestamp NULL DEFAULT NULL,
  `ordering` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`,`date_pub`),
  KEY `album_id` (`album_id`,`date_pub`,`id`),
  KEY `slug` (`slug`),
  KEY `ordering` (`ordering`),
  FULLTEXT KEY `title` (`title`,`content`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{#}galleryplus_likes` (
  `id`          int(11) unsigned NOT NULL AUTO_INCREMENT,
  `target_id`   int(11) unsigned NOT NULL,
  `target_type` varchar(32)       NOT NULL COMMENT 'photo or album',
  `user_id`     int(11) unsigned NOT NULL,
  `date_pub`    timestamp         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `target_user` (`target_id`,`target_type`,`user_id`),
  KEY `target` (`target_id`,`target_type`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{#}galleryplus_categories` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `{#}images_presets` (`name`, `title`, `width`, `height`, `quality`, `is_square`, `is_internal`)
SELECT 'galleryplus_thumb', 'Gallery+ thumb', 400, NULL, 85, 0, NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `{#}images_presets` WHERE `name` = 'galleryplus_thumb');

INSERT INTO `{#}images_presets` (`name`, `title`, `width`, `height`, `quality`, `is_square`, `is_internal`, `convert_format`)
SELECT 'galleryplus_big', 'Gallery+ big', NULL, 700, 85, 0, NULL, 'webp'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `{#}images_presets` WHERE `name` = 'galleryplus_big');

INSERT INTO `{#}images_presets` (`name`, `title`, `width`, `height`, `quality`, `is_square`, `is_internal`, `convert_format`)
SELECT 'galleryplus_nocrop', 'Gallery+ оригинал (WebP)', 0, 0, 92, 0, NULL, 'webp'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `{#}images_presets` WHERE `name` = 'galleryplus_nocrop');

INSERT INTO `{#}users_tabs` (`title`, `controller`, `name`, `is_active`, `ordering`)
SELECT 'Albums', 'galleryplus', 'albums', 1, 8
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `{#}users_tabs` WHERE `name` = 'albums' AND `controller` = 'galleryplus');

INSERT INTO `{#}widgets` (`title`, `name`, `controller`, `author`, `version`, `is_external`)
SELECT 'Albums', 'albums', 'galleryplus', 'GalleryPlus', '1.0.0', 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `{#}widgets` WHERE `name` = 'albums' AND `controller` = 'galleryplus');

INSERT INTO `{#}widgets` (`title`, `name`, `controller`, `author`, `version`, `is_external`)
SELECT 'Latest photos', 'photos', 'galleryplus', 'GalleryPlus', '1.0.0', 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `{#}widgets` WHERE `name` = 'photos' AND `controller` = 'galleryplus');

INSERT INTO `{#}widgets` (`title`, `name`, `controller`, `author`, `version`, `is_external`)
SELECT 'Random photos', 'random', 'galleryplus', 'GalleryPlus', '1.0.0', 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `{#}widgets` WHERE `name` = 'random' AND `controller` = 'galleryplus');

INSERT INTO `{#}widgets` (`title`, `name`, `controller`, `author`, `version`, `is_external`)
SELECT 'Categories', 'categories', 'galleryplus', 'GalleryPlus', '1.0.0', 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `{#}widgets` WHERE `name` = 'categories' AND `controller` = 'galleryplus');

INSERT INTO `{#}widgets` (`title`, `name`, `controller`, `author`, `version`, `is_external`)
SELECT 'Photo map', 'map', 'galleryplus', 'GalleryPlus', '1.0.0', 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `{#}widgets` WHERE `name` = 'map' AND `controller` = 'galleryplus');

INSERT INTO `{#}widgets_pages` (`controller`, `name`, `title_const`, `url_mask`)
SELECT 'galleryplus', 'all', 'LANG_GALLERYPLUS_PAGE_ALL', 'galleryplus'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `{#}widgets_pages` WHERE `controller` = 'galleryplus' AND `name` = 'all');

INSERT INTO `{#}widgets_pages` (`controller`, `name`, `title_const`, `url_mask`)
SELECT 'galleryplus', 'galleryplus.albums', 'LANG_GALLERYPLUS_PAGE_ALBUMS', 'galleryplus/album/*'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `{#}widgets_pages` WHERE `controller` = 'galleryplus' AND `name` = 'galleryplus.albums');

INSERT INTO `{#}widgets_pages` (`controller`, `name`, `title_const`, `url_mask`, `url_mask_not`)
SELECT 'galleryplus', 'galleryplus.photos', 'LANG_GALLERYPLUS_PAGE_PHOTOS', 'galleryplus/*.html', 'galleryplus/album/*\ngalleryplus/category/*\ngalleryplus/tag/*'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `{#}widgets_pages` WHERE `controller` = 'galleryplus' AND `name` = 'galleryplus.photos');

CREATE TABLE IF NOT EXISTS `{#}galleryplus_updates` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `latest_version` varchar(32) DEFAULT NULL,
  `release_url` varchar(255) DEFAULT NULL,
  `notified_version` varchar(32) DEFAULT NULL,
  `checked_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
