<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json(['service' => 'kakeibo-app', 'api' => '/api/v1']));
