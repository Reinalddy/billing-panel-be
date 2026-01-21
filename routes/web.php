<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    Cache::put('test_key', 'OK', 60);
    dd(Cache::get('test_key'));
    return view('welcome');
});
