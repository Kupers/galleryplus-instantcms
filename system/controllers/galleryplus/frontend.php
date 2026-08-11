<?php

class galleryplus extends cmsFrontend {

    protected $useOptions = true;

    public $useSeoOptions = true;

    public function runAction($action_name, $params = []) {
        if ($action_name !== 'index' || $params) {
            $uri = $this->name;
            if ($action_name !== 'index') {
                $uri .= '/' . $action_name;
            }
            if ($params) {
                $uri .= '/' . implode('/', $params);
            }
            $parsed = $this->parseRoute($uri);
            if ($parsed) {
                return parent::runAction($parsed);
            }
        }
        return parent::runAction($action_name, $params);
    }

    public function route($uri) {
        $action_name = $this->parseRoute($this->cms_core->uri);
        if (!$action_name) {
            return cmsCore::error404();
        }
        parent::runAction($action_name);
    }

    public function getOptions() {
        return cmsController::loadOptions('galleryplus');
    }

    public function applyAdultFilter($photos) {
        $show_in_feed = array_key_exists('show_adult_in_feed', $this->options) ? (bool)$this->options['show_adult_in_feed'] : true;
        $show_to_guests = $this->options['show_adult_to_guests'] ?? 0;
        $user_id = $this->cms_user->id;

        if (!$photos) { return $photos; }

        $album_ids = array_unique(array_filter(array_map(function($p) {
            return $p['album_id'] ?? 0;
        }, $photos)));

        if (!$show_in_feed && !$user_id && !$show_to_guests) {
            return [];
        }

        $adult_albums = [];
        if ($album_ids) {
            $ids = implode(',', array_map('intval', $album_ids));
            $result = $this->model->db->query("SELECT id, privacy, user_id FROM {#}galleryplus_albums WHERE id IN ({$ids})");
            $rows = $result ? $this->model->db->fetchAll($result) : [];
            if ($rows) {
                foreach ($rows as $row) {
                    if ($row['privacy'] === 'adult') {
                        $adult_albums[(int)$row['id']] = (int)$row['user_id'];
                    }
                }
            }
        }

        $show_adult = ($show_in_feed && $user_id) || ($show_to_guests);

        $result = [];
        foreach ($photos as $p) {
            $aid = $p['album_id'] ?? 0;
            if (isset($adult_albums[$aid])) {
                $owner_id = $adult_albums[$aid];
                if ($user_id && $user_id == $owner_id) {
                    $p['is_adult'] = false;
                    $result[] = $p;
                } elseif ($show_adult) {
                    $p['is_adult'] = true;
                    $result[] = $p;
                }
            } else {
                $result[] = $p;
            }
        }

        return $result;
    }

}
