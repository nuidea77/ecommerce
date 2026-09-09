<?php

use Illuminate\Support\Facades\Route;

// Single-page application entry point. All non-API routes render the Vue app.
Route::view('/{any?}', 'app')->where('any', '^(?!api|storage|sanctum|up).*$');
