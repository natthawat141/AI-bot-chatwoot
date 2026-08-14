<?php

use App\Http\Controllers\Api\BotInteractionController;
use App\Http\Controllers\Api\BusinessProfileApiController;
use App\Http\Controllers\Api\CatalogSearchController;
use App\Http\Controllers\Api\KnowledgeApiController;
use Illuminate\Support\Facades\Route;

/*
 * Read-only knowledge API consumed by the Chatwoot AI service.
 * Guarded by a revocable hashed bearer token and rate limited.
 * All endpoints return only active records.
 */
Route::middleware(['api.token:read', 'throttle:120,1'])->prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/meta', [KnowledgeApiController::class, 'meta'])->name('meta');
    Route::get('/packages', [KnowledgeApiController::class, 'packages'])->name('packages');
    Route::get('/faqs', [KnowledgeApiController::class, 'faqs'])->name('faqs');
    Route::get('/knowledge', [KnowledgeApiController::class, 'knowledge'])->name('knowledge');
    Route::get('/business-profile', [BusinessProfileApiController::class, 'show'])->name('business-profile');
    Route::post('/catalog/search', [CatalogSearchController::class, 'search'])->name('catalog.search');
    Route::get('/catalog/{package}', [CatalogSearchController::class, 'show'])->name('catalog.show');
});

Route::post('/v1/interactions', [BotInteractionController::class, 'store'])
    ->middleware(['api.token:analytics:write', 'throttle:120,1'])
    ->name('api.v1.interactions.store');
