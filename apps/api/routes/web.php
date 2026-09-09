<?php

declare(strict_types=1);

use App\Integration\Geo\Http\TileProxyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Tile Proxy (§8.4)
Route::get('/tiles/{theme}/{z}/{x}/{y}.png', [TileProxyController::class, 'getTile'])
    ->where(['z' => '[0-9]+', 'x' => '[0-9]+', 'y' => '[0-9]+']);

Route::get('/v1/tiles/{theme}/{z}/{x}/{y}.png', [TileProxyController::class, 'getTile'])
    ->where(['z' => '[0-9]+', 'x' => '[0-9]+', 'y' => '[0-9]+']);
