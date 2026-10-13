<?php

return [

    'tmdb' => [
        'token' => env('TMDB_API_TOKEN'),
        'url' => env('TMDB_API_URL', 'https://api.themoviedb.org/3/'),
        'timeout' => (int) env('TMDB_TIMEOUT', 8),
        'connect_timeout' => (int) env('TMDB_CONNECT_TIMEOUT', 3),
        'retries' => (int) env('TMDB_RETRIES', 2),
    ],

];
