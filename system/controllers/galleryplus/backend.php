<?php

class backendGalleryplus extends cmsBackend {

    public $useDefaultOptionsAction = true;
    public $useSeoOptions = true;
    public $useDefaultModerationAction = true;

    public function __construct($request) {
        parent::__construct($request);
        $this->backend_menu[] = [
            'title' => LANG_GALLERYPLUS_CATEGORIES,
            'url'   => href_to($this->root_url, 'categories'),
            'options' => ['icon' => 'folder-open']
        ];
        $this->backend_menu[] = [
            'title' => LANG_GALLERYPLUS_PENDING,
            'url'   => href_to($this->root_url, 'pending'),
            'options' => ['icon' => 'hourglass-half']
        ];
        $this->backend_menu[] = [
            'title' => LANG_GALLERYPLUS_CLEANUP,
            'url'   => href_to($this->root_url, 'cleanup'),
            'options' => ['icon' => 'trash-alt', 'class' => 'galleryplus-cleanup-tab']
        ];
        $this->backend_menu[] = [
            'title' => LANG_GALLERYPLUS_LOGS,
            'url'   => href_to($this->root_url, 'logs'),
            'options' => ['icon' => 'clipboard-list']
        ];

        $this->maybeShowUpdateNotice();
    }

    private function maybeShowUpdateNotice() {

        try {

            $update = $this->model->checkForUpdates();

            if (!$update) {
                return;
            }

            $this->backend_menu[] = [
                'title' => sprintf(LANG_GALLERYPLUS_UPDATE_AVAILABLE, $update['latest_version']),
                'url'   => $update['release_url'] ?: '#',
                'options' => [
                    'icon'   => 'arrow-alt-circle-up',
                    'target' => '_blank',
                    'class'  => 'ml-auto text-success font-weight-bold'
                ]
            ];

            $this->model->notifyAdminsAboutUpdate($update['latest_version'], $update['release_url']);

        } catch (\Throwable $e) {}
    }

    public function actionIndex() {
        $this->redirectToAction('options');
    }

    public function actionModerators() {

        if (empty($this->useDefaultModerationAction)) {
            return cmsCore::error404();
        }

        $moderators = $this->model_moderation->getContentTypeModerators($this->name);

        $template_params = [
            'title'         => $this->title,
            'not_use_trash' => !$this->useModerationTrash,
            'moderators'    => $moderators
        ];

        // если задан шаблон в контроллере
        if ($this->cms_template->getTemplateFileName('controllers/' . $this->name . '/backend/moderators', true)) {

            return $this->cms_template->render('backend/moderators', $template_params);

        } else {

            $default_admin_tpl = $this->cms_template->getTemplateFileName('controllers/admin/controllers_moderators');

            return $this->cms_template->processRender($default_admin_tpl, $template_params);
        }
    }

    public function actionCategories($page = 1) {

        $perpage = 20;
        $total = $this->model->getCategoriesCount();
        $categories = $this->model->getCategories($page, $perpage);

        return $this->cms_template->render('backend/categories', [
            'categories' => $categories,
            'total'      => $total,
            'page'       => $page,
            'perpage'    => $perpage,
        ]);
    }

    public function actionCategoryAdd() {

        $form = $this->getForm('category');

        if ($this->request->has('submit')) {

            $csrf_token = $this->request->get('csrf_token', '');
            if (!cmsForm::validateCSRFToken($csrf_token)) {
                cmsUser::addSessionMessage(LANG_FORM_ERRORS, 'error');
                return $this->redirectToAction('categories');
            }

            $form_data = $form->parse($this->request);
            $errors = $form->validate($this, $form_data, false);

            if ($errors) {
                cmsUser::addSessionMessage(LANG_FORM_ERRORS, 'error');
                return $this->cms_template->render('backend/category_form', [
                    'form' => $form,
                    'data' => $form_data,
                    'errors' => $errors,
                ]);
            }

            $data = [];
            $data['title']       = $form_data['title'];
            $data['slug']        = lang_slug($form_data['slug'] ?: $form_data['title']);
            $data['description'] = $form_data['description'] ?? '';
            $data['ordering']    = (int)($form_data['ordering'] ?? 0);
            $data['is_hidden']   = (int)($form_data['is_hidden'] ?? 0);

            $existing = $this->model->getGalleryCategoryBySlug($data['slug']);
            if ($existing) {
                cmsUser::addSessionMessage(LANG_GALLERYPLUS_CATEGORY_SLUG_EXISTS, 'error');
                return $this->cms_template->render('backend/category_form', [
                    'form' => $form,
                    'data' => $form_data,
                    'errors' => ['slug' => LANG_GALLERYPLUS_CATEGORY_SLUG_EXISTS],
                ]);
            }

            $id = $this->model->addGalleryCategory($data);
            if ($id) {
                cmsUser::addSessionMessage(LANG_SUCCESS_MSG, 'success');
                return $this->redirectToAction('categories');
            }

            cmsUser::addSessionMessage(LANG_FORM_ERRORS, 'error');
        }

        return $this->cms_template->render('backend/category_form', [
            'form' => $form,
            'data' => [],
            'errors' => false,
        ]);
    }

    public function actionCategoryEdit($id = 0) {

        if (!$id) { return cmsCore::error404(); }

        $category = $this->model->getCategoryById($id);
        if (!$category) { return cmsCore::error404(); }

        $form = $this->getForm('category', [$category]);

        if ($this->request->has('submit')) {

            $csrf_token = $this->request->get('csrf_token', '');
            if (!cmsForm::validateCSRFToken($csrf_token)) {
                cmsUser::addSessionMessage(LANG_FORM_ERRORS, 'error');
                return $this->redirectToAction('categories');
            }

            $form_data = $form->parse($this->request);
            $errors = $form->validate($this, $form_data, false);

            if ($errors) {
                cmsUser::addSessionMessage(LANG_FORM_ERRORS, 'error');
                return $this->cms_template->render('backend/category_form', [
                    'form'    => $form,
                    'data'    => $form_data,
                    'errors'  => $errors,
                    'editing' => true,
                ]);
            }

            $data = [];
            $data['title']       = $form_data['title'];
            $data['slug']        = lang_slug($form_data['slug'] ?: $form_data['title']);
            $data['description'] = $form_data['description'] ?? '';
            $data['ordering']    = (int)($form_data['ordering'] ?? 0);
            $data['is_hidden']   = (int)($form_data['is_hidden'] ?? 0);

            $existing = $this->model->getGalleryCategoryBySlug($data['slug']);
            if ($existing && $existing['id'] != $id) {
                cmsUser::addSessionMessage(LANG_GALLERYPLUS_CATEGORY_SLUG_EXISTS, 'error');
                return $this->cms_template->render('backend/category_form', [
                    'form'    => $form,
                    'data'    => $form_data,
                    'errors'  => ['slug' => LANG_GALLERYPLUS_CATEGORY_SLUG_EXISTS],
                    'editing' => true,
                ]);
            }

            $this->model->updateGalleryCategory($id, $data);
            cmsUser::addSessionMessage(LANG_SUCCESS_MSG, 'success');
            return $this->redirectToAction('categories');
        }

        return $this->cms_template->render('backend/category_form', [
            'form'    => $form,
            'data'    => $category,
            'errors'  => false,
            'editing' => true,
        ]);
    }

    public function actionCategoryDelete($id = 0) {

        if (!$id) { return cmsCore::error404(); }

        $category = $this->model->getCategoryById($id);
        if (!$category) { return cmsCore::error404(); }

        $this->model->deleteGalleryCategory($id);

        cmsUser::addSessionMessage(LANG_DELETE_SUCCESS, 'success');
        return $this->redirectToAction('categories');
    }

    public function actionPending($page = 1) {

        $perpage = 20;

        $total  = $this->model->getPendingCount();
        $photos = $this->model->getPendingPhotos($page, $perpage);

        return $this->cms_template->render('backend/pending', [
            'photos'  => $photos,
            'total'   => $total,
            'page'    => $page,
            'perpage' => $perpage,
        ]);
    }

    public function actionApprove() {

        if (!$this->request->isAjax()) { return cmsCore::error404(); }

        $id = $this->request->get('id', 0);
        if (!$id) { return cmsCore::error404(); }

        $this->model->approvePhoto($id, $this->cms_user->id);

        return $this->cms_template->renderJSON(['error' => false]);
    }

    public function actionDelete() {

        $id = $this->request->get('id', 0);
        if (!$id) { return cmsCore::error404(); }

        $photo = $this->model->db->getRow('galleryplus_photos', 'id = ' . $id, 'id, title, user_id, image');
        if (!$photo) { return cmsCore::error404(); }

        $photo['image'] = cmsModel::yamlToArray($photo['image']);
        if (!empty($photo['image'])) {
            foreach ($photo['image'] as $path) {
                $file = cmsConfig::get('upload_path') . $path;
                if (is_file($file)) { @unlink($file); }
            }
        }

        $this->model->deletePhoto($id);

        $this->model->addLog('delete', 'photo', $id, $photo['title'] ?? '', $photo['user_id'] ?? 0, $this->cms_user->id);

        if ($this->request->isAjax()) {
            return $this->cms_template->renderJSON(['error' => false]);
        }

        $this->redirectBack();
    }

    public function actionLogs($page = 1) {

        $perpage = 20;

        $total = $this->model->getLogsCount();
        $logs  = $this->model->getLogs($page, $perpage);

        return $this->cms_template->render('backend/logs', [
            'logs'            => $logs,
            'total'           => $total,
            'page'            => $page,
            'perpage'         => $perpage,
            'logging_enabled' => $this->model->isLoggingEnabled(),
        ]);
    }

    public function actionLogDeleteAll() {

        if (!$this->request->has('submit')) {
            return $this->redirectToAction('logs');
        }

        $csrf_token = $this->request->get('csrf_token', '');
        if (!cmsForm::validateCSRFToken($csrf_token)) {
            cmsUser::addSessionMessage(LANG_FORM_ERRORS, 'error');
            return $this->redirectToAction('logs');
        }

        $this->model->clearLogs();

        cmsUser::addSessionMessage(LANG_GALLERYPLUS_LOG_CLEARED, 'success');
        return $this->redirectToAction('logs');
    }

    public function actionLogDelete($id = 0) {

        if (!$id) { return cmsCore::error404(); }

        $this->model->deleteLog($id);

        cmsUser::addSessionMessage(LANG_DELETE_SUCCESS, 'success');
        return $this->redirectToAction('logs');
    }

    public function actionCleanup() {

        $db = $this->model->db;

        $stats = [
            'photos' => $db->getRowsCount('galleryplus_photos'),
            'albums' => $db->getRowsCount('galleryplus_albums'),
            'likes'  => $db->getRowsCount('galleryplus_likes'),
        ];

        $total_size = 0;
        if ($stats['photos']) {
            $rows = $db->getRows('galleryplus_photos', '1', 'image');
            foreach ($rows as $row) {
                $img = cmsModel::yamlToArray($row['image']);
                if (!empty($img)) {
                    foreach ($img as $path) {
                        $file = cmsConfig::get('upload_path') . $path;
                        if (is_file($file)) { $total_size += filesize($file); }
                    }
                }
            }
        }

        if ($this->request->has('submit')) {

            $csrf_token = $this->request->get('csrf_token', '');
            if (!cmsForm::validateCSRFToken($csrf_token)) {
                cmsUser::addSessionMessage(LANG_FORM_ERRORS, 'error');
                return $this->redirectToAction('cleanup');
            }

            $delete_photos = $this->request->get('delete_photos', 0);
            $delete_albums = $this->request->get('delete_albums', 0);
            $delete_likes  = $this->request->get('delete_likes', 0);

            if ($delete_photos) {
                $rows = $db->getRows('galleryplus_photos', '1', 'id, image');
                if ($rows) {
                    foreach ($rows as $photo) {
                        $img = cmsModel::yamlToArray($photo['image']);
                        if (!empty($img)) {
                            foreach ($img as $path) {
                                $file = cmsConfig::get('upload_path') . $path;
                                if (is_file($file)) { @unlink($file); }
                            }
                        }
                    }
                }
                $db->truncateTable('galleryplus_photos');
            }

            if ($delete_albums) {
                $db->truncateTable('galleryplus_albums');
            }

            if ($delete_likes) {
                $db->truncateTable('galleryplus_likes');
            }

            cmsUser::addSessionMessage(LANG_GALLERYPLUS_CLEANUP_DONE, 'success');
            return $this->redirectToAction('cleanup');
        }

        return $this->cms_template->render('backend/cleanup', [
            'stats'      => $stats,
            'total_size' => $total_size,
        ]);
    }

    public function getContentTypeForModeration($subject) {
        $types = [
            'photo' => [
                'name'  => 'photo',
                'title' => LANG_GALLERYPLUS_PHOTO,
            ],
            'album' => [
                'name'  => 'album',
                'title' => LANG_GALLERYPLUS_ALBUM,
            ],
        ];
        return $types[$subject] ?? null;
    }

}
