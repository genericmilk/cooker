<?php

use Illuminate\Support\Facades\Route;
use Genericmilk\Cooker\Engine;

Route::prefix('__cooker')->group(function () {
    Route::get('{file}', [Engine::class, 'render']);
    Route::prefix('package-imports')->group(function () {
        Route::get('{baseFile}/{file}', [Engine::class, 'import']);
    });
    Route::prefix('local-imports')->group(function () {
        Route::get('{baseFile}/{file}', [Engine::class, 'localImport']);
    });

    
});