<?php

use Illuminate\Support\Facades\Route;

Route::prefix('cooker-resources')->group(function () {
    Route::get('/', function () {
        return 'Hello from the Cooker package!';
    });
    Route::get('view', function () {
        return view('cole::howdy');
    });
    
});