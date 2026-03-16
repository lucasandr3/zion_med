<?php

use App\Http\Controllers\StatusPageController;
use App\Http\Controllers\Webhook\AsaasWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas Web (mínimas — projeto API-first; UI no front Angular)
|--------------------------------------------------------------------------
| Mantidas apenas: página de status do serviço e webhook Asaas.
*/

Route::get('/status', [StatusPageController::class, 'show'])->name('status');

Route::post('/webhooks/asaas', [AsaasWebhookController::class, 'handle'])->name('webhooks.asaas');
