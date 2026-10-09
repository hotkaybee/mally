<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Storefront SPA)
|--------------------------------------------------------------------------
| Serves the React Storefront for all storefront paths,
| while preserving /admin for Filament 4 panel.
*/

Route::get('/products/{slug?}', function () {
    return view('welcome');
})->where('slug', '.*');

Route::get('/catalog', function () {
    return view('welcome');
});

Route::get('/cart', function () {
    return view('welcome');
});

Route::get('/checkout/{any?}', function () {
    return view('welcome');
})->where('any', '.*');

Route::get('/track', function () {
    return view('welcome');
});

Route::get('/profile', function () {
    return view('welcome');
});

Route::get('/favorites', function () {
    return view('welcome');
});

Route::get('/returns', function () {
    return view('welcome');
});

Route::get('/{any?}', function () {
    return view('welcome');
})->where('any', '^(?!admin|css|js|fonts|storage|livewire|api).*$');
