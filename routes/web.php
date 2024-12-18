<?php

use Illuminate\Support\Facades\Route;
use Genericmilk\Cooker\Engine;

Route::prefix('__cooker')->group(function () {
    Route::get('{file}', [Engine::class, 'render']);
});