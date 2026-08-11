<?php

function routes_galleryplus() {
    return [
        [
            'pattern' => '/^galleryplus\/comments_html$/i',
            'action'  => 'comments_html',
        ],
        [
            'pattern' => '/^galleryplus\/album\/edit\/([a-z0-9\-\/]+).html$/i',
            'action'  => 'album_edit',
            1         => 'slug'
        ],
        [
            'pattern' => '/^galleryplus\/album\/([a-z0-9\-\/]+).html$/i',
            'action'  => 'album',
            1         => 'slug'
        ],
        [
            'pattern' => '/^galleryplus\/explore\/([a-z]+)$/i',
            'action'  => 'index',
            1         => 'explore'
        ],
        [
            'pattern' => '/^galleryplus\/category\/([a-z0-9\-\/]+).html$/i',
            'action'  => 'index',
            1         => 'category'
        ],
        [
            'pattern' => '/^galleryplus\/tag\/([^\/]+)$/i',
            'action'  => 'index',
            1         => 'tag'
        ],
        [
            'pattern' => '/^galleryplus\/edit\/(\d+)$/i',
            'action'  => 'edit',
            1         => 'photo_id'
        ],
        [
            'pattern' => '/^galleryplus\/serve\/(\d+)\/(small|big|nocrop|original)$/i',
            'action'  => 'serve',
            1         => 'photo_id',
            2         => 'preset'
        ],
        [
            'pattern' => '/^galleryplus\/([a-z0-9\-\/]+).html$/i',
            'action'  => 'view',
            1         => 'slug'
        ],
    ];
}
