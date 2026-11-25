<?php

use App\Http\Controllers\AffiliateLinkRedirectController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductSearchController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::get('/cari', ProductSearchController::class)->name('products.search');

Route::get('/product/{product:code}', [ProductController::class, 'show'])
    ->whereNumber('product')
    ->name('products.show');

Route::get('/product/{product:code}/go/{affiliateLink}', AffiliateLinkRedirectController::class)
    ->whereNumber(['product', 'affiliateLink'])
    ->scopeBindings()
    ->middleware('throttle:affiliate-redirect')
    ->name('affiliate-links.redirect');
