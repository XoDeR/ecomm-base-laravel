<?php

use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CurrencyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PriceBatchController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PromoCodeController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShopMethodController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin panel routes
|--------------------------------------------------------------------------
|
| Loaded from routes/web.php under the "admin" prefix with the auth and admin
| middleware. Route names are prefixed with "admin.".
|
*/

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::resource('categories', CategoryController::class)->except('show');
Route::resource('attributes', AttributeController::class)->except('show');
Route::resource('products', ProductController::class)->except('show');

Route::get('price-batches', [PriceBatchController::class, 'index'])->name('price-batches.index');
Route::get('price-batches/preview', [PriceBatchController::class, 'preview'])->name('price-batches.preview');
Route::post('price-batches', [PriceBatchController::class, 'store'])->name('price-batches.store');
Route::post('price-batches/{batch}/undo', [PriceBatchController::class, 'undo'])->name('price-batches.undo');

Route::resource('orders', OrderController::class)->only(['index', 'show', 'update', 'destroy']);
Route::post('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');

Route::resource('users', UserController::class)->only(['index', 'edit', 'update', 'destroy']);
Route::post('users/{user}/block', [UserController::class, 'block'])->name('users.block');
Route::post('users/{user}/bonus', [UserController::class, 'bonus'])->name('users.bonus');
Route::post('users/{user}/subscription', [UserController::class, 'subscription'])->name('users.subscription');
Route::post('users/{user}/activity-log/clear', [UserController::class, 'clearActivity'])->name('users.activity.clear');

Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
Route::post('reviews/bulk', [ReviewController::class, 'bulk'])->name('reviews.bulk');
Route::put('reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
Route::post('reviews/{review}/toggle', [ReviewController::class, 'toggle'])->name('reviews.toggle');
Route::post('reviews/{review}/reply', [ReviewController::class, 'reply'])->name('reviews.reply');
Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

Route::post('pages/upload', [PageController::class, 'upload'])->name('pages.upload');
Route::resource('pages', PageController::class)->except('show');

Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

Route::get('currencies', [CurrencyController::class, 'index'])->name('currencies.index');
Route::post('currencies', [CurrencyController::class, 'store'])->name('currencies.store');
Route::put('currencies/{currency}', [CurrencyController::class, 'update'])->name('currencies.update');
Route::post('currencies/{currency}/activate', [CurrencyController::class, 'activate'])->name('currencies.activate');
Route::delete('currencies/{currency}', [CurrencyController::class, 'destroy'])->name('currencies.destroy');

Route::get('methods', [ShopMethodController::class, 'index'])->name('methods.index');
Route::post('methods', [ShopMethodController::class, 'store'])->name('methods.store');
Route::put('methods/{method}', [ShopMethodController::class, 'update'])->name('methods.update');
Route::delete('methods/{method}', [ShopMethodController::class, 'destroy'])->name('methods.destroy');

Route::resource('promo-codes', PromoCodeController::class)->except('show');

Route::get('stocks', [StockController::class, 'index'])->name('stocks.index');
Route::post('stocks/adjust', [StockController::class, 'adjust'])->name('stocks.adjust');
