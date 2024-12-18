<?php

use Illuminate\Support\Facades\Route;

Route::prefix('__cooker')->group(function () {
    Route::get('/', function () {
        return 'Hello from the Cooker package!';
    });
    Route::get('view', function () {
        return view('cole::howdy');
    });
    
});