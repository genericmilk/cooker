<?php

Route::get('/e/announce', function(){
    return config('element.api');
    //return 'Oh shit it element';
});