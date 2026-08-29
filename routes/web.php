<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('app'));

// SPA shell — semua path non-API yang tidak match file statis disajikan ke app.
// Negasi lookahead menjaga endpoint /api, /up (health), dan asset dari catch-all.
Route::get('/{any}', fn () => view('app'))
    ->where('any', '^(?!api|up|storage|build|vendor).*');
