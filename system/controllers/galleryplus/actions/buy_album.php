<?php

class actionGalleryplusBuyAlbum extends cmsAction {

    public function run($album_id = 0) {

        if (!$album_id) {
            $album_id = $this->request->get('album_id', 0);
        }

        if (!$this->cms_user->is_logged) {
            return $this->redirectToLogin(href_to('galleryplus', 'buy_album', [(int)$album_id]));
        }

        $album_id = (int)$album_id;
        if (!$album_id) { return cmsCore::error404(); }

        $album = $this->model->getAlbum($album_id);
        if (!$album) { return cmsCore::error404(); }

        $album_url = href_to('galleryplus', 'album', [$album['slug']]) . '.html';

        // Владельцу и админу доступ не нужен
        if ($this->cms_user->is_admin || $album['user_id'] == $this->cms_user->id) {
            return $this->redirect($album_url);
        }

        // Уже оплачено
        if ($this->model->isAlbumAccessGranted($this->cms_user->id, $album_id)) {
            return $this->redirect($album_url);
        }

        $billing = $this->billing();
        if (!$billing) { return $this->redirect($album_url); }

        $price = $this->billingPrice('view_album');
        if ($price <= 0) { return $this->redirect($album_url); }

        $billing_model = $billing->model;

        $billing_model->startTransaction();

        $balance = $billing_model->forUpdate()->getUserBalance($this->cms_user->id);

        if ($price > $balance) {

            cmsUser::sessionSet('billing_ticket', [
                'title'       => sprintf(LANG_GALLERYPLUS_BILLING_BUY_ALBUM_TITLE, $album['title']),
                'amount'      => $price,
                'diff_amount' => round($price - $balance, 2),
                'back_url'    => href_to('galleryplus', 'buy_album', [$album_id])
            ]);

            return $this->redirectTo('billing', 'deposit');
        }

        if (!$this->request->has('submit')) {

            return $this->cms_template->render('buy_album', [
                'b_spellcount' => $billing->options['currency'] ?? '',
                'price_spell'  => $this->billingSpellPrice($price, $billing->options['currency'] ?? ''),
                'balance_spell'=> $this->billingSpellPrice($balance, $billing->options['currency'] ?? ''),
                'album'        => $album,
                'album_url'    => $album_url,
                'balance'      => $balance,
                'price'        => $price
            ]);
        }

        if (!cmsForm::validateCSRFToken($this->request->get('csrf_token', ''))) {
            cmsUser::addSessionMessage(LANG_FORM_ERRORS, 'error');
            return $this->redirectTo('galleryplus', 'buy_album', [$album_id]);
        }

        $success = $this->billingCharge('view_album', $this->cms_user->id);

        if ($success) {

            $author_amount = $this->billingAuthorPayout($price);

            if ($author_amount > 0) {

                $success = $billing_model->incrementUserBalance(
                    (int)$album['user_id'],
                    $author_amount,
                    [
                        'text' => sprintf(defined('LANG_GALLERYPLUS_BILLING_AUTHOR_PAYOUT') ? LANG_GALLERYPLUS_BILLING_AUTHOR_PAYOUT : 'Sale: %s', $album['title']),
                        'url'  => $album_url
                    ]
                );

            }

        }

        $success = $success && $this->model->setAlbumAccess($this->cms_user->id, $album_id);

        $billing_model->endTransaction($success);

        if (!$success) {
            cmsUser::addSessionMessage(LANG_GALLERYPLUS_BILLING_ERROR_TRY, 'error');
            return $this->redirectTo('galleryplus', 'buy_album', [$album_id]);
        }

        cmsUser::addSessionMessage(LANG_GALLERYPLUS_BILLING_BUY_SUCCESS, 'success');

        return $this->redirect($album_url);
    }

}