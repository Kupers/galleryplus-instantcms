<?php

class actionGalleryplusLike extends cmsAction {

    public function run() {
        if (!$this->request->isAjax()) {
            return cmsCore::error404();
        }
        if (!$this->cms_user->id) {
            return $this->cms_template->renderJSON([
                'error' => true,
                'message' => string_lang('LANG_LOGIN_REQUIRED', 'Login required')
            ]);
        }

        $target_id   = $this->request->get('target_id', 0);
        $target_type = $this->request->get('target_type', '');

        if (!$target_id || !in_array($target_type, ['photo', 'album'])) {
            return $this->cms_template->renderJSON(['error' => true, 'message' => 'Invalid params']);
        }

        // Prevent self-liking
        if ($target_type === 'photo') {
            $photo = $this->model->getItemById('galleryplus_photos', $target_id);
            if ($photo && (int)$photo['user_id'] === (int)$this->cms_user->id) {
                return $this->cms_template->renderJSON([
                    'error' => true,
                    'message' => 'Cannot like your own photo'
                ]);
            }
        }

        $result = $this->model->addLike($target_id, $target_type, $this->cms_user->id);

        return $this->cms_template->renderJSON($result);
    }

}
