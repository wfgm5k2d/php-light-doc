<?php

use Illuminate\Support\Facades\Route;
use Wfgm5k2d\PhpLightDoc\Http\Controllers\PhpLightDocController;

Route::get('/doc', [PhpLightDocController::class, 'index']);
