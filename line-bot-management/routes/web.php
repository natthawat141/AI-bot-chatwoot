<?php

use App\Http\Controllers\Admin\ApiTokenController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\GuideController;
use App\Http\Controllers\Admin\ImportExportController;
use App\Http\Controllers\Admin\KnowledgeEntryController;
use App\Http\Controllers\Admin\PackageCategoryController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

// This is an internal admin tool only — root goes to the dashboard (or login).
Route::get('/', fn () => redirect()->route(auth()->check() ? 'admin.dashboard' : 'login'))->name('home');

// Guest authentication.
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Protected admin area.
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/guide', GuideController::class)->name('guide');

    Route::resource('package-categories', PackageCategoryController::class)
        ->parameters(['package-categories' => 'category'])
        ->except('show');
    Route::resource('packages', PackageController::class)->except('show');
    Route::resource('faqs', FaqController::class)->except('show');
    Route::resource('knowledge', KnowledgeEntryController::class)
        ->parameters(['knowledge' => 'knowledge'])
        ->except('show');

    // Revocable hashed bearer tokens for the knowledge API.
    Route::get('/api-tokens', [ApiTokenController::class, 'index'])->name('api-tokens.index');
    Route::post('/api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::delete('/api-tokens/{token}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');

    // Safe package/promotion import only: preview before confirming, never overwrite by code.
    Route::get('/imports', [ImportExportController::class, 'index'])->name('imports.index');
    Route::post('/imports/packages/preview', [ImportExportController::class, 'preview'])->name('imports.preview');
    Route::post('/imports/packages/confirm', [ImportExportController::class, 'confirm'])->name('imports.confirm');
    Route::post('/imports/packages/cancel', [ImportExportController::class, 'cancel'])->name('imports.cancel');
    Route::get('/imports/packages/template', [ImportExportController::class, 'template'])->name('imports.template');
    Route::get('/exports/packages', [ImportExportController::class, 'export'])->name('exports.packages');
});
