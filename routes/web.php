<?php

use Illuminate\Support\Facades\Route;

Route::prefix('cooker')->group(function () {
    Route::get('/', function () {
        return 'Hello from the Cole package!';
    });
    Route::get('view', function () {
        return view('cole::howdy');
    });
    
});