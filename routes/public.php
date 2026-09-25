<?php

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
    Route::get('/{typeSlug}', [SiteController::class, 'type'])->name('types.show');
    Route::get('/{typeSlug}/{entry}', [SiteController::class, 'entry'])->name('entries.show');
});
