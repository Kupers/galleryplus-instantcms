<?php

/**
 * Секретные ссылки (share-token).
 *
 *  GET  /galleryplus/s/{token}  — переход по секретной ссылке
 *  POST /galleryplus/share      — управление ссылками (AJAX, только владелец/админ)
 */
class actionGalleryplusShare extends cmsAction {

    const DEVICE_COOKIE = 'galleryplus_share_device';

    public function run($token = null) {

        $sub = $this->request->get('action', '');
        if ($sub) {
            return $this->manage($sub);
        }

        if (!$token) { $token = $this->request->get('token', ''); }
        if (!$token) { return cmsCore::error404(); }

        return $this->visit($token);
    }

//============================================================================//
//  Переход по ссылке
//============================================================================//

    private function visit($token) {

        $link = $this->model->getShareLinkByToken($token);
        if (!$link || empty($link['is_active'])) { return cmsCore::error404(); }

        if (!empty($link['expires_at']) && strtotime($link['expires_at']) < time()) {
            return $this->renderBlocked(LANG_GALLERYPLUS_SHARE_EXPIRED);
        }

        $photo    = null;
        $album_id = (int)$link['album_id'];

        if (!empty($link['photo_id'])) {
            $photo = $this->model->getItemById('galleryplus_photos', (int)$link['photo_id']);
            if (!$photo) { return cmsCore::error404(); }
            $album_id = (int)$photo['album_id'];
        }

        $album = $album_id ? $this->model->getAlbum($album_id) : null;
        if (!$album) { return cmsCore::error404(); }

        // Автоматические запросы (предпросмотр ссылок в мессенджерах/соцсетях, боты)
        // не должны расходовать ссылку: не регистрируем устройство, не считаем переход
        // и не закрываем одноразовую ссылку. Показываем страницу-прослойку с кнопкой;
        // реальный пользователь нажимает её (?open=1) и получает доступ.
        $force_open = ((int)$this->request->get('open', 0) === 1);
        if (!$force_open && $this->isAutomatedRequest()) {
            return $this->renderPreview($link, $album, $photo);
        }

        // Ограничение по количеству устройств / привязка к устройству
        $device_id   = $this->getDeviceId();
        $max_devices = (int)$link['max_devices'];
        if ($max_devices > 0 && !$this->model->isShareDeviceKnown($link['id'], $device_id)
                && $this->model->countShareDevices($link['id']) >= $max_devices) {
            return $this->renderBlocked(LANG_GALLERYPLUS_SHARE_DEVICES_LIMIT);
        }

        $this->model->registerShareDevice(
            $link['id'],
            $device_id,
            isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '',
            isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : ''
        );
        $this->model->touchShareLink($link['id']);
        $this->model->grantShareAccess($album_id);

        // Одноразовый просмотр: после первого перехода ссылка закрывается
        if (!empty($link['single_use'])) {
            $this->model->setShareLinkActive($link['id'], 0);
        }

        if ($photo) {
            return $this->redirectTo('galleryplus', $photo['slug'] . '.html');
        }
        return $this->redirectTo('galleryplus', 'album', [$album['slug'] . '.html']);
    }

    private function renderBlocked($message) {
        $this->cms_template->addBreadcrumb(
            defined('LANG_GALLERYPLUS_TITLE') ? LANG_GALLERYPLUS_TITLE : 'Gallery',
            href_to('galleryplus')
        );
        return $this->cms_template->render('share_blocked', [
            'message' => $message,
        ]);
    }

    // Страница-прослойка для предпросмотра/роботов: без расхода ссылки
    private function renderPreview($link, $album, $photo) {
        $title = $photo ? $photo['title'] : $album['title'];

        $this->cms_template->addBreadcrumb(
            defined('LANG_GALLERYPLUS_TITLE') ? LANG_GALLERYPLUS_TITLE : 'Gallery',
            href_to('galleryplus')
        );

        return $this->cms_template->render('share_preview', [
            'title'    => $title,
            'open_url' => href_to('galleryplus', 's', [$link['token']]) . '?open=1',
        ]);
    }

    // Определяем автоматический запрос (робот/предпросмотр), который не должен
    // расходовать ссылку. Доверяем только «настоящим» браузерным движкам.
    private function isAutomatedRequest() {

        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string)$_SERVER['REQUEST_METHOD']) : 'GET';
        if ($method !== 'GET') { return true; }

        foreach (['HTTP_PURPOSE', 'HTTP_X_PURPOSE', 'HTTP_X_MOZ', 'HTTP_SEC_PURPOSE', 'HTTP_X_PREFETCH'] as $h) {
            if (!empty($_SERVER[$h])) { return true; }
        }

        if (isset($_SERVER['HTTP_SEC_FETCH_MODE']) && strtolower((string)$_SERVER['HTTP_SEC_FETCH_MODE']) !== 'navigate') { return true; }
        if (isset($_SERVER['HTTP_SEC_FETCH_DEST']) && strtolower((string)$_SERVER['HTTP_SEC_FETCH_DEST']) !== 'document') { return true; }

        $ua     = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower(trim((string)$_SERVER['HTTP_USER_AGENT'])) : '';
        $accept = isset($_SERVER['HTTP_ACCEPT']) ? strtolower((string)$_SERVER['HTTP_ACCEPT']) : '';

        if ($ua === '' || strpos($accept, 'text/html') === false) { return true; }

        $markers = [
            'bot', 'crawler', 'spider', 'slurp', 'curl', 'wget', 'python', 'guzzle',
            'okhttp', 'go-http-client', 'java/', 'libwww', 'httpclient', 'http-client',
            'facebookexternalhit', 'facebookcatalog', 'facebot', 'facebook',
            'telegram', 'whatsapp', 'slackbot', 'discord', 'twitterbot', 'skype',
            'linkedinbot', 'embedly', 'iframely', 'metainspector', 'opengraph',
            'unfurl', 'scraper', 'scrape', 'preview', 'headless', 'phantomjs',
            'puppeteer', 'playwright', 'axios', 'node-fetch', 'rss', 'feed',
            'monitor', 'uptime', 'pingdom', 'statuscake', 'validator', 'audit',
            'semrush', 'ahrefs', 'mj12', 'dotbot', 'yandex', 'googlebot', 'bingbot',
            'baidu', 'applebot', 'petalbot', 'sogou', 'exabot', 'ia_archiver',
            'archive.org', 'bitly', 'pinterest', 'redditbot', 'tumblr', 'snapchat',
            'nextcloud', 'owncloud', 'synology', 'zabbix', 'nagios', 'vk', 'mail.ru',
            'maxbot',
        ];
        foreach ($markers as $m) {
            if (strpos($ua, $m) !== false) { return true; }
        }

        $browser = (strpos($ua, 'mozilla') !== false) && preg_match(
            '~chrome/|crios/|safari/|firefox/|gecko/|edg/|edgios/|edga/|opr/|opera|trident/|msie|yaapp|yabrowser|samsungbrowser|ucbrowser|vivaldi~',
            $ua
        );

        return !$browser;
    }

    private function getDeviceId() {
        $name = self::DEVICE_COOKIE;
        $id   = isset($_COOKIE[$name]) ? (string)$_COOKIE[$name] : '';
        if (!preg_match('/^[a-f0-9]{32}$/i', $id)) {
            $id = bin2hex(random_bytes(16));
            $this->setDeviceCookie($name, $id);
        }
        return strtolower($id);
    }

    private function setDeviceCookie($name, $value) {
        if (!headers_sent()) {
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            @setcookie($name, $value, time() + 31536000, '/', '', $secure, true);
        }
        $_COOKIE[$name] = $value;
    }

//============================================================================//
//  Управление ссылками (AJAX)
//============================================================================//

    private function manage($sub) {

        if (!$this->cms_user->id) {
            return $this->cms_template->renderJSON(['error' => LANG_GALLERYPLUS_SHARE_ERR_LOGIN]);
        }
        if (!cmsForm::validateCSRFToken($this->request->get('csrf_token', ''))) {
            return $this->cms_template->renderJSON(['error' => LANG_FORM_ERRORS]);
        }

        switch ($sub) {
            case 'share_list':          return $this->ajaxList();
            case 'share_create':        return $this->ajaxCreate();
            case 'share_revoke':        return $this->ajaxSetActive(0);
            case 'share_activate':      return $this->ajaxSetActive(1);
            case 'share_regenerate':    return $this->ajaxRegenerate();
            case 'share_delete':        return $this->ajaxDelete();
            case 'share_devices':       return $this->ajaxDevices();
            case 'share_reset_devices': return $this->ajaxResetDevices();
        }

        return $this->cms_template->renderJSON(['error' => 'Unknown action']);
    }

    private function resolveTarget() {
        $album_id = (int)$this->request->get('album_id', 0);
        $photo_id = (int)$this->request->get('photo_id', 0);

        $photo = null;
        $album = null;

        if ($photo_id) {
            $photo = $this->model->getItemById('galleryplus_photos', $photo_id);
            if (!$photo) { return [null, null]; }
            $album_id = (int)$photo['album_id'];
        }
        if ($album_id) {
            $album = $this->model->getItemById('galleryplus_albums', $album_id);
        }
        return [$album, $photo];
    }

    private function canManageLink($link) {
        if (empty($link)) { return false; }
        if ($this->cms_user->is_admin) { return true; }
        return (int)$link['user_id'] === (int)$this->cms_user->id;
    }

    private function linkToArray($link) {
        $devices = $this->model->countShareDevices($link['id']);
        $expired = !empty($link['expires_at']) && strtotime($link['expires_at']) < time();
        return [
            'id'           => (int)$link['id'],
            'token'        => $link['token'],
            'url'          => $this->shareUrl($link['token']),
            'photo_id'     => (int)$link['photo_id'],
            'expires_at'   => $link['expires_at'],
            'is_expired'   => $expired,
            'max_devices'  => (int)$link['max_devices'],
            'single_use'   => !empty($link['single_use']),
            'is_active'    => !empty($link['is_active']),
            'opens'        => (int)$link['opens'],
            'devices'      => $devices,
            'last_open_at' => $link['last_open_at'],
            'date_pub'     => $link['date_pub'],
        ];
    }

    private function shareUrl($token) {
        $host = rtrim((string)cmsConfig::get('host'), '/');
        return $host . href_to('galleryplus', 's', [$token]);
    }

    private function ajaxList() {
        list($album, $photo) = $this->resolveTarget();
        if (!$album) { return $this->cms_template->renderJSON(['error' => LANG_GALLERYPLUS_SHARE_ERR_TARGET]); }

        $photo_id = $photo ? (int)$photo['id'] : 0;
        $links = $this->model->getShareLinks($album['id'], $photo_id);

        $out = [];
        foreach ($links as $link) {
            $out[] = $this->linkToArray($link);
        }

        return $this->cms_template->renderJSON([
            'success'  => true,
            'links'    => $out,
            'album_id' => (int)$album['id'],
            'photo_id' => $photo_id,
            'is_owner' => $this->cms_user->is_admin || (int)$album['user_id'] === (int)$this->cms_user->id,
        ]);
    }

    private function ajaxCreate() {
        list($album, $photo) = $this->resolveTarget();
        if (!$album) { return $this->cms_template->renderJSON(['error' => LANG_GALLERYPLUS_SHARE_ERR_TARGET]); }

        $is_admin = $this->cms_user->is_admin;
        $owner_id = $photo ? (int)$photo['user_id'] : (int)$album['user_id'];
        if (!$is_admin && $owner_id !== (int)$this->cms_user->id) {
            return $this->cms_template->renderJSON(['error' => LANG_GALLERYPLUS_SHARE_ERR_ACCESS]);
        }

        $days = (int)$this->request->get('days', 7);
        if ($days < 0) { $days = 0; }
        if ($days > 30) { $days = 30; }

        $max_devices = (int)$this->request->get('max_devices', 0);
        if ($max_devices < 0) { $max_devices = 0; }

        $single_use = (int)$this->request->get('single_use', 0) ? 1 : 0;

        $created = $this->model->createShareLink([
            'album_id'    => (int)$album['id'],
            'photo_id'    => $photo ? (int)$photo['id'] : 0,
            'user_id'     => (int)$this->cms_user->id,
            'expires_at'  => $days > 0 ? date('Y-m-d H:i:s', strtotime('+' . $days . ' days')) : null,
            'max_devices' => $max_devices,
            'single_use'  => $single_use,
        ]);

        if (!$created) {
            return $this->cms_template->renderJSON(['error' => LANG_GALLERYPLUS_SHARE_ERR_CREATE]);
        }

        $this->model->addLog('create', 'share', $created['id'], $album['title'] ?? '', $owner_id, $this->cms_user->id);

        $link = $this->model->getShareLink($created['id']);
        return $this->cms_template->renderJSON([
            'success' => true,
            'link'    => $link ? $this->linkToArray($link) : null,
        ]);
    }

    private function ajaxSetActive($active) {
        $link = $this->loadManagedLink();
        if (is_array($link) && isset($link['error'])) { return $this->cms_template->renderJSON($link); }

        $this->model->setShareLinkActive($link['id'], $active);
        $fresh = $this->model->getShareLink($link['id']);
        return $this->cms_template->renderJSON(['success' => true, 'link' => $this->linkToArray($fresh)]);
    }

    private function ajaxRegenerate() {
        $link = $this->loadManagedLink();
        if (is_array($link) && isset($link['error'])) { return $this->cms_template->renderJSON($link); }

        $this->model->regenerateShareLink($link['id']);
        $fresh = $this->model->getShareLink($link['id']);
        return $this->cms_template->renderJSON(['success' => true, 'link' => $this->linkToArray($fresh)]);
    }

    private function ajaxDelete() {
        $link = $this->loadManagedLink();
        if (is_array($link) && isset($link['error'])) { return $this->cms_template->renderJSON($link); }

        $this->model->deleteShareLink($link['id']);
        return $this->cms_template->renderJSON(['success' => true, 'deleted' => (int)$link['id']]);
    }

    private function ajaxDevices() {
        $link = $this->loadManagedLink();
        if (is_array($link) && isset($link['error'])) { return $this->cms_template->renderJSON($link); }

        $devices = $this->model->getShareDevices($link['id']);
        $out = [];
        foreach ($devices as $d) {
            $out[] = [
                'id'         => (int)$d['id'],
                'device_id'  => substr((string)$d['device_id'], 0, 8) . '…',
                'ip'         => $d['ip'],
                'user_agent' => $d['user_agent'],
                'date_pub'   => $d['date_pub'],
            ];
        }
        return $this->cms_template->renderJSON(['success' => true, 'devices' => $out]);
    }

    private function ajaxResetDevices() {
        $link = $this->loadManagedLink();
        if (is_array($link) && isset($link['error'])) { return $this->cms_template->renderJSON($link); }

        $this->model->clearShareDevices($link['id']);
        return $this->cms_template->renderJSON(['success' => true]);
    }

    private function loadManagedLink() {
        $link = $this->model->getShareLink((int)$this->request->get('link_id', 0));
        if (!$link) {
            return ['error' => LANG_GALLERYPLUS_SHARE_ERR_TARGET];
        }
        if (!$this->canManageLink($link)) {
            return ['error' => LANG_GALLERYPLUS_SHARE_ERR_ACCESS];
        }
        return $link;
    }

}
