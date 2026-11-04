<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Catalog
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/category/{path}', [ProductController::class, 'category'])->where('path', '.+')->name('categories.show');
Route::get('/product/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::post('/product/{slug}/reviews', [ProductController::class, 'storeReview'])->middleware('auth')->name('products.reviews.store');
Route::post('/favorites/toggle', [ProductController::class, 'toggleFavorite'])->middleware('auth')->name('favorites.toggle');

// Search
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/search/autocomplete', [ProductController::class, 'suggestions'])->name('search.autocomplete');

// Cart
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');
Route::post('/cart/promo', [CartController::class, 'applyPromo'])->name('cart.promo.apply');
Route::delete('/cart/promo', [CartController::class, 'removePromo'])->name('cart.promo.remove');

// Product comparison
Route::get('/compare', [CompareController::class, 'index'])->name('compare.index');
Route::post('/compare/add/{product}', [CompareController::class, 'add'])->name('compare.add');
Route::delete('/compare/remove/{id}', [CompareController::class, 'remove'])->name('compare.remove');
Route::delete('/compare/clear', [CompareController::class, 'clear'])->name('compare.clear');

// Checkout
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/place-order', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/order-success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');

// Customer account
Route::middleware('auth')->prefix('profile')->group(function () {
    Route::get('/', [AccountController::class, 'profile'])->name('account.profile');
    Route::get('/orders', [AccountController::class, 'orders'])->name('account.orders');
    Route::get('/favorites', [AccountController::class, 'favorites'])->name('account.favorites');
    Route::redirect('/edit', '/settings/profile');
});

// Admin panel
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(base_path('routes/admin.php'));

require __DIR__.'/settings.php';

// Static content pages must stay last: they match any single-segment URL.
Route::get('/{slug}', [PageController::class, 'show'])->name('pages.show');
