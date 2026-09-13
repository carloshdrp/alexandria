<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return json_encode('Hello World!');
});
