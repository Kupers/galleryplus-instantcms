<?php

class actionGalleryplusFavorite extends cmsAction {

    public function run() {
        if (!$this->request->isAjax()) {
            return cmsCore::error404();
        }
        if (!$this->cms_user->id) {
            return $this->cms_template->renderJSON([
                'error'   => true,
                'message' => string_lang('LANG_LOGIN_REQUIRED', 'Login required')
            ]);
        }

        $photo_id = (int)$this->request->get('photo_id', 0);

        if (!$photo_id) {
            return $this->cms_template->renderJSON(['error' => true, 'message' => 'Invalid params']);
        }

        $result = $this->model->toggleFavorite($photo_id, $this->cms_user->id);

        return $this->cms_template->renderJSON($result);
    }

}
