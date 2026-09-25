<?php

use App\Http\Controllers\Tenant\AnalyticsController;
use App\Http\Controllers\Tenant\BackupController as TenantBackupController;
use App\Http\Controllers\Tenant\CategoryController;
use App\Http\Controllers\Tenant\CommerceController;
use App\Http\Controllers\Tenant\ContentTypeController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\EmailController;
use App\Http\Controllers\Tenant\FeedbackController;
use App\Http\Controllers\Tenant\HeaderFooterController;
use App\Http\Controllers\Tenant\LayoutController;
use App\Http\Controllers\Tenant\MediaController;
use App\Http\Controllers\Tenant\MenuController;
use App\Http\Controllers\Tenant\ModulePlaceholderController;
use App\Http\Controllers\Tenant\PageController;
use App\Http\Controllers\Tenant\PermissionController;
use App\Http\Controllers\Tenant\PostController;
use App\Http\Controllers\Tenant\RoleController;
use App\Http\Controllers\Tenant\SeoController;
use App\Http\Controllers\Tenant\SettingController;
use App\Http\Controllers\Tenant\ThemeController;
use App\Http\Controllers\Tenant\UserController;
use App\Http\Controllers\Tenant\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'tenant'])->prefix('app')->name('tenant.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/exit', [WorkspaceController::class, 'exit'])->name('exit');

    Route::middleware('module:users')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->middleware('permission:users.create')->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
        Route::get('/users/{member}/edit', [UserController::class, 'edit'])->middleware('permission:users.edit')->name('users.edit');
        Route::put('/users/{member}', [UserController::class, 'update'])->middleware('permission:users.edit')->name('users.update');
        Route::delete('/users/{member}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');
    });

    Route::middleware('module:roles')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:roles.view')->name('roles.index');
        Route::get('/roles/create', [RoleController::class, 'create'])->middleware('permission:roles.create')->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:roles.create')->name('roles.store');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->middleware('permission:roles.edit')->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.edit')->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete')->name('roles.destroy');
    });

    Route::get('/permissions', [PermissionController::class, 'index'])
        ->middleware(['module:permissions', 'permission:permissions.view'])
        ->name('permissions.index');

    Route::get('/settings', [SettingController::class, 'edit'])
        ->middleware(['module:settings', 'permission:settings.view'])
        ->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])
        ->middleware(['module:settings', 'permission:settings.update'])
        ->name('settings.update');

    Route::middleware('module:categories')->group(function () {
        Route::get('/categories', [CategoryController::class, 'index'])->middleware('permission:categories.view')->name('categories.index');
        Route::get('/categories/create', [CategoryController::class, 'create'])->middleware('permission:categories.create')->name('categories.create');
        Route::post('/categories', [CategoryController::class, 'store'])->middleware('permission:categories.create')->name('categories.store');
        Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->middleware('permission:categories.edit')->name('categories.edit');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->middleware('permission:categories.edit')->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('permission:categories.delete')->name('categories.destroy');
    });

    Route::middleware('module:posts')->group(function () {
        Route::get('/posts/types', [ContentTypeController::class, 'index'])->middleware('permission:posts.types')->name('posts.types.index');
        Route::get('/posts/types/create', [ContentTypeController::class, 'create'])->middleware('permission:posts.types')->name('posts.types.create');
        Route::post('/posts/types', [ContentTypeController::class, 'store'])->middleware('permission:posts.types')->name('posts.types.store');
        Route::get('/posts/types/{contentType}/edit', [ContentTypeController::class, 'edit'])->middleware('permission:posts.types')->name('posts.types.edit');
        Route::put('/posts/types/{contentType}', [ContentTypeController::class, 'update'])->middleware('permission:posts.types')->name('posts.types.update');
        Route::delete('/posts/types/{contentType}', [ContentTypeController::class, 'destroy'])->middleware('permission:posts.types')->name('posts.types.destroy');
        Route::post('/posts/types/{contentType}/fields', [ContentTypeController::class, 'storeField'])->middleware('permission:posts.types')->name('posts.types.fields.store');
        Route::delete('/posts/types/{contentType}/fields/{field}', [ContentTypeController::class, 'destroyField'])->middleware('permission:posts.types')->whereNumber('field')->name('posts.types.fields.destroy');

        Route::get('/posts', [PostController::class, 'index'])->middleware('permission:posts.view')->name('posts.index');
        Route::get('/posts/create', [PostController::class, 'create'])->middleware('permission:posts.create')->name('posts.create');
        Route::post('/posts', [PostController::class, 'store'])->middleware('permission:posts.create')->name('posts.store');
        Route::get('/posts/{post}/preview', [PostController::class, 'preview'])->middleware('permission:posts.view')->name('posts.preview');
        Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->middleware('permission:posts.edit')->name('posts.edit');
        Route::put('/posts/{post}', [PostController::class, 'update'])->middleware('permission:posts.edit')->name('posts.update');
        Route::delete('/posts/{post}', [PostController::class, 'destroy'])->middleware('permission:posts.delete')->name('posts.destroy');
    });

    Route::middleware('module:form-builder')->prefix('form-builder')->name('form-builder.')->group(function () {
        Route::get('/', [ContentTypeController::class, 'index'])->middleware('permission:form-builder.view')->name('index');
        Route::get('/create', [ContentTypeController::class, 'create'])->middleware('permission:form-builder.create')->name('create');
        Route::post('/', [ContentTypeController::class, 'store'])->middleware('permission:form-builder.create')->name('store');
        Route::get('/{contentType}/edit', [ContentTypeController::class, 'edit'])->middleware('permission:form-builder.edit')->name('edit');
        Route::put('/{contentType}', [ContentTypeController::class, 'update'])->middleware('permission:form-builder.edit')->name('update');
        Route::delete('/{contentType}', [ContentTypeController::class, 'destroy'])->middleware('permission:form-builder.delete')->name('destroy');
        Route::post('/{contentType}/fields', [ContentTypeController::class, 'storeField'])->middleware('permission:form-builder.edit')->name('fields.store');
        Route::delete('/{contentType}/fields/{field}', [ContentTypeController::class, 'destroyField'])->middleware('permission:form-builder.edit')->whereNumber('field')->name('fields.destroy');
    });

    Route::middleware('module:media')->group(function () {
        Route::get('/media', [MediaController::class, 'index'])->middleware('permission:media.view')->name('media.index');
        Route::post('/media', [MediaController::class, 'store'])->middleware('permission:media.create')->name('media.store');
        Route::put('/media/{mediaAsset}', [MediaController::class, 'update'])->middleware('permission:media.edit')->name('media.update');
        Route::delete('/media/{mediaAsset}', [MediaController::class, 'destroy'])->middleware('permission:media.delete')->name('media.destroy');
        Route::post('/media/folders', [MediaController::class, 'storeFolder'])->middleware('permission:media.create')->name('media.folders.store');
        Route::delete('/media/folders/{mediaFolder}', [MediaController::class, 'destroyFolder'])->middleware('permission:media.delete')->name('media.folders.destroy');
    });

    Route::middleware('module:pages')->group(function () {
        Route::get('/pages', [PageController::class, 'index'])->middleware('permission:pages.view')->name('pages.index');
        Route::get('/pages/create', [PageController::class, 'create'])->middleware('permission:pages.create')->name('pages.create');
        Route::post('/pages', [PageController::class, 'store'])->middleware('permission:pages.create')->name('pages.store');
        Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->middleware('permission:pages.edit')->name('pages.edit');
        Route::put('/pages/{page}', [PageController::class, 'update'])->middleware('permission:pages.edit')->name('pages.update');
        Route::delete('/pages/{page}', [PageController::class, 'destroy'])->middleware('permission:pages.delete')->name('pages.destroy');
    });

    Route::middleware('module:menu-manager')->group(function () {
        Route::get('/menus', [MenuController::class, 'index'])->middleware('permission:menu-manager.view')->name('menus.index');
        Route::post('/menus/items', [MenuController::class, 'store'])->middleware('permission:menu-manager.create')->name('menus.store');
        Route::put('/menus/items/{menuItem}', [MenuController::class, 'update'])->middleware('permission:menu-manager.edit')->name('menus.update');
        Route::delete('/menus/items/{menuItem}', [MenuController::class, 'destroy'])->middleware('permission:menu-manager.delete')->name('menus.destroy');
    });

    Route::middleware('module:layout-builder')->group(function () {
        Route::get('/layout', [LayoutController::class, 'index'])->middleware('permission:layout-builder.view')->name('layout.index');
        Route::post('/layout', [LayoutController::class, 'store'])->middleware('permission:layout-builder.create')->name('layout.store');
        Route::put('/layout/{layoutBlock}', [LayoutController::class, 'update'])->middleware('permission:layout-builder.edit')->name('layout.update');
        Route::delete('/layout/{layoutBlock}', [LayoutController::class, 'destroy'])->middleware('permission:layout-builder.delete')->name('layout.destroy');
    });

    Route::middleware('module:header-footer')->group(function () {
        Route::get('/header', [HeaderFooterController::class, 'edit'])->middleware('permission:header-footer.view')->name('header.edit');
        Route::put('/header', [HeaderFooterController::class, 'update'])->middleware('permission:header-footer.update')->name('header.update');
    });

    Route::middleware('module:seo')->group(function () {
        Route::get('/seo', [SeoController::class, 'edit'])->middleware('permission:seo.view')->name('seo.edit');
        Route::put('/seo', [SeoController::class, 'update'])->middleware('permission:seo.update')->name('seo.update');
    });

    Route::middleware('module:analytics')->group(function () {
        Route::get('/analytics', [AnalyticsController::class, 'edit'])->middleware('permission:analytics.view')->name('analytics.edit');
        Route::put('/analytics', [AnalyticsController::class, 'update'])->middleware('permission:analytics.update')->name('analytics.update');
    });

    Route::middleware('module:commerce')->group(function () {
        Route::get('/commerce', [CommerceController::class, 'edit'])->middleware('permission:commerce.view')->name('commerce.edit');
        Route::put('/commerce', [CommerceController::class, 'update'])->middleware('permission:commerce.update')->name('commerce.update');
        Route::put('/commerce/orders/{order}', [CommerceController::class, 'updateOrder'])->middleware('permission:commerce.update')->name('commerce.orders.update');
    });

    Route::middleware('module:email')->group(function () {
        Route::get('/email', [EmailController::class, 'edit'])->middleware('permission:email.view')->name('email.edit');
        Route::put('/email', [EmailController::class, 'update'])->middleware('permission:email.update')->name('email.update');
    });

    Route::middleware('module:feedback')->group(function () {
        Route::get('/feedback', [FeedbackController::class, 'index'])->middleware('permission:feedback.view')->name('feedback.index');
        Route::put('/feedback/settings', [FeedbackController::class, 'settings'])->middleware('permission:feedback.update')->name('feedback.settings');
        Route::put('/feedback/{feedback}', [FeedbackController::class, 'update'])->middleware('permission:feedback.update')->name('feedback.update');
        Route::delete('/feedback/{feedback}', [FeedbackController::class, 'destroy'])->middleware('permission:feedback.delete')->name('feedback.destroy');
    });

    Route::middleware('module:backup')->group(function () {
        Route::get('/backups', [TenantBackupController::class, 'index'])->middleware('permission:backup.view')->name('backups.index');
        Route::post('/backups', [TenantBackupController::class, 'store'])->middleware('permission:backup.create')->name('backups.store');
        Route::get('/backups/{backup}/download', [TenantBackupController::class, 'download'])->middleware(['permission:backup.download', 'signed'])->name('backups.download');
        Route::delete('/backups/{backup}', [TenantBackupController::class, 'destroy'])->middleware('permission:backup.delete')->name('backups.destroy');
    });

    Route::middleware('module:theme-settings')->group(function () {
        Route::get('/theme', [ThemeController::class, 'edit'])->middleware('permission:theme-settings.view')->name('theme.edit');
        Route::put('/theme', [ThemeController::class, 'update'])->middleware('permission:theme-settings.update')->name('theme.update');
    });

    foreach (config('modules.catalog', []) as $slug => $module) {
        if ($module['implemented'] ?? false) {
            continue;
        }

        Route::middleware(['module:'.$slug, 'permission:'.$slug.'.view'])
            ->get('/'.$slug, [ModulePlaceholderController::class, 'show'])
            ->defaults('module', $slug)
            ->name('modules.'.$slug);
    }
});
