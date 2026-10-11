<?php

class actionGalleryplusSuggest extends cmsAction {

    public function run() {

        $field = (string)$this->request->get('field', 'title');
        if (!in_array($field, ['title', 'author'], true)) { $field = 'title'; }

        $term = (string)$this->request->get('term', '');

        $user = $this->cms_user;

        $show_adult_to_guests    = $this->options['show_adult_to_guests'] ?? 0;
        $include_adult_for_guests = !$user->id && $show_adult_to_guests;

        $this->model->adult_karma = (int)($this->options['adult_karma'] ?? 0);
        $this->model->user_karma  = $user->karma ?? 0;

        $values = $this->model->suggestAlbumValues($field, $term, $user->id, $include_adult_for_guests, 10);

        $out = [];
        foreach ($values as $v) {
            $out[] = ['value' => $v, 'label' => $v];
        }

        $this->cms_template->renderJSON($out);
    }

}
