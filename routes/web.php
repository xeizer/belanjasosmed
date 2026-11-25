<?php

use App\Http\Controllers\AffiliateLinkRedirectController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductSearchController;
use App\Models\Product;
use App\Models\User;
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

Route::livewire('/admin/login', 'pages::admin.login')
    ->middleware('guest')
    ->name('login');

Route::post('/admin/logout', LogoutController::class)
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/products')->name('dashboard');

    Route::livewire('/products', 'pages::admin.products.index')
        ->middleware('can:viewAny,'.Product::class)
        ->name('products.index');

    Route::livewire('/products/create', 'pages::admin.products.form')
        ->middleware('can:create,'.Product::class)
        ->name('products.create');

    // Authorization for editing happens inside the component (mount and save).
    Route::livewire('/products/{product}/edit', 'pages::admin.products.form')
        ->whereNumber('product')
        ->name('products.edit');

    Route::livewire('/users', 'pages::admin.users.index')
        ->middleware('can:viewAny,'.User::class)
        ->name('users.index');

    Route::livewire('/users/create', 'pages::admin.users.form')
        ->middleware('can:create,'.User::class)
        ->name('users.create');

    // Authorization for editing happens inside the component (mount and save).
    Route::livewire('/users/{user}/edit', 'pages::admin.users.form')
        ->whereNumber('user')
        ->middleware('can:viewAny,'.User::class)
        ->name('users.edit');

    Route::livewire('/password', 'pages::admin.password')->name('password.edit');
});
