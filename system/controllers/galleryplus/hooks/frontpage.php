<?php

class onGalleryplusFrontpage extends cmsAction {

    public function run($action) {

        if ($action !== 'index') {
            return cmsCore::error404();
        }

        return $this->runAction('index');

    }

}