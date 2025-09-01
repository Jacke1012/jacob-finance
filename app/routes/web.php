<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome'); // expects resources/views/welcome.blade.php
});
