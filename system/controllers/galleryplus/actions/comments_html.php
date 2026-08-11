<?php

class actionGalleryplusCommentsHtml extends cmsAction {

    public function run() {

        $target_type = $this->request->get('target_type', '');
        $target_id   = (int)$this->request->get('target_id', 0);

        if (!$target_type || !$target_id) {
            $this->cms_template->renderJSON(['html' => '']);
            return;
        }

        if ($target_type === 'photo' && empty($this->options['is_comments_photo'])) { $this->cms_template->renderJSON(['html' => '']); return; }
        if ($target_type === 'album' && empty($this->options['is_comments_album'])) { $this->cms_template->renderJSON(['html' => '']); return; }

        if ($target_type === 'photo') {
            $photo = $this->model->getItemById('galleryplus_photos', $target_id);
            if (!$photo) { $this->cms_template->renderJSON(['html' => '']); return; }
            $target_user_id = $photo['user_id'];
        } elseif ($target_type === 'album') {
            $album = $this->model->getItemById('galleryplus_albums', $target_id);
            if (!$album) { $this->cms_template->renderJSON(['html' => '']); return; }
            $target_user_id = $album['user_id'];
        } else {
            $this->cms_template->renderJSON(['html' => '']);
            return;
        }

        $cc = cmsCore::getController('comments');
        $cc->target_controller = 'galleryplus';
        $cc->target_subject    = $target_type;
        $cc->target_id         = $target_id;
        $cc->target_user_id    = $target_user_id;

        $native = $cc->getNativeComments();
        $comments_html = $native['html'] ?? '';

        $html = '<div id="comments_widget">';
        $html .= '<div id="tab-icms" class="tab" style="display:block">';
        $html .= $comments_html;
        $html .= '</div>';
        $html .= '</div>';

        // Get actual comments count
        $comments_count = $this->model->getItemById('galleryplus_photos', $target_id)['comments'] ?? 0;

        $this->cms_template->renderJSON([
            'html'    => $html,
            'urls'    => [
                'get'     => href_to('comments', 'get'),
                'approve' => href_to('comments', 'approve'),
                'delete'  => href_to('comments', 'delete'),
                'refresh' => href_to('comments', 'refresh'),
                'track'   => href_to('comments', 'track'),
                'rate'    => href_to('comments', 'rate'),
            ],
            'target'  => [
                'tc' => 'galleryplus',
                'ts' => $target_type,
                'ti' => $target_id,
                'tud' => $target_user_id,
                'timestamp' => time(),
            ],
            'count'   => (int)$comments_count,
        ]);

    }

}
