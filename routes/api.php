<?php

use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\ProtocolController;
use App\Http\Controllers\Api\V1\TemplateController;
use App\Models\FormSubmission;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/me', MeController::class)->name('api.v1.me');
    Route::get('/protocols', [ProtocolController::class, 'index'])->name('api.v1.protocols.index');
    Route::get('/protocols/{protocol}', [ProtocolController::class, 'show'])->name('api.v1.protocols.show')
        ->scopeBindings();
    Route::get('/templates', [TemplateController::class, 'index'])->name('api.v1.templates.index');
    Route::post('/templates', [TemplateController::class, 'store'])->name('api.v1.templates.store');
    Route::get('/templates/{template}', [TemplateController::class, 'show'])->name('api.v1.templates.show')
        ->scopeBindings();
});

Route::bind('protocol', fn ($value) => FormSubmission::findOrFail($value));
