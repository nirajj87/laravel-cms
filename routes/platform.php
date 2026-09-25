<?php

use App\Http\Controllers\Platform\ActivityController;
use App\Http\Controllers\Platform\BackupController;
use App\Http\Controllers\Platform\DashboardController;
use App\Http\Controllers\Platform\SettingController;
use App\Http\Controllers\Platform\TenantController;
use App\Http\Controllers\Platform\TenantUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'superadmin'])->prefix('platform')->name('platform.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');

    Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
    Route::post('/backups', [BackupController::class, 'store'])->name('backups.store');
    Route::get('/backups/{backup}/download', [BackupController::class, 'download'])->middleware('signed')->name('backups.download');
    Route::delete('/backups/{backup}', [BackupController::class, 'destroy'])->name('backups.destroy');

    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings/{group}', [SettingController::class, 'update'])->name('settings.update');

    Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
    Route::get('/tenants/create', [TenantController::class, 'create'])->name('tenants.create');
    Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
    Route::get('/tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show');
    Route::get('/tenants/{tenant}/edit', [TenantController::class, 'edit'])->name('tenants.edit');
    Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
    Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy'])->name('tenants.destroy');
    Route::post('/tenants/{tenant}/enter', [TenantController::class, 'enter'])->name('tenants.enter');

    Route::get('/tenants/{tenant}/users', [TenantUserController::class, 'index'])->name('tenants.users.index');
    Route::get('/tenants/{tenant}/users/create', [TenantUserController::class, 'create'])->name('tenants.users.create');
    Route::post('/tenants/{tenant}/users', [TenantUserController::class, 'store'])->name('tenants.users.store');
    Route::get('/tenants/{tenant}/users/{tenantUser}/edit', [TenantUserController::class, 'edit'])->name('tenants.users.edit');
    Route::put('/tenants/{tenant}/users/{tenantUser}', [TenantUserController::class, 'update'])->name('tenants.users.update');
    Route::delete('/tenants/{tenant}/users/{tenantUser}', [TenantUserController::class, 'destroy'])->name('tenants.users.destroy');
});
