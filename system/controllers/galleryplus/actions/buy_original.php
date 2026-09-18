<?php

class actionGalleryplusBuyOriginal extends cmsAction {

    public function run($photo_id = 0) {

        if (!$photo_id) {
            $photo_id = $this->request->get('photo_id', 0);
        }

        if (!$this->cms_user->is_logged) {
            return $this->redirectToLogin(href_to('galleryplus', 'buy_original', [(int)$photo_id]));
        }

        $photo_id = (int)$photo_id;
        if (!$photo_id) { return cmsCore::error404(); }

        $photo = $this->model->getItemById('galleryplus_photos', $photo_id);
        if (!$photo) { return cmsCore::error404(); }

        $album = $this->model->getAlbum($photo['album_id']);
        if (!$album) { return cmsCore::error404(); }

        $user = $this->cms_user;
        $show_adult_for_guests = (bool)($this->options['show_adult_to_guests'] ?? false);
        if (!$this->model->canAccessAlbum($album, $user->id, $user->karma ?? 0, (int)($this->options['adult_karma'] ?? 0), $show_adult_for_guests, $user->rating ?? 0, (int)($this->options['adult_rating'] ?? 0))) {
            return cmsCore::error404();
        }

        if (!(int)$photo['is_approved']) {
            $is_owner = $user->id && (int)$photo['user_id'] === (int)$user->id;
            if (!$is_owner && !$user->is_admin) {
                return cmsCore::error404();
            }
        }

        $photo_url = href_to('galleryplus', $photo['slug'] ?: ('photo-' . $photo_id)) . '.html';

        // Владельцу и админу доступ не нужен
        if ($user->is_admin || $photo['user_id'] == $user->id) {
            return $this->redirect($photo_url);
        }

        // Уже оплачено
        if ($this->model->isOriginalAccessGranted($user->id, $photo_id)) {
            return $this->redirect($photo_url);
        }

        $billing = $this->billing();
        if (!$billing) { return $this->redirect($photo_url); }

        $price = $this->billingPrice('download_original');
        if ($price <= 0) { return $this->redirect($photo_url); }

        $billing_model = $billing->model;

        $billing_model->startTransaction();

        $balance = $billing_model->forUpdate()->getUserBalance($user->id);

        if ($price > $balance) {

            cmsUser::sessionSet('billing_ticket', [
                'title'       => sprintf(LANG_GALLERYPLUS_BILLING_BUY_ORIGINAL_TITLE, $photo['title'] ?: $photo_id),
                'amount'      => $price,
                'diff_amount' => round($price - $balance, 2),
                'back_url'    => href_to('galleryplus', 'buy_original', [$photo_id])
            ]);

            return $this->redirectTo('billing', 'deposit');
        }

        if (!$this->request->has('submit')) {

            return $this->cms_template->render('buy_original', [
                'b_spellcount' => $billing->options['currency'] ?? '',
                'price_spell'  => $this->billingSpellPrice($price, $billing->options['currency'] ?? ''),
                'balance_spell'=> $this->billingSpellPrice($balance, $billing->options['currency'] ?? ''),
                'photo'        => $photo,
                'photo_url'    => $photo_url,
                'balance'      => $balance,
                'price'        => $price
            ]);
        }

        if (!cmsForm::validateCSRFToken($this->request->get('csrf_token', ''))) {
            cmsUser::addSessionMessage(LANG_FORM_ERRORS, 'error');
            return $this->redirectTo('galleryplus', 'buy_original', [$photo_id]);
        }

        $success = $this->billingCharge('download_original', $user->id);

        if ($success) {

            $author_amount = $this->billingAuthorPayout($price);

            if ($author_amount > 0) {

                $success = $billing_model->incrementUserBalance(
                    (int)$photo['user_id'],
                    $author_amount,
                    [
                        'text' => sprintf(defined('LANG_GALLERYPLUS_BILLING_AUTHOR_PAYOUT') ? LANG_GALLERYPLUS_BILLING_AUTHOR_PAYOUT : 'Sale: %s', $photo['title'] ?: $photo_id),
                        'url'  => $photo_url
                    ]
                );

            }

        }

        $success = $success && $this->model->setOriginalAccess($user->id, $photo_id);

        $billing_model->endTransaction($success);

        if (!$success) {
            cmsUser::addSessionMessage(LANG_GALLERYPLUS_BILLING_ERROR_TRY, 'error');
            return $this->redirectTo('galleryplus', 'buy_original', [$photo_id]);
        }

        cmsUser::addSessionMessage(LANG_GALLERYPLUS_BILLING_BUY_SUCCESS, 'success');

        return $this->redirect($photo_url);
    }

}