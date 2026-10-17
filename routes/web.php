<?php

use App\Livewire\Storefront\CartPage;
use App\Livewire\Storefront\CatalogPage;
use App\Livewire\Storefront\CheckoutPage;
use App\Livewire\Storefront\ContactPage;
use App\Livewire\Storefront\FavoritesPage;
use App\Livewire\Storefront\HomePage;
use App\Livewire\Storefront\OrderReceiptPage;
use App\Livewire\Storefront\ProductDetailPage;
use App\Livewire\Storefront\ProfilePage;
use App\Livewire\Storefront\ReturnsPage;
use Illuminate\Support\Facades\Route;

Route::get('/', HomePage::class)->name('home');
Route::get('/catalog', CatalogPage::class)->name('catalog');
Route::get('/products/{slug}', ProductDetailPage::class)->name('product.detail');
Route::get('/cart', CartPage::class)->name('cart');
Route::get('/checkout', CheckoutPage::class)->name('checkout');
Route::get('/checkout/{orderNumber}/{orderHash?}', OrderReceiptPage::class)->name('checkout.receipt');
Route::get('/favorites', FavoritesPage::class)->name('favorites');
Route::get('/profile', ProfilePage::class)->name('profile');
Route::get('/returns', ReturnsPage::class)->name('returns');
Route::get('/contact', ContactPage::class)->name('contact');
