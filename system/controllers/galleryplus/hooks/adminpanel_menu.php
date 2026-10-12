<?php

class onGalleryplusAdminpanelMenu extends cmsAction {

    public function run($menu) {

        // Проверка обновлений Gallery+ выполняется при открытии любой страницы
        // админ-панели (не чаще одного раза в UPDATE_CHECK_INTERVAL).
        // Если вышла новая версия — администраторы получают системное
        // уведомление («колокольчик» в шапке админки) на всех страницах админки,
        // в том числе на admin/controllers.

        try {

            $update = $this->model->checkForUpdates();

            if ($update) {
                $this->model->notifyAdminsAboutUpdate($update['latest_version'], $update['release_url']);
            }

        } catch (\Throwable $e) {}

        return $menu;
    }

}