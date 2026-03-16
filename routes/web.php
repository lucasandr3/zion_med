<?php

use App\Http\Controllers\StatusPageController;
use App\Http\Controllers\Webhook\AsaasWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas Web (mínimas — projeto API-first; UI no front Angular)
|--------------------------------------------------------------------------
| Mantidas apenas: health check raiz, página de status e webhook Asaas.
*/

Route::get('/', fn () => response()->json(['status' => 'ok']))->name('health');

Route::get('/status', [StatusPageController::class, 'show'])->name('status');

Route::post('/webhooks/asaas', [AsaasWebhookController::class, 'handle'])->name('webhooks.asaas');
