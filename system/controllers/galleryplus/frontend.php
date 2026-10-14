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
        return $this->model->applyAdultFilter($photos, (int)$this->cms_user->id);
    }

    /**
     * Пользователь подтвердил возраст 18+ (админ/модератор или дата рождения).
     */
    public function isAdultConfirmed() {
        return $this->model->isAdultConfirmed((int)$this->cms_user->id);
    }

    /**
     * 1 — блюр для этого пользователя снимать нельзя (возраст не подтверждён).
     */
    public function isAdultLocked() {
        return $this->isAdultConfirmed() ? 0 : 1;
    }

    /**
     * Строки интерфейса секретных ссылок для передачи в JS.
     */
    public function shareI18n() {
        return galleryplus_share_i18n();
    }

//============================================================================//
// Billing-интеграция (платная загрузка, просмотр альбома, оригинал)
//============================================================================//

    /** @var bool|object|null кэш контроллера billing */
    private $_billing = null;

    public function billingAvailable() {
        return cmsController::enabled('billing');
    }

    public function billing() {
        if ($this->_billing === null) {
            if (!$this->billingAvailable()) {
                $this->_billing = false;
            } else {
                $this->_billing = cmsCore::getController('billing');
            }
        }
        return $this->_billing;
    }

    /**
     * Цена действия для текущего (или заданного) пользователя.
     * 0 = действие не оплачивается.
     */
    public function billingPrice($name, $user_id = 0) {
        $b = $this->billing();
        if (!$b) { return 0.0; }
        // Админ оплачивает всё бесплатно
        if ($this->cms_user->is_admin) { return 0.0; }
        if (!$user_id) { $user_id = (int)$this->cms_user->id; }
        list($price, $action) = $b->getPriceAndAction('galleryplus', $name, $user_id);
        return (float)$price;
    }

    /**
     * Цена с валютой для вывода (все три формы валидны для html_spellcount).
     */
    public function billingSpellPrice($num, $currency = '') {
        $num = (float)$num;
        if ($num <= 0) { return '0'; }
        if ($currency === '') { $currency = 'р.'; }
        if (strpos($currency, '|') !== false) {
            // Валюта уже задана тремя формами: руб.|рубля|рублей
            return html_spellcount($num, $currency);
        }
        return html_spellcount($num, $currency, $currency, $currency);
    }

    /**
     * Фича активна для пользователя, если действие существует и цена > 0.
     */
    public function billingActive($name) {
        return $this->billingPrice($name) > 0;
    }

    /**
     * Списание за действие. Внутри processAction проверяет открытую
     * транзакцию: если она запущена на billing-модели, коммит не делается.
     * При передаче $amount списывается своя сумма (пер-альбомная цена);
     * запись в журнал сохраняет привязку к billing-действию.
     */
    public function billingCharge($name, $user_id, $amount = null) {
        $b = $this->billing();
        if (!$b || !$user_id) { return true; }
        if ($amount === null) {
            return $b->processAction('galleryplus', $name, $user_id);
        }
        $amount = (float)$amount;
        if ($amount <= 0) { return true; }
        list(, $action) = $b->getPriceAndAction('galleryplus', $name, $user_id);
        if (!$action) { return true; }
        return $b->model->decrementUserBalance($user_id, $amount, $action['title'], (int)$action['id']);
    }

    /**
     * Цена просмотра альбома: своя цена владельца (поле price), если задана,
     * иначе — глобальная цена админа из Биллинга.
     */
    public function albumViewPrice($album) {
        $custom = $album['price'] ?? null;
        if ($custom === null || $custom === '' || !is_numeric($custom)) {
            return $this->billingPrice('view_album');
        }
        return (float)$custom;
    }

    /**
     * Наименьшая положительная цена действия среди всех групп.
     * Нужна для paywall гостю, у которого группа (0) в прайсе не задана.
     */
    public function billingAnyPositivePrice($name) {
        $b = $this->billing();
        if (!$b) { return 0.0; }
        list(, $action) = $b->getPriceAndAction('galleryplus', $name);
        if (!$action) { return 0.0; }
        $found = 0.0;
        foreach ((array)$action['prices'] as $price) {
            $price = (float)$price;
            if ($price > 0 && ($found <= 0 || $price < $found)) {
                $found = $price;
            }
        }
        return $found;
    }

    /**
     * Цена, по которой альбом считается платным и показывается в paywall.
     * Гостю возвращается минимальная цена действия view_album, даже если для
     * его группы она не задана — иначе незарегистрированные обходили бы оплату.
     */
    public function albumLockPrice($album, $user_id = 0) {
        $custom = isset($album['price']) ? trim((string)$album['price']) : '';
        if ($custom !== '' && is_numeric($custom)) {
            $price = (float)$custom;
            if ($price >= 0) { return $price; }
        }
        $price = $this->billingPrice('view_album', $user_id);
        if ($price > 0) { return $price; }
        if (!$user_id) {
            $price = $this->billingAnyPositivePrice('view_album');
            if ($price > 0) { return $price; }
        }
        return 0.0;
    }

    /**
     * Услуга активна глобально (хотя бы для одной группы цена > 0).
     * Используется для гейтинга url-ов оригинала.
     */
    public function billingEnabledFeature($name) {
        $b = $this->billing();
        if (!$b) { return false; }
        list(, $action) = $b->getPriceAndAction('galleryplus', $name);
        if (!$action) { return false; }
        foreach ((array)$action['prices'] as $price) {
            if ((float)$price > 0) { return true; }
        }
        return false;
    }

    public function billingBalance($user_id = 0) {
        $b = $this->billing();
        if (!$b) { return 0.0; }
        if (!$user_id) { $user_id = (int)$this->cms_user->id; }
        return (float)$b->model->getUserBalance($user_id);
    }

    /**
     * Не-AJAX вход: если действие платное и средств не хватает,
     * billing сам переводит на пополнение и завершает запрос.
     * Возвращает true, если можно продолжать.
     */
    public function billingEnsureBalance($name, $back_url = '') {
        $b = $this->billing();
        if (!$b || !$this->billingActive($name)) { return true; }
        return $b->checkBalanceForAction('galleryplus', $name, $back_url ?: $this->cms_core->uri_absolute);
    }

    /**
     * AJAX (JSON) проверка баланса перед списанием.
     * true — можно продолжать; иначе массив ['error'=>..., 'redirect'=>...]
     */
    public function billingAjaxCheck($name, $back_url = '') {
        $b = $this->billing();
        if (!$b || !$this->billingActive($name)) { return true; }
        $price   = $this->billingPrice($name);
        $balance = $this->billingBalance();
        if ($price <= $balance) { return true; }
        return [
            'error'    => LANG_GALLERYPLUS_BILLING_NOT_ENOUGH,
            'redirect' => href_to('billing', 'deposit'),
        ];
    }

    /**
     * Сумма, зачисляемая автору продажи после удержания комиссии сайта.
     * Комиссия берётся только если включена опция billing_take_percent,
     * иначе автор получает всю сумму.
     */
    public function billingAuthorPayout($price) {
        $price = (float)$price;
        if ($price <= 0) { return 0.0; }
        $percent = 0.0;
        if (!empty($this->options['billing_take_percent'])) {
            $percent = (float)($this->options['billing_percent'] ?? 0);
            $percent = max(0.0, min(100.0, $percent));
        }
        return round($price * (100 - $percent) / 100, 2);
    }

}

if (!function_exists('galleryplus_share_i18n')) {
    /**
     * Строки интерфейса секретных ссылок (для шаблонов и JS).
     */
    function galleryplus_share_i18n() {
        $t = function ($name, $default = '') {
            return defined($name) ? constant($name) : $default;
        };
        return [
            'title'          => $t('LANG_GALLERYPLUS_SHARE_TITLE', 'Секретная ссылка'),
            'hint'           => $t('LANG_GALLERYPLUS_SHARE_HINT', ''),
            'create'         => $t('LANG_GALLERYPLUS_SHARE_CREATE', 'Создать ссылку'),
            'expires'        => $t('LANG_GALLERYPLUS_SHARE_EXPIRES', 'Срок действия'),
            'unlimited'      => $t('LANG_GALLERYPLUS_SHARE_UNLIMITED', 'Бессрочно'),
            'daysSuffix'     => $t('LANG_GALLERYPLUS_SHARE_DAYS', '%s дн.'),
            'devicesToggle'  => $t('LANG_GALLERYPLUS_SHARE_DEVICES_TOGGLE', ''),
            'devicesHint'    => $t('LANG_GALLERYPLUS_SHARE_DEVICES_HINT', ''),
            'devicesCount'   => $t('LANG_GALLERYPLUS_SHARE_DEVICES_COUNT', 'Устройств'),
            'singleToggle'   => $t('LANG_GALLERYPLUS_SHARE_SINGLE_TOGGLE', ''),
            'singleHint'     => $t('LANG_GALLERYPLUS_SHARE_SINGLE_HINT', ''),
            'hidePreviewToggle' => $t('LANG_GALLERYPLUS_SHARE_HIDE_PREVIEW_TOGGLE', ''),
            'hidePreviewHint'   => $t('LANG_GALLERYPLUS_SHARE_HIDE_PREVIEW_HINT', ''),
            'copy'           => $t('LANG_GALLERYPLUS_SHARE_COPY', 'Скопировать'),
            'copied'         => $t('LANG_GALLERYPLUS_SHARE_COPIED', 'Скопировано'),
            'revoke'         => $t('LANG_GALLERYPLUS_SHARE_REVOKE', 'Отозвать'),
            'activate'       => $t('LANG_GALLERYPLUS_SHARE_ACTIVATE', 'Включить'),
            'regenerate'     => $t('LANG_GALLERYPLUS_SHARE_REGENERATE', 'Перевыпустить'),
            'delete'         => $t('LANG_GALLERYPLUS_SHARE_DELETE', 'Удалить'),
            'resetDevices'   => $t('LANG_GALLERYPLUS_SHARE_RESET_DEVICES', 'Сбросить устройства'),
            'statusActive'   => $t('LANG_GALLERYPLUS_SHARE_STATUS_ACTIVE', 'Активна'),
            'statusRevoked'  => $t('LANG_GALLERYPLUS_SHARE_STATUS_REVOKED', 'Отозвана'),
            'statusExpired'  => $t('LANG_GALLERYPLUS_SHARE_STATUS_EXPIRED', 'Истекла'),
            'statusSingle'   => $t('LANG_GALLERYPLUS_SHARE_STATUS_SINGLE', 'Одноразовая'),
            'statusHidePreview' => $t('LANG_GALLERYPLUS_SHARE_STATUS_HIDE_PREVIEW', ''),
            'empty'          => $t('LANG_GALLERYPLUS_SHARE_EMPTY', 'Ссылок пока нет'),
            'opens'          => $t('LANG_GALLERYPLUS_SHARE_OPENS', 'переходов'),
            'devicesWord'    => $t('LANG_GALLERYPLUS_SHARE_DEVICES_WORD', 'устройств'),
            'until'          => $t('LANG_GALLERYPLUS_SHARE_UNTIL', 'до'),
            'loading'        => $t('LANG_GALLERYPLUS_SHARE_LOADING', 'Загрузка...'),
            'confirmDelete'  => $t('LANG_GALLERYPLUS_SHARE_CONFIRM_DELETE', 'Удалить ссылку?'),
            'confirmRevoke'  => $t('LANG_GALLERYPLUS_SHARE_CONFIRM_REVOKE', 'Отозвать ссылку?'),
            'close'          => $t('LANG_GALLERYPLUS_SHARE_CLOSE', 'Закрыть'),
            'error'          => $t('LANG_GALLERYPLUS_SHARE_ERR_CREATE', 'Ошибка'),
        ];
    }
}
