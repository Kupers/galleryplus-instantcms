<?php

class modelGalleryplus extends cmsModel {

    use icms\traits\controllers\models\transactable;

    public $preset_small  = 'galleryplus_thumb';
    public $preset_big    = 'galleryplus_big';
    public $preset_nocrop = 'galleryplus_nocrop';
    public $adult_karma   = 0;
    public $user_karma    = 0;
    public $adult_rating  = 0;
    public $user_rating   = 0;
    public $skip_visible_albums_filter = false;

    /**
     * При включении платного скачивания оригинала
     * url_nocrop / url_original направляются через serve.php
     */
    public $original_paid = false;

    public function isPrivacyFilterDisabled() {
        return $this->privacy_filter_disabled;
    }

    public function isApprovedFilterDisabled() {
        return $this->approved_filter_disabled;
    }

    public function getPhotos($page = 1, $perpage = 24, $category_id = 0, $include_adult_for_guests = false) {
        $this->joinUser();
        if (!$this->privacy_filter_disabled) { $this->filterPrivacy(); }
        if (!$this->approved_filter_disabled) { $this->filterApprovedOnly(); }

        $current_user_id = cmsUser::get('id');
        if ($category_id) {
            $this->join('galleryplus_albums', 'a', 'i.album_id = a.id');
            $this->filterEqual('a.category_id', $category_id);
        }
        $this->filterVisibleAlbums($current_user_id, $include_adult_for_guests);

        $this->limitPagePlus($page, $perpage);
        $result = $this->get('galleryplus_photos', function ($item, $model) {
            $item['image'] = cmsModel::yamlToArray($item['image']);
            $item['sizes'] = cmsModel::yamlToArray($item['sizes']);
            $item['exif']  = cmsModel::yamlToArray($item['exif']);
            $item['user'] = [
                'id'       => $item['user_id'],
                'nickname' => $item['user_nickname'],
                'slug'     => $item['user_slug'],
                'avatar'   => $item['user_avatar'],
            ];
            $item = $this->decoratePhoto($item);
            $this->applyPhotoUrls($item);
            return $item;
        }, false);
        if (!$result) { return []; }

        $album_ids = [];
        foreach ($result as $p) {
            if (!empty($p['album_id'])) { $album_ids[$p['album_id']] = true; }
        }
        $protected_albums = [];
        if ($album_ids) {
            $this->resetFilters();
            $this->filterIn('id', array_keys($album_ids));
            $this->filterNotEqual('privacy', '');
            $this->filterNotEqual('privacy', 'public');
            $protected = $this->get('galleryplus_albums', function ($item) { return $item['id']; }, false);
            if ($protected) {
                foreach ($protected as $aid) { $protected_albums[$aid] = true; }
            }
        }

        foreach ($result as &$p) {
            if (!empty($protected_albums[$p['album_id']])) {
                $p['url_thumb']  = $this->getPhotoServeUrl($p['id'], 'small');
                $p['url_big']    = $this->getPhotoServeUrl($p['id'], 'big');
                $p['url_nocrop'] = !empty($p['image'][$this->preset_nocrop]) ? $this->getPhotoServeUrl($p['id'], 'nocrop') : '';
                $p['url_original'] = !empty($p['image']['original']) ? $this->getPhotoServeUrl($p['id'], 'original') : '';
            }
        }
        unset($p);

        return $result;
    }

    /**
     * Гарантирует фото корректный slug и заголовок (fallback на имя файла).
     * Может работать с сырой строкой image из БД или уже с массивом.
     */
    public function decoratePhoto($item) {
        if (!is_array($item['image'] ?? null)) {
            $item['image'] = cmsModel::yamlToArray($item['image'] ?? '');
        }
        $filename = '';
        $orig = $item['image']['original'] ?? '';
        if ($orig) {
            $filename = pathinfo($orig, PATHINFO_FILENAME);
        } else {
            $thumb_key = $this->preset_small;
            $thumb_src = $item['image'][$thumb_key] ?? (is_array($item['image']) ? reset($item['image']) : '');
            if ($thumb_src) {
                $filename = preg_replace('/-' . preg_quote($thumb_key, '/') . '$/', '', pathinfo($thumb_src, PATHINFO_FILENAME));
            }
        }
        $item['filename'] = $filename;
        if (empty($item['title'])) {
            $item['title'] = $filename;
        }
        if (empty($item['slug'])) {
            $item['slug'] = 'photo-' . $item['id'];
        }
        $item['url'] = href_to('galleryplus', $item['slug']) . '.html';
        return $item;
    }

    /**
     * Формирует url_thumb / url_big / url_nocrop / url_original у фото.
     * При original_paid url_nocrop и url_original идут через serve.php
     * и в шаблонах должны выдаваться только при can_original.
     */
    public function applyPhotoUrls(&$item) {
        $item['url_thumb'] = html_image_src($item['image'], $this->preset_small, true);
        $item['url_big']   = html_image_src($item['image'], $this->preset_big, true)
            ?: html_image_src($item['image'], 'normal', true);
        if ($this->original_paid) {
            $item['url_nocrop']   = !empty($item['image'][$this->preset_nocrop]) ? $this->getPhotoServeUrl($item['id'], 'nocrop') : '';
            $item['url_original'] = !empty($item['image']['original']) ? $this->getPhotoServeUrl($item['id'], 'original') : '';
        } else {
            $item['url_nocrop']   = html_image_src($item['image'], $this->preset_nocrop, true);
            $item['url_original'] = html_image_src($item['image'], 'original', true);
        }
        $item['can_original'] = $this->canDownloadOriginal($item);
        return $item;
    }

    /**
     * Может ли пользователь скачать оригинал фото.
     */
    public function canDownloadOriginal($photo) {
        if (!$this->original_paid) { return true; }
        $user_id = cmsUser::get('id');
        if (!$user_id) { return false; }
        if (cmsUser::isAdmin()) { return true; }
        if ((int)$photo['user_id'] === (int)$user_id) { return true; }
        return $this->isOriginalAccessGranted($user_id, $photo['id']);
    }

    public function isAlbumAccessGranted($user_id, $album_id) {
        $this->resetFilters();
        $this->filterEqual('user_id', (int)$user_id);
        $this->filterEqual('album_id', (int)$album_id);
        $count = $this->getCount('galleryplus_album_access');
        $this->resetFilters();
        return (bool)$count;
    }

    public function setAlbumAccess($user_id, $album_id) {
        $this->insert('galleryplus_album_access', [
            'user_id'  => (int)$user_id,
            'album_id' => (int)$album_id,
        ], false, true);
        return true;
    }

    public function deleteAlbumAccess($user_id, $album_id) {
        $this->resetFilters();
        $this->filterEqual('user_id', (int)$user_id);
        $this->filterEqual('album_id', (int)$album_id);
        $result = $this->deleteFiltered('galleryplus_album_access');
        $this->resetFilters();
        return $result;
    }

    public function clearAlbumAccess($album_id) {
        $this->resetFilters();
        $this->filterEqual('album_id', (int)$album_id);
        $result = $this->deleteFiltered('galleryplus_album_access');
        $this->resetFilters();
        return $result;
    }

    public function isOriginalAccessGranted($user_id, $photo_id) {
        $this->resetFilters();
        $this->filterEqual('user_id', (int)$user_id);
        $this->filterEqual('photo_id', (int)$photo_id);
        $count = $this->getCount('galleryplus_original_access');
        $this->resetFilters();
        return (bool)$count;
    }

    public function setOriginalAccess($user_id, $photo_id) {
        $this->insert('galleryplus_original_access', [
            'user_id'  => (int)$user_id,
            'photo_id' => (int)$photo_id,
        ], false, true);
        return true;
    }

    public function deleteOriginalAccess($user_id, $photo_id) {
        $this->resetFilters();
        $this->filterEqual('user_id', (int)$user_id);
        $this->filterEqual('photo_id', (int)$photo_id);
        $result = $this->deleteFiltered('galleryplus_original_access');
        $this->resetFilters();
        return $result;
    }

    public function clearPhotoAccess($photo_id) {
        $this->resetFilters();
        $this->filterEqual('photo_id', (int)$photo_id);
        $result = $this->deleteFiltered('galleryplus_original_access');
        $this->resetFilters();
        return $result;
    }

    /**
     * id фото, по которым у пользователя есть платный доступ к оригиналу.
     * Для батч-проверок в списках фото.
     */
    public function getOriginalAccessGrantedIds($user_id, $photo_ids) {
        if (!$user_id || !$photo_ids) { return []; }
        $photo_ids = array_map('intval', $photo_ids);
        $this->resetFilters();
        $this->filterEqual('user_id', (int)$user_id);
        $this->filterIn('photo_id', $photo_ids);
        $rows = $this->get('galleryplus_original_access', function ($item) {
            return (int)$item['photo_id'];
        }, false);
        $this->resetFilters();
        return $rows ?: [];
    }

    public function getPhotoBySlug($slug) {
        $this->joinUser();
        $this->joinSessionsOnline();
        if (preg_match('/^photo-(\d+)$/i', $slug, $m)) {
            $this->filterEqual('i.id', (int)$m[1]);
        } else {
            $this->filterEqual('i.slug', $slug);
        }
        $photo = $this->getItem('galleryplus_photos', function ($item, $model) {
            $item['image'] = cmsModel::yamlToArray($item['image']);
            $item['sizes'] = cmsModel::yamlToArray($item['sizes']);
            $item['exif']  = cmsModel::yamlToArray($item['exif']);
            $item['user'] = [
                'id'        => $item['user_id'],
                'slug'      => $item['user_slug'],
                'nickname'  => $item['user_nickname'],
                'is_online' => $item['is_online'],
                'avatar'    => $item['user_avatar'],
            ];
            $item = $this->decoratePhoto($item);
            $this->applyPhotoUrls($item);
            return $item;
        });
        if (!$photo) { return null; }
        $album = $this->getAlbum($photo['album_id']);
        if ($album) {
            $album['url'] = href_to('galleryplus', 'album', [$album['slug']]) . '.html';
            $photo['album'] = $album;
            if ($this->isAlbumProtected($album)) {
                $photo['url_thumb']  = $this->getPhotoServeUrl($photo['id'], 'small');
                $photo['url_big']    = $this->getPhotoServeUrl($photo['id'], 'big');
                $photo['url_nocrop'] = !empty($photo['image'][$this->preset_nocrop]) ? $this->getPhotoServeUrl($photo['id'], 'nocrop') : '';
                $photo['url_original'] = !empty($photo['image']['original']) ? $this->getPhotoServeUrl($photo['id'], 'original') : '';
            }
        }
        return $photo;
    }

    public function getAlbum($id) {
        $this->joinUser();
        $this->filterEqual('i.id', $id);
        $album = $this->getItem('galleryplus_albums', function ($item, $model) {
            $item['user'] = [
                'id'       => $item['user_id'],
                'nickname' => $item['user_nickname'] ?? '',
                'slug'     => $item['user_slug'] ?? '',
            ];
            return $item;
        });
        if (!$album) { return null; }
        return $album;
    }

    public function getAlbumBySlug($slug) {
        $this->joinUser();
        $this->filterEqual('slug', $slug);
        $album = $this->getItem('galleryplus_albums', function ($item, $model) {
            $item['user'] = [
                'id'       => $item['user_id'],
                'nickname' => $item['user_nickname'] ?? '',
                'slug'     => $item['user_slug'] ?? '',
            ];
            return $item;
        });
        if (!$album) { return null; }
        return $album;
    }

    public function getAlbums($page = 1, $perpage = 12, $user_id = 0, $include_adult_for_guests = false) {
        $this->joinUser();
        $this->orderBy('i.date_pub', 'desc');
        $this->filterPrivacyAlbums($user_id, $include_adult_for_guests, $this->user_karma, $this->adult_karma);
        $this->limitPagePlus($page, $perpage);
        $inc_adult = $include_adult_for_guests;
        return $this->get('galleryplus_albums', function ($item, $model) use ($inc_adult) {
            $item['url'] = href_to('galleryplus', 'album', [$item['slug']]) . '.html';
            $item['photo_count'] = $model->getPhotosCount($item['id'], 0, $inc_adult);
            $cover = $model->getAlbumCover($item['id']);
            $item['cover_url']    = $cover['url'];
            $item['cover_width']  = $cover['width'];
            $item['cover_height'] = $cover['height'];
            $item['likes_count'] = $model->getAlbumLikesCount($item['id']);
            $item['user'] = [
                'id'       => $item['user_id'],
                'nickname' => $item['user_nickname'] ?? '',
                'slug'     => $item['user_slug'] ?? '',
            ];
            return $item;
        }, false);
    }

    public function getPhotosLikesBatch($photo_ids, $user_id) {
        if (!$photo_ids) { return []; }
        $ids = array_map('intval', $photo_ids);

        // Get counts for all photos
        $id_list = implode(',', $ids);
        $sql = "SELECT target_id, COUNT(*) as cnt FROM {#}galleryplus_likes WHERE target_type = 'photo' AND target_id IN ({$id_list}) GROUP BY target_id";
        $result = $this->db->query($sql);
        $counts = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $counts[(int)$row['target_id']] = (int)$row['cnt'];
        }

        // Get user like status for all photos
        $liked_ids = [];
        if ($user_id) {
            $sql2 = "SELECT target_id FROM {#}galleryplus_likes WHERE target_type = 'photo' AND user_id = " . (int)$user_id . " AND target_id IN ({$id_list})";
            $result2 = $this->db->query($sql2);
            while ($row = $this->db->fetchAssoc($result2)) {
                $liked_ids[(int)$row['target_id']] = true;
            }
        }

        $data = [];
        foreach ($ids as $pid) {
            $data[$pid] = [
                'count' => $counts[$pid] ?? 0,
                'liked' => !empty($liked_ids[$pid]),
            ];
        }
        return $data;
    }

    public function getAlbumLikesCount($album_id) {
        $photos = $this->filterEqual('album_id', $album_id)->get('galleryplus_photos', function ($item) {
            return $item['id'];
        }, false);
        $this->resetFilters();
        if (!$photos) { return 0; }
        $ids = array_map('intval', $photos);
        $sql = "SELECT COUNT(*) as cnt FROM {#}galleryplus_likes WHERE target_type = 'photo' AND target_id IN (" . implode(',', $ids) . ")";
        $result = $this->db->query($sql);
        $row = $this->db->fetchAssoc($result);
        return $row ? (int) $row['cnt'] : 0;
    }

    public function getPhotosCount($album_id = null, $category_id = 0, $include_adult_for_guests = false) {
        $this->joinUser();
        if (!$this->privacy_filter_disabled) { $this->filterPrivacy(); }
        if (!$this->approved_filter_disabled) { $this->filterApprovedOnly(); }
        if ($album_id) {
            $this->filterEqual('i.album_id', $album_id);
        } elseif ($category_id) {
            $this->join('galleryplus_albums', 'a', 'i.album_id = a.id');
            $this->filterEqual('a.category_id', $category_id);
        }
        $current_user_id = cmsUser::get('id');
        $this->filterVisibleAlbums($current_user_id, $include_adult_for_guests);
        $count = $this->getCount('galleryplus_photos');
        $this->resetFilters();
        return $count;
    }

    public function getAlbumsCount($user_id = 0, $include_adult_for_guests = false) {
        $this->filterPrivacyAlbums($user_id, $include_adult_for_guests, $this->user_karma, $this->adult_karma);
        $count = $this->getCount('galleryplus_albums');
        $this->resetFilters();
        return $count;
    }

    public function getAlbumCover($album_id) {
        $this->resetFilters();
        $this->filterEqual('i.album_id', $album_id);
        $this->orderBy('i.date_pub', 'asc');
        $this->limit(1);
        $photo = $this->getItem('galleryplus_photos');
        $this->resetFilters();
        if ($photo) {
            $photo['image'] = cmsModel::yamlToArray($photo['image']);
            $url = html_image_src($photo['image'], $this->preset_small, true)
                ?: '';
            return ['url' => $url, 'width' => (int)$photo['width'], 'height' => (int)$photo['height']];
        }
        return ['url' => '', 'width' => 0, 'height' => 0];
    }

    public function incrementCounter($photo_id) {
        $this->resetFilters();
        $this->filterEqual('id', $photo_id);
        return $this->increment('galleryplus_photos', 'hits_count');
    }

    public function addLike($target_id, $target_type, $user_id) {
        $this->resetFilters();
        $exists = $this->filterEqual('target_id', $target_id)
            ->filterEqual('target_type', $target_type)
            ->filterEqual('user_id', $user_id)
            ->getItem('galleryplus_likes');
        $this->resetFilters();
        if ($exists) {
            $this->delete('galleryplus_likes', $exists['id']);
            return ['status' => 'unliked', 'count' => $this->getLikesCount($target_id, $target_type)];
        }
        $this->insert('galleryplus_likes', [
            'target_id'   => $target_id,
            'target_type' => $target_type,
            'user_id'     => $user_id,
            'date_pub'    => null,
        ]);
        return ['status' => 'liked', 'count' => $this->getLikesCount($target_id, $target_type)];
    }

    public function getLikesCount($target_id, $target_type) {
        $this->resetFilters();
        $count = $this->filterEqual('target_id', $target_id)
            ->filterEqual('target_type', $target_type)
            ->getCount('galleryplus_likes');
        $this->resetFilters();
        return $count;
    }

    public function getUserLikeStatus($target_id, $target_type, $user_id) {
        if (!$user_id) { return false; }
        $this->resetFilters();
        $status = (bool) $this->filterEqual('target_id', $target_id)
            ->filterEqual('target_type', $target_type)
            ->filterEqual('user_id', $user_id)
            ->getCount('galleryplus_likes');
        $this->resetFilters();
        return $status;
    }

    // ─── Favorites ─────────────────────────────────────────

    public function isPhotoFavorited($photo_id, $user_id) {
        if (!$user_id) { return false; }
        return (bool) $this->filterEqual('photo_id', $photo_id)
            ->filterEqual('user_id', $user_id)
            ->getCount('galleryplus_favorites');
    }

    public function toggleFavorite($photo_id, $user_id) {
        $this->resetFilters();
        $exists = $this->filterEqual('photo_id', $photo_id)
            ->filterEqual('user_id', $user_id)
            ->getItem('galleryplus_favorites');
        $this->resetFilters();
        if ($exists) {
            $this->delete('galleryplus_favorites', $exists['id']);
            return ['status' => 'unfavorited', 'count' => $this->getFavoritesCount($photo_id)];
        }
        $this->insert('galleryplus_favorites', [
            'photo_id' => $photo_id,
            'user_id'  => $user_id,
            'date_pub' => null,
        ]);
        return ['status' => 'favorited', 'count' => $this->getFavoritesCount($photo_id)];
    }

    public function getFavoritesCount($photo_id) {
        $this->resetFilters();
        $count = $this->filterEqual('photo_id', $photo_id)->getCount('galleryplus_favorites');
        $this->resetFilters();
        return $count;
    }

    public function getUserFavoritesCount($user_id) {
        $this->resetFilters();
        $count = $this->filterEqual('user_id', $user_id)->getCount('galleryplus_favorites');
        $this->resetFilters();
        return $count;
    }

    public function getFavoritePhotoIds($user_id) {
        $this->resetFilters();
        $rows = $this->filterEqual('user_id', $user_id)
            ->orderBy('date_pub', 'desc')
            ->get('galleryplus_favorites', function ($item) {
                return (int)$item['photo_id'];
            }, false);
        $this->resetFilters();
        return $rows ?: [];
    }

    public function getPhotosFavoritesBatch($photo_ids, $user_id) {
        if (!$photo_ids) { return []; }
        $ids = array_map('intval', $photo_ids);
        $fav = [];
        if ($user_id) {
            $list = implode(',', $ids);
            $sql = "SELECT photo_id FROM {#}galleryplus_favorites WHERE user_id = " . (int)$user_id . " AND photo_id IN ({$list})";
            $result = $this->db->query($sql);
            while ($row = $this->db->fetchAssoc($result)) {
                $fav[(int)$row['photo_id']] = true;
            }
        }
        $data = [];
        foreach ($ids as $pid) {
            $data[$pid] = !empty($fav[$pid]);
        }
        return $data;
    }

    public function getFavoritePhotos($user_id, $page = 1, $perpage = 24, $include_adult_for_guests = false) {
        $ids = $this->getFavoritePhotoIds($user_id);
        if (!$ids) { return []; }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        $offset = max(0, ($page - 1) * $perpage);
        $page_ids = array_slice($ids, $offset, $perpage + 1);
        if (!$page_ids) { return []; }

        $this->joinUser();
        if (!$this->privacy_filter_disabled) { $this->filterPrivacy(); }
        if (!$this->approved_filter_disabled) { $this->filterApprovedOnly(); }

        $result = $this->filterIn('i.id', $page_ids)->get('galleryplus_photos', function ($item, $model) {
            $item['image'] = cmsModel::yamlToArray($item['image']);
            $item['sizes'] = cmsModel::yamlToArray($item['sizes']);
            $item['exif']  = cmsModel::yamlToArray($item['exif']);
            $item['user'] = [
                'id'       => $item['user_id'],
                'nickname' => $item['user_nickname'],
                'slug'     => $item['user_slug'],
                'avatar'   => $item['user_avatar'],
            ];
            $item = $this->decoratePhoto($item);
            $item['url_thumb']    = html_image_src($item['image'], $model->preset_small, true);
            $item['url_big']      = html_image_src($item['image'], $model->preset_big, true)
                ?: html_image_src($item['image'], 'normal', true);
            $item['url_nocrop']   = html_image_src($item['image'], $model->preset_nocrop, true);
            $item['url_original'] = html_image_src($item['image'], 'original', true);
            return $item;
        }, false);
        $this->resetFilters();
        if (!$result) { return []; }

        // Восстанавливаем порядок последних добавленных (date_pub desc)
        $order_map = array_flip($page_ids);
        usort($result, function ($a, $b) use ($order_map) {
            $ka = isset($order_map[$a['id']]) ? $order_map[$a['id']] : 0;
            $kb = isset($order_map[$b['id']]) ? $order_map[$b['id']] : 0;
            return $ka <=> $kb;
        });

        return $result;
    }

    public function filterApprovedOnly() {
        if ($this->approved_filtered) { return $this; }
        $this->approved_filtered = true;
        $this->filterEqual('i.is_approved', 1);
        return $this;
    }

    public function addPhoto($data) {
        return $this->insert('galleryplus_photos', $data);
    }

    public function updatePhoto($id, $data) {
        $this->resetFilters();
        $this->filterEqual('id', $id);
        return $this->updateFiltered('galleryplus_photos', $data);
    }

    public function deletePhoto($id) {
        $this->resetFilters();
        $this->filterEqual('id', $id);
        $result = $this->delete('galleryplus_photos', $id);
        if ($result) { $this->clearPhotoAccess($id); }
        return $result;
    }

    public function getPhotosImagesByIds($ids) {
        if (!$ids) { return []; }
        $ids = array_map('intval', $ids);
        $rows = $this->db->getRows('galleryplus_photos', 'id IN (' . implode(',', $ids) . ')', 'id, album_id, title, user_id, image');
        return $rows ?: [];
    }

    public function getUniqueAlbumSlug($slug, $user_id = 0) {
        $slug = trim((string)$slug);
        if ($slug === '') { $slug = 'album'; }
        $base   = $slug;
        $suffix = 2;
        $this->resetFilters();
        $this->filterEqual('slug', $slug);
        if ($user_id) { $this->filterEqual('user_id', $user_id); }
        while ($this->getCount('galleryplus_albums')) {
            $slug = $base . '-' . $suffix++;
            $this->resetFilters();
            $this->filterEqual('slug', $slug);
            if ($user_id) { $this->filterEqual('user_id', $user_id); }
        }
        $this->resetFilters();
        return $slug;
    }

    public function recalcAlbumPhotosCount($album_id) {
        $this->resetFilters();
        $this->filterEqual('album_id', $album_id);
        $count = $this->getCount('galleryplus_photos');
        $this->resetFilters();
        return $this->update('galleryplus_albums', $album_id, ['photos_count' => $count]);
    }

    public function deletePhotosBatch($ids) {
        if (!$ids) { return 0; }
        $ids = array_map('intval', $ids);
        $deleted = 0;
        foreach ($ids as $id) {
            $this->resetFilters();
            $this->filterEqual('id', $id);
            if ($this->delete('galleryplus_photos', $id)) {
                $this->clearPhotoAccess($id);
                $deleted++;
            }
        }
        return $deleted;
    }

    public function getPendingPhotos($page = 1, $perpage = 20) {
        $this->joinUser();
        $this->filterEqual('i.is_approved', 0);
        $this->orderBy('i.date_pub', 'asc');
        $this->limitPagePlus($page, $perpage);
        return $this->get('galleryplus_photos', function ($item, $model) {
            $item['image'] = cmsModel::yamlToArray($item['image']);
            $item['sizes'] = cmsModel::yamlToArray($item['sizes']);
            $item['user'] = [
                'id'       => $item['user_id'],
                'nickname' => $item['user_nickname'],
                'slug'     => $item['user_slug'],
                'avatar'   => $item['user_avatar'],
            ];
            $item['url_thumb'] = html_image_src($item['image'], $model->preset_small, true)
                ?: html_image_src($item['image'], 'original', true);
            $item['url_big'] = html_image_src($item['image'], $model->preset_big, true)
                ?: html_image_src($item['image'], 'original', true);
            if (empty($item['title'])) {
                $orig = $item['image']['original'] ?? '';
                if ($orig) {
                    $item['title'] = pathinfo($orig, PATHINFO_FILENAME);
                } else {
                    $thumb_key = $model->preset_small;
                    $thumb_src = $item['image'][$thumb_key] ?? reset($item['image']);
                    $item['title'] = preg_replace('/-' . preg_quote($thumb_key, '/') . '$/', '', pathinfo($thumb_src, PATHINFO_FILENAME));
                }
            }
            return $item;
        }, false);
    }

    public function approvePhoto($id, $moderator_id) {
        return $this->update('galleryplus_photos', $id, [
            'is_approved'  => 1,
            'approved_by'  => $moderator_id,
            'date_approved' => null,
        ]);
    }

    public function getPendingCount() {
        $this->resetFilters();
        $this->filterEqual('is_approved', 0);
        return $this->getCount('galleryplus_photos');
    }

    public function filterPrivacyAlbums($user_id = 0, $include_adult_for_guests = false, $user_karma = 0, $adult_karma = 0) {
        if ($user_id) {
            $this->filterStart();
            $this->filterEqual('i.privacy', 'public');
            $this->filterOr();
            $this->filterEqual('i.privacy', 'adult');
            $this->filterOr();
            $this->filterEqual('i.user_id', $user_id);
            $this->filterEnd();
            return $this;
        }
        if ($include_adult_for_guests) {
            $this->filterStart();
            $this->filterEqual('i.privacy', 'public');
            $this->filterOr();
            $this->filterEqual('i.privacy', 'adult');
            $this->filterEnd();
            return $this;
        }
        $this->filterEqual('i.privacy', 'public');
        return $this;
    }

    public function canAccessAlbum($album, $user_id = 0, $user_karma = 0, $adult_karma = 0, $show_adult_for_guests = false, $user_rating = 0, $adult_rating = 0) {
        if (empty($album['privacy']) || $album['privacy'] === 'public') { return true; }
        if ($user_id && $album['user_id'] == $user_id) { return true; }
        switch ($album['privacy']) {
            case 'private':
                return false;
            case 'friends':
                if (!$user_id) { return false; }
                return cmsCore::getModel('users')->isFriendshipMutual($album['user_id'], $user_id);
            case 'users':
                if (!$user_id || empty($album['privacy_users'])) { return false; }
                $allowed = explode(',', $album['privacy_users']);
                return in_array($user_id, $allowed);
            case 'password':
                return false;
            case 'adult':
                if (!$user_id) {
                    return $show_adult_for_guests;
                }
                $user = cmsUser::getInstance();
                if ($user->is_admin) { return true; }
                $mod = cmsCore::getModel('moderation');
                if ($mod->userIsContentModerator('galleryplus', $user_id)) { return true; }
                if ($adult_karma > 0 && $user_karma < $adult_karma) { return false; }
                if ($adult_rating > 0 && $user_rating < $adult_rating) { return false; }
                return true;
            default:
                return true;
        }
    }

    public function filterVisibleAlbums($user_id = 0, $include_adult_for_guests = false) {
        if ($this->skip_visible_albums_filter) { return $this; }
        $this->joinInner('galleryplus_albums', 'gp_a', 'gp_a.id = i.album_id');
        if ($user_id) {
            $this->filter("(gp_a.privacy = 'public' OR gp_a.privacy = 'adult' OR gp_a.user_id = " . intval($user_id) . ")");
        } elseif ($include_adult_for_guests) {
            $this->filter("(gp_a.privacy = 'public' OR gp_a.privacy = 'adult')");
        } else {
            $this->filter("gp_a.privacy = 'public'");
        }
        $this->filterPaidAlbumPhotos($user_id);
        return $this;
    }

    /**
     * В общей ленте фото платных альбомов без доступа скрываются,
     * кроме открытых превью-фото (album_preview = 1).
     * Владелец, админ и купившие доступ видят все фото.
     */
    public function filterPaidAlbumPhotos($user_id = 0) {
        if (cmsUser::isAdmin()) { return $this; }
        $user_id = (int)$user_id;
        $cond = "(gp_a.is_paid = 0 OR i.album_preview = 1";
        if ($user_id) {
            $cond .= " OR gp_a.user_id = " . $user_id;
            $access = $this->db->getRows('galleryplus_album_access', 'user_id = ' . $user_id, 'album_id');
            if ($access) {
                $ids = array_map('intval', array_column($access, 'album_id'));
                if ($ids) {
                    $cond .= " OR gp_a.id IN (" . implode(',', $ids) . ")";
                }
            }
        }
        $cond .= ")";
        $this->filter($cond);
        return $this;
    }

    public function isAlbumProtected($album) {
        return !empty($album['privacy']) && $album['privacy'] !== 'public';
    }

    public function getUserAlbumsList($user_id) {
        $this->resetFilters();
        $user_id = (int) $user_id;
        $result = $this->db->query("SELECT id, title FROM {#}galleryplus_albums WHERE user_id = {$user_id} ORDER BY title ASC");
        $rows = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Фото альбома для формы редактирования: чекбоксы «показывать без блюра».
     */
    public function getAlbumPhotosForEdit($album_id) {
        $album_id = (int)$album_id;
        $rows = $this->db->getRows(
            'galleryplus_photos',
            'album_id = ' . $album_id,
            'id, title, album_preview, image'
        );
        if (!$rows) { return []; }
        $model = $this;
        $result = [];
        foreach ($rows as $row) {
            $image = cmsModel::yamlToArray($row['image']);
            $row['url_thumb'] = html_image_src($image, $model->preset_small, true)
                ?: html_image_src($image, 'original', true);
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Устанавливает album_preview=1 указанным фото альбома, остальным — 0.
     * Если список пуст — открывает первые 3 фото (превью по умолчанию).
     */
    public function syncAlbumPreviews($album_id, $preview_ids = []) {
        $album_id = (int)$album_id;
        $preview_ids = array_map('intval', $preview_ids);

        if (!$preview_ids) {
            $this->resetFilters();
            $this->filterEqual('album_id', $album_id);
            $this->orderBy('date_pub', 'asc', 'id', 'asc');
            $rows = $this->limit(3)->get('galleryplus_photos', function ($item) {
                return (int)$item['id'];
            }, false);
            $this->resetFilters();
            $preview_ids = $rows ?: [];
        }

        $this->resetFilters();
        $this->filterEqual('album_id', $album_id);
        $this->updateFiltered('galleryplus_photos', ['album_preview' => 0]);

        if ($preview_ids) {
            $this->resetFilters();
            $this->filterIn('id', $preview_ids);
            $this->filterEqual('album_id', $album_id);
            $this->updateFiltered('galleryplus_photos', ['album_preview' => 1]);
        }

        $this->resetFilters();
        return true;
    }

    /**
     * Снимает все флаги preview у фото альбома.
     */
    public function clearAlbumPreviews($album_id) {
        $album_id = (int)$album_id;
        $this->resetFilters();
        $this->filterEqual('album_id', $album_id);
        $this->updateFiltered('galleryplus_photos', ['album_preview' => 0]);
        $this->resetFilters();
        return true;
    }

    /**
     * Если альбом платный и открытых (preview) фото ещё нет —
     * помечает первые 3 как открытые.
     */
    public function setPaidAlbumDefaultPreview($album_id) {
        $album_id = (int)$album_id;
        if (!$album_id) { return; }
        $album = $this->getItemById('galleryplus_albums', $album_id);
        if (!$album || empty($album['is_paid'])) { return; }
        $this->resetFilters();
        $this->filterEqual('album_id', $album_id);
        $this->filterEqual('album_preview', 1);
        $have_preview = $this->getCount('galleryplus_photos') > 0;
        $this->resetFilters();
        if ($have_preview) { return; }
        $this->syncAlbumPreviews($album_id, []);
    }

    public function getUserAlbums($user_id, $page = 1, $perpage = 12) {
        $this->joinUser();
        $this->filterEqual('i.user_id', $user_id);
        $this->orderBy('i.date_pub', 'desc');
        $this->limitPagePlus($page, $perpage);
        return $this->get('galleryplus_albums', function ($item, $model) {
            $item['url'] = href_to('galleryplus', 'album', [$item['slug']]) . '.html';
            $model->skip_visible_albums_filter = true;
            $item['photo_count'] = $model->getPhotosCount($item['id']);
            $model->skip_visible_albums_filter = false;
            $cover = $model->getAlbumCover($item['id']);
            $item['cover_url']    = $cover['url'];
            $item['cover_width']  = $cover['width'];
            $item['cover_height'] = $cover['height'];
            $item['likes_count'] = $model->getAlbumLikesCount($item['id']);
            $item['is_protected'] = $model->isAlbumProtected($item);
            $item['user'] = [
                'id'       => $item['user_id'],
                'nickname' => $item['user_nickname'] ?? '',
                'slug'     => $item['user_slug'] ?? '',
            ];
            return $item;
        }, false);
    }

    public function getUserAlbumsCount($user_id) {
        $this->filterEqual('i.user_id', $user_id);
        $count = $this->getCount('galleryplus_albums');
        $this->resetFilters();
        return $count;
    }

    public function getPhotoServeUrl($photo_id, $preset = 'big') {
        return href_to('galleryplus', 'serve', [$photo_id, $preset]);
    }

    public function canEditAlbum($album, $user_id = 0) {
        if (!$user_id) { return false; }
        if ($album['user_id'] == $user_id) { return true; }
        $user = cmsCore::getModel('users')->getUser($user_id);
        if ($user && !empty($user['is_admin'])) { return true; }
        try {
            if (cmsCore::isModelExists('moderation')) {
                $mod = cmsCore::getModel('moderation');
                if ($mod && method_exists($mod, 'userIsContentModerator')) {
                    return $mod->userIsContentModerator('galleryplus', $user_id);
                }
            }
        } catch (\Throwable $e) {}
        return false;
    }

    public function canUploadToAlbum($album, $user_id = 0) {
        if (!$user_id) { return false; }
        if ($album['user_id'] == $user_id) { return true; }
        if (!empty($album['allow_upload'])) { return true; }
        return false;
    }

    public function updateAlbum($id, $data) {
        $this->resetFilters();
        $this->filterEqual('id', $id);
        return $this->updateFiltered('galleryplus_albums', $data);
    }

    public function deleteAlbum($id, $user_id = 0) {

        $user_id = (int)$user_id;
        if (!$user_id) { $user_id = (int)cmsUser::get('id'); }

        $album = $this->getItemById('galleryplus_albums', $id);

        $this->resetFilters();
        $this->filterEqual('id', $id);
        $photos = $this->get('galleryplus_photos', function ($item, $model) {
            $item['image'] = cmsModel::yamlToArray($item['image']);
            return $item;
        }, false);
        if ($photos) {
            foreach ($photos as $photo) {
                if (!empty($photo['image'])) {
                    foreach ($photo['image'] as $path) {
                        $file = cmsConfig::get('upload_path') . $path;
                        if (is_file($file)) { @unlink($file); }
                    }
                }
                if ($album) {
                    $this->addLog('delete', 'photo', $photo['id'], $photo['title'] ?? '', $album['user_id'], $user_id);
                }
            }
            $photo_ids = array_map('intval', array_column($photos, 'id'));
            if ($photo_ids) {
                $this->resetFilters();
                $this->filterIn('photo_id', $photo_ids);
                $this->deleteFiltered('galleryplus_original_access');
                $this->resetFilters();
            }
        }
        $this->resetFilters();
        $this->filterEqual('album_id', $id);
        $this->deleteFiltered('galleryplus_photos');
        $this->resetFilters();
        $result = $this->delete('galleryplus_albums', $id);
        $this->clearAlbumAccess($id);

        if ($album) {
            $this->addLog('delete', 'album', $id, $album['title'] ?? '', $album['user_id'], $user_id);
        }

        return $result;
    }

    public function getTargetItemInfo($subject, $id) {
        if ($subject === 'photo') {
            $this->joinUser();
            $item = $this->filterEqual('i.id', $id)->getItem('galleryplus_photos');
            $this->resetFilters();
            if (!$item) { return false; }
            $item['image'] = cmsModel::yamlToArray($item['image'] ?? '');
            $item = $this->decoratePhoto($item);
            return [
                'url'   => href_to_rel('galleryplus', $item['slug'] . '.html'),
                'title' => $item['title'] ?: $item['filename'],
            ];
        }
        if ($subject === 'album') {
            $item = $this->getItemById('galleryplus_albums', $id);
            $this->resetFilters();
            if (!$item) { return false; }
            return [
                'url'   => href_to_rel('galleryplus', 'album', [$item['slug']]) . '.html',
                'title' => $item['title'],
            ];
        }
        return false;
    }

    public function updateCommentsCount($subject, $id, $comments_count) {
        if ($subject === 'photo') {
            $this->resetFilters();
            return $this->update('galleryplus_photos', $id, ['comments' => $comments_count]);
        }
        if ($subject === 'album') {
            $this->resetFilters();
            return $this->update('galleryplus_albums', $id, ['comments_count' => $comments_count]);
        }
        return false;
    }

    // ─── Categories ──────────────────────────────────────────

    public function getCategories($page = 1, $perpage = 20) {
        $this->orderBy('i.ordering', 'asc');
        $this->orderBy('i.title', 'asc');
        $this->limitPagePlus($page, $perpage);
        return $this->get('galleryplus_categories', function ($item) {
            $item['url'] = href_to('galleryplus', 'category', [$item['slug']]) . '.html';
            return $item;
        }, false);
    }

    public function getCategoriesCount() {
        $count = $this->getCount('galleryplus_categories');
        $this->resetFilters();
        return $count;
    }

    public function getCategoriesAll() {
        $this->resetFilters();
        $this->orderBy('i.ordering', 'asc');
        $this->orderBy('i.title', 'asc');
        $categories = $this->get('galleryplus_categories', function ($item) {
            $item['url'] = href_to('galleryplus', 'category', [$item['slug']]) . '.html';
            return $item;
        }, false);
        $this->resetFilters();
        return $categories ?: [];
    }

    public function getCategoriesForWidget($limit = 0, $show_hidden = false) {
        $this->resetFilters();
        if (!$show_hidden) {
            $this->filterEqual('i.is_hidden', 0);
        }
        $this->orderBy('i.ordering', 'asc');
        $this->orderBy('i.title', 'asc');
        if ($limit > 0) {
            $this->limit($limit);
        }
        $categories = $this->get('galleryplus_categories', function ($item) {
            $item['url'] = href_to('galleryplus', 'category', [$item['slug']]) . '.html';
            return $item;
        }, false);
        $this->resetFilters();
        return $categories ?: [];
    }

    public function getCategoryById($id) {
        $this->resetFilters();
        $item = $this->getItemById('galleryplus_categories', $id);
        $this->resetFilters();
        return $item;
    }

    public function getGalleryCategoryBySlug($slug) {
        $this->resetFilters();
        $this->filterEqual('slug', $slug);
        $item = $this->getItem('galleryplus_categories');
        $this->resetFilters();
        return $item;
    }

    public function addGalleryCategory($data) {
        $id = $this->insert('galleryplus_categories', $data);
        if ($id) {
            $this->cacheCategoryItemsCount($id);
        }
        return $id;
    }

    public function updateGalleryCategory($id, $data) {
        $this->resetFilters();
        $this->filterEqual('id', $id);
        $result = $this->updateFiltered('galleryplus_categories', $data);
        $this->cacheCategoryItemsCount($id);
        return $result;
    }

    public function deleteGalleryCategory($id) {
        $this->resetFilters();
        $this->filterEqual('category_id', $id);
        $this->updateFiltered('galleryplus_albums', ['category_id' => 0]);
        $this->resetFilters();
        return $this->delete('galleryplus_categories', $id);
    }

    public function cacheCategoryItemsCount($category_id) {
        $this->resetFilters();
        $this->filterEqual('category_id', $category_id);
        $count = $this->getCount('galleryplus_albums');
        $this->resetFilters();
        $this->filterEqual('id', $category_id);
        $this->update('galleryplus_categories', $category_id, ['items_count' => $count]);
        $this->resetFilters();
    }

    public function cacheAllCategoriesCount() {
        $categories = $this->get('galleryplus_categories', function ($item) {
            return $item['id'];
        }, false);
        $this->resetFilters();
        if (!$categories) { return; }
        foreach ($categories as $cat_id) {
            $this->cacheCategoryItemsCount($cat_id);
        }
    }

    public function getAlbumsByCategory($category_id, $page = 1, $perpage = 12, $user_id = 0, $include_adult_for_guests = false) {
        $this->joinUser();
        $this->filterEqual('i.category_id', $category_id);
        $this->orderBy('i.date_pub', 'desc');
        $this->filterPrivacyAlbums($user_id, $include_adult_for_guests, $this->user_karma, $this->adult_karma);
        $this->limitPagePlus($page, $perpage);
        $inc_adult = $include_adult_for_guests;
        $albums = $this->get('galleryplus_albums', function ($item, $model) use ($inc_adult) {
            $item['url'] = href_to('galleryplus', 'album', [$item['slug']]) . '.html';
            $item['photo_count'] = $model->getPhotosCount($item['id'], 0, $inc_adult);
            $cover = $model->getAlbumCover($item['id']);
            $item['cover_url']    = $cover['url'];
            $item['cover_width']  = $cover['width'];
            $item['cover_height'] = $cover['height'];
            $item['likes_count'] = $model->getAlbumLikesCount($item['id']);
            $item['user'] = [
                'id'       => $item['user_id'],
                'nickname' => $item['user_nickname'] ?? '',
                'slug'     => $item['user_slug'] ?? '',
            ];
            return $item;
        }, false);
        return $albums;
    }

    public function getAdjacentPhotos($photo_id, $album_id) {
        $this->resetFilters();
        $current = $this->getItemById('galleryplus_photos', $photo_id);
        if (!$current) { return ['prev' => null, 'next' => null]; }

        $order_val = (int)($current['ordering'] ?? 0);
        $current_id = (int)$current['id'];

        $first_callback = function ($item) {
            $item['image'] = cmsModel::yamlToArray($item['image'] ?? '');
            $item = $this->decoratePhoto($item);
            return [
                'id'    => $item['id'],
                'slug'  => $item['slug'],
                'title' => $item['title'],
                'url'   => $item['url'],
            ];
        };

        $prev = $this->resetFilters()
            ->filterEqual('album_id', $album_id)
            ->filterLt('ordering', $order_val)
            ->orderBy('ordering', 'desc')
            ->getItem('galleryplus_photos', $first_callback);
        if ($prev === false) {
            $prev = $this->resetFilters()
                ->filterEqual('album_id', $album_id)
                ->filterLt('id', $current_id)
                ->filterEqual('ordering', $order_val)
                ->orderBy('id', 'desc')
                ->getItem('galleryplus_photos', $first_callback);
        }

        $next = $this->resetFilters()
            ->filterEqual('album_id', $album_id)
            ->filterGt('ordering', $order_val)
            ->orderBy('ordering', 'asc')
            ->getItem('galleryplus_photos', $first_callback);
        if ($next === false) {
            $next = $this->resetFilters()
                ->filterEqual('album_id', $album_id)
                ->filterGt('id', $current_id)
                ->filterEqual('ordering', $order_val)
                ->orderBy('id', 'asc')
                ->getItem('galleryplus_photos', $first_callback);
        }

        $this->resetFilters();
        return ['prev' => $prev, 'next' => $next];
    }

    /**
     * Похожие фото: по общим тегам (приоритет), затем из того же альбома
     *
     * @param int $photo_id
     * @param int $album_id
     * @param string $photo_slug Текущий slug, чтобы исключить само фото
     * @param int $user_id Текущий пользователь для фильтра видимости
     * @param int $limit
     * @return array
     */
    public function getSimilarPhotos($photo_id, $album_id, $photo_slug = '', $user_id = 0, $limit = 6) {

        $photo_id = (int)$photo_id;
        $limit    = max(1, (int)$limit);

        $include_adult_for_guests = !$user_id && !empty(cmsController::loadOptions('galleryplus')['show_adult_to_guests']);

        // 1) По общим тегам, если теги включены в опциях
        $options = cmsController::loadOptions('galleryplus');
        $use_photo_tags = !empty($options['use_photo_tags']);

        $tag_ids = [];
        if ($use_photo_tags && cmsCore::isModelExists('tags')) {
            $tags_model = cmsCore::getModel('tags');
            $rows = $this->db->query(
                "SELECT tag_id FROM {#}tags_bind WHERE target_controller = 'galleryplus' AND target_subject = 'photo' AND target_id = " . $photo_id
            );
            if ($rows) {
                while ($row = $this->db->fetchAssoc($rows)) {
                    $tag_ids[(int)$row['tag_id']] = true;
                }
            }
        }

        $similar_photo_id = 0;
        if ($tag_ids) {
            $tag_list = implode(',', array_keys($tag_ids));
            $row = $this->db->query(
                "SELECT target_id, COUNT(*) AS cnt
                 FROM {#}tags_bind
                 WHERE target_controller = 'galleryplus' AND target_subject = 'photo'
                   AND target_id <> " . $photo_id . "
                   AND tag_id IN ({$tag_list})
                 GROUP BY target_id
                 ORDER BY cnt DESC
                 LIMIT 1"
            );
            $r = $row ? $this->db->fetchAssoc($row) : null;
            if ($r) { $similar_photo_id = (int)$r['target_id']; }
        }

        $this->resetFilters();
        $this->joinUser();
        if (!$this->privacy_filter_disabled) { $this->filterPrivacy(); }
        if (!$this->approved_filter_disabled) { $this->filterApprovedOnly(); }
        $this->skip_visible_albums_filter = true; // в page видимость уже проверена у текущего фото

        $this->filterNotEqual('i.id', $photo_id);

        if ($similar_photo_id) {
            // приоритет: фото с общим тегом, затем из того же альбома
            $this->filterStart();
            $this->filterEqual('i.id', $similar_photo_id);
            $this->filterOr();
            $this->filterEqual('i.album_id', $album_id);
            $this->filterEnd();
            $this->orderByRaw('(i.id = ' . $similar_photo_id . ') desc, i.date_pub desc');
        } else {
            $this->filterEqual('i.album_id', $album_id);
            $this->orderBy('i.date_pub', 'desc');
        }

        $this->limit($limit);

        $result = $this->get('galleryplus_photos', function ($item, $model) {
            $item['image'] = cmsModel::yamlToArray($item['image']);
            $item['sizes'] = cmsModel::yamlToArray($item['sizes']);
            $item['exif']  = cmsModel::yamlToArray($item['exif']);
            $item['user'] = [
                'id'       => $item['user_id'],
                'nickname' => $item['user_nickname'],
                'slug'     => $item['user_slug'],
                'avatar'   => $item['user_avatar'],
            ];
            $item = $this->decoratePhoto($item);
            $item['url_thumb']    = html_image_src($item['image'], $model->preset_small, true);
            $item['url_big']      = html_image_src($item['image'], $model->preset_big, true)
                ?: html_image_src($item['image'], 'normal', true);
            $item['url_nocrop']   = html_image_src($item['image'], $model->preset_nocrop, true);
            $item['url_original'] = html_image_src($item['image'], 'original', true);
            return $item;
        }, false);
        $this->resetFilters();
        $this->skip_visible_albums_filter = false;

        return $result ?: [];
    }

    public function getAlbumsCountByCategory($category_id, $user_id = 0, $include_adult_for_guests = false) {
        $this->filterEqual('i.category_id', $category_id);
        $this->filterPrivacyAlbums($user_id, $include_adult_for_guests, $this->user_karma, $this->adult_karma);
        $count = $this->getCount('galleryplus_albums');
        $this->resetFilters();
        return $count;
    }

    // ─── Audit log ─────────────────────────────────────────

    public function isLoggingEnabled() {
        $options = cmsController::loadOptions('galleryplus');
        return !empty($options['logging_enabled']);
    }

    public function addLog($action, $target_type, $target_id, $title = '', $owner_id = 0, $user_id = null) {
        if (!$this->isLoggingEnabled()) { return false; }
        if ($user_id === null) { $user_id = cmsUser::get('id'); }
        return $this->db->insert('galleryplus_logs', [
            'action'      => $action,
            'target_type' => $target_type,
            'target_id'   => (int)$target_id,
            'title'       => (string)$title,
            'user_id'     => (int)$user_id,
            'owner_id'    => (int)$owner_id,
            'date_pub'    => date('Y-m-d H:i:s'),
        ]);
    }

    public function clearLogs() {
        $this->db->truncateTable('galleryplus_logs');
        return true;
    }

    public function getLogsCount() {
        $count = $this->getCount('galleryplus_logs');
        $this->resetFilters();
        return $count;
    }

    public function getLogs($page = 1, $perpage = 20) {
        $this->joinUser('user_id', ['u.nickname' => 'user_nickname'], 'left', 'u');
        $this->joinUser('owner_id', ['o.nickname' => 'owner_nickname'], 'left', 'o');
        $this->orderBy('i.date_pub', 'desc');
        return $this->get('galleryplus_logs', function ($item, $model) {
            $item['user_name']  = !empty($item['user_nickname'])  ? $item['user_nickname']  : '—';
            $item['owner_name'] = !empty($item['owner_nickname']) ? $item['owner_nickname'] : '—';
            return $item;
        });
    }

    public function deleteLog($id) {
        $this->resetFilters();
        $this->filterEqual('id', $id);
        return $this->delete('galleryplus_logs', $id);
    }

//============================================================================//
//  Проверка обновлений на GitHub
//============================================================================//

    const GITHUB_REPO           = 'Kupers/galleryplus-instantcms';
    const UPDATE_CHECK_INTERVAL = 21600;

    public function getUpdateInfo() {
        return $this->db->getRow('galleryplus_updates', 'id = 1', '*') ?: [];
    }

    public function saveUpdateInfo($latest_version, $release_url) {

        $latest_version = $latest_version ? "'" . $this->db->escape($latest_version) . "'" : 'NULL';
        $release_url    = $release_url    ? "'" . $this->db->escape($release_url)    . "'" : 'NULL';

        return $this->db->query(
            "INSERT INTO `{#}galleryplus_updates` (`id`, `latest_version`, `release_url`, `checked_at`)
             VALUES (1, {$latest_version}, {$release_url}, NOW())
             ON DUPLICATE KEY UPDATE `latest_version` = VALUES(`latest_version`), `release_url` = VALUES(`release_url`), `checked_at` = NOW()"
        );
    }

    public function getInstalledComponentVersion() {
        $row = $this->db->getRow('controllers', "name = 'galleryplus'", 'version');
        return isset($row['version']) ? (string)$row['version'] : '';
    }

    public function getAdminUserIds() {
        $rows = $this->db->getRows('users', 'is_admin = 1', 'id');
        if (!$rows) { return []; }
        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int)$row['id'];
        }
        return $ids;
    }

    public function fetchLatestRelease() {

        if (!function_exists('curl_init')) {
            return [null, null];
        }

        $url = 'https://api.github.com/repos/' . self::GITHUB_REPO . '/releases/latest';

        $data = file_get_contents_from_url($url, 5, true);

        if (!is_array($data) || empty($data['tag_name'])) {
            return [null, null];
        }

        $tag = ltrim((string)$data['tag_name'], 'vV');

        return [$tag, isset($data['html_url']) ? (string)$data['html_url'] : ''];
    }

    public function checkForUpdates() {

        $cache = $this->getUpdateInfo();

        $checked_at = !empty($cache['checked_at']) ? strtotime($cache['checked_at']) : 0;

        if (!$checked_at || (time() - $checked_at) >= self::UPDATE_CHECK_INTERVAL) {

            list($latest, $release_url) = $this->fetchLatestRelease();

            $this->saveUpdateInfo($latest, $release_url);

            $cache = [
                'latest_version' => $latest,
                'release_url'    => $release_url,
            ];
        }

        if (empty($cache['latest_version'])) {
            return null;
        }

        $installed = $this->getInstalledComponentVersion();
        $latest    = ltrim((string)$cache['latest_version'], 'vV');

        if (!$installed || version_compare($latest, $installed) <= 0) {
            return null;
        }

        return [
            'latest_version'    => $latest,
            'installed_version' => $installed,
            'release_url'       => isset($cache['release_url']) ? (string)$cache['release_url'] : '',
        ];
    }

    public function notifyAdminsAboutUpdate($latest_version, $release_url) {

        $cache = $this->getUpdateInfo();

        if (!empty($cache['notified_version']) && $cache['notified_version'] === $latest_version) {
            return false;
        }

        $admin_ids = $this->getAdminUserIds();
        if (!$admin_ids) {
            return false;
        }

        $text = sprintf(
            LANG_GALLERYPLUS_UPDATE_PM_TEXT,
            $latest_version,
            $release_url ?: 'https://github.com/' . self::GITHUB_REPO . '/releases'
        );

        $sender = (int)cmsUser::get('id');
        if (!$sender) { $sender = 1; }

        try {

            foreach ($admin_ids as $admin_id) {

                $contact = $this->db->getRow('{users}_contacts', 'user_id = ' . (int)$admin_id . ' AND contact_id = ' . (int)$sender, 'id');

                if (!$contact) {
                    $this->db->query(
                        "INSERT INTO `{users}_contacts` (`user_id`, `contact_id`) VALUES (" . (int)$admin_id . ", " . (int)$sender . ")"
                    );
                }

                $this->db->query(
                    "INSERT INTO `{users}_messages` (`from_id`, `to_id`, `content`) VALUES (" .
                    (int)$sender . ", " . (int)$admin_id . ", '" . $this->db->escape($text) . "')"
                );
            }

        } catch (\Throwable $e) {}

        return $this->db->query(
            "UPDATE `{#}galleryplus_updates` SET `notified_version` = '" . $this->db->escape($latest_version) . "' WHERE `id` = 1"
        );
    }

}
