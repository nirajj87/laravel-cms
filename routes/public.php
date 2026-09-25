<?php

use App\Http\Controllers\Public\CartController;
use App\Http\Controllers\Public\CheckoutController;
use App\Http\Controllers\Public\CustomerAuthController;
use App\Http\Controllers\Public\CustomerDashboardController;
use App\Http\Controllers\Public\PaymentController;
use App\Http\Controllers\Tenant\PublicGrowthController;
use App\Http\Controllers\Tenant\SiteController;
use Illuminate\Support\Facades\Route;

Route::prefix('site/{siteTenant}')->name('site.')->group(function () {
    Route::get('/', [SiteController::class, 'home'])->name('home');
    Route::get('/search', [SiteController::class, 'search'])->name('search');
    Route::get('/contact', [PublicGrowthController::class, 'contact'])->name('contact');
    Route::post('/contact', [PublicGrowthController::class, 'storeContact'])->middleware('throttle:public-form')->name('contact.store');
    Route::post('/feedback', [PublicGrowthController::class, 'storeFeedback'])->middleware('throttle:public-form')->name('feedback.store');
    Route::get('/sitemap.xml', [PublicGrowthController::class, 'sitemap'])->name('sitemap');
    Route::get('/robots.txt', [PublicGrowthController::class, 'robots'])->name('robots');
    Route::get('/category/{topic}', [SiteController::class, 'category'])->name('categories.show');
    Route::get('/content/{entry}', [SiteController::class, 'content'])->name('content.show');
    Route::get('/page/{page}', [SiteController::class, 'page'])->name('pages.show');
    Route::post('/newsletter', [SiteController::class, 'newsletter'])->name('newsletter.store');
    Route::get('/topics/{topic}', [SiteController::class, 'topic'])->name('topics.show');

    Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
    Route::post('/cart', [CartController::class, 'add'])->middleware('throttle:60,1')->name('cart.add');
    Route::get('/cart/add/{post}', [CartController::class, 'addGet'])->middleware('throttle:60,1')->name('cart.add.get');
    Route::patch('/cart', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart', [CartController::class, 'remove'])->name('cart.remove');

    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:30,1')->name('checkout.store');

    Route::get('/payment/{order}', [PaymentController::class, 'show'])->middleware('auth:customer')->name('payment.show');
    Route::post('/payment/{order}', [PaymentController::class, 'confirm'])->middleware('auth:customer')->name('payment.confirm');

    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [CustomerAuthController::class, 'login'])->middleware('throttle:20,1')->name('login.store');
        Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [CustomerAuthController::class, 'register'])->middleware('throttle:20,1')->name('register.store');
        Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('logout');

        Route::middleware('auth:customer')->group(function () {
            Route::get('/', [CustomerDashboardController::class, 'dashboard'])->name('dashboard');
            Route::get('/orders', [CustomerDashboardController::class, 'orders'])->name('orders');
            Route::get('/orders/{order}', [CustomerDashboardController::class, 'order'])->name('orders.show');
        });
    });

    Route::get('/{typeSlug}', [SiteController::class, 'type'])->name('types.show');
    Route::get('/{typeSlug}/{entry}', [SiteController::class, 'entry'])->name('entries.show');
});
