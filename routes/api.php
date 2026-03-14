<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\ChooseClinicController;
use App\Http\Controllers\Api\V1\ClinicSettingsController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\IntegrationsController;
use App\Http\Controllers\Api\V1\LinkBioController;
use App\Http\Controllers\Api\V1\LinksPublicosController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProtocolController;
use App\Http\Controllers\Api\V1\TemplateController;
use App\Http\Controllers\Api\V1\ComeceController as ComeceApiController;
use App\Http\Controllers\Api\V1\LandingController;
use App\Http\Controllers\Api\V1\PublicFormApiController;
use App\Http\Controllers\Api\V1\UserController;
use App\Models\FormSubmission;
use Illuminate\Support\Facades\Route;

// Auth e formulário público (sem auth:sanctum)
Route::prefix('v1')->middleware('throttle:api')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->name('api.v1.auth.login');

    Route::get('/landing', LandingController::class)->name('api.v1.landing');
    Route::post('/comece', [ComeceApiController::class, 'store'])->name('api.v1.comece.store');
    Route::get('/formulario-publico/{token}', [PublicFormApiController::class, 'show'])->name('api.v1.formulario-publico.show');
    Route::post('/formulario-publico/{token}/submit', [PublicFormApiController::class, 'submit'])->name('api.v1.formulario-publico.submit');
});

Route::bind('protocol', fn ($value) => FormSubmission::findOrFail($value));

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');

    Route::get('/me', MeController::class)->name('api.v1.me');
    Route::get('/dashboard', DashboardController::class)->name('api.v1.dashboard');

    Route::get('/usuarios/roles', [UserController::class, 'roles'])->name('api.v1.usuarios.roles');
    Route::apiResource('usuarios', UserController::class)->parameters(['usuarios' => 'usuario'])->names('api.v1.usuarios');

    Route::get('/templates', [TemplateController::class, 'index'])->name('api.v1.templates.index');
    Route::post('/templates', [TemplateController::class, 'store'])->name('api.v1.templates.store');
    Route::post('/templates/a-partir-de/{template}', [TemplateController::class, 'storeFromTemplate'])->name('api.v1.templates.storeFromTemplate');
    Route::get('/templates/{template}', [TemplateController::class, 'show'])->name('api.v1.templates.show');
    Route::put('/templates/{template}', [TemplateController::class, 'update'])->name('api.v1.templates.update');
    Route::delete('/templates/{template}', [TemplateController::class, 'destroy'])->name('api.v1.templates.destroy');
    Route::get('/templates/{template}/campos', [TemplateController::class, 'campos'])->name('api.v1.templates.campos.index');
    Route::post('/templates/{template}/campos', [TemplateController::class, 'storeCampo'])->name('api.v1.templates.campos.store');
    Route::put('/templates/{template}/campos/{campo}', [TemplateController::class, 'updateCampo'])->name('api.v1.templates.campos.update');
    Route::delete('/templates/{template}/campos/{campo}', [TemplateController::class, 'destroyCampo'])->name('api.v1.templates.campos.destroy');
    Route::post('/templates/{template}/link-publico', [TemplateController::class, 'gerarLink'])->name('api.v1.templates.link.gerar');
    Route::delete('/templates/{template}/link-publico', [TemplateController::class, 'desativarLink'])->name('api.v1.templates.link.desativar');

    Route::get('/protocols/exportar', [ProtocolController::class, 'exportarCsv'])->name('api.v1.protocols.exportar');
    Route::get('/protocols', [ProtocolController::class, 'index'])->name('api.v1.protocols.index');
    Route::get('/protocols/{protocol}', [ProtocolController::class, 'show'])->name('api.v1.protocols.show');
    Route::get('/protocols/{protocol}/pdf', [ProtocolController::class, 'pdf'])->name('api.v1.protocols.pdf');
    Route::post('/protocols/{protocol}/revisao', [ProtocolController::class, 'aprovar'])->name('api.v1.protocols.revisao');
    Route::post('/protocols/{protocol}/comentario', [ProtocolController::class, 'comentario'])->name('api.v1.protocols.comentario');

    Route::get('/links-publicos', [LinksPublicosController::class, 'index'])->name('api.v1.links-publicos.index');

    Route::get('/link-bio', [LinkBioController::class, 'index'])->name('api.v1.link-bio.index');
    Route::post('/link-bio/links', [LinkBioController::class, 'store'])->name('api.v1.link-bio.links.store');
    Route::put('/link-bio/links/{link}', [LinkBioController::class, 'update'])->name('api.v1.link-bio.links.update');
    Route::delete('/link-bio/links/{link}', [LinkBioController::class, 'destroy'])->name('api.v1.link-bio.links.destroy');
    Route::post('/link-bio/links/reorder', [LinkBioController::class, 'reorder'])->name('api.v1.link-bio.links.reorder');
    Route::put('/link-bio/aparencia', [LinkBioController::class, 'updateAparencia'])->name('api.v1.link-bio.aparencia.update');

    Route::get('/clinica/escolher', [ChooseClinicController::class, 'index'])->name('api.v1.clinica.escolher.index');
    Route::post('/clinica/escolher', [ChooseClinicController::class, 'store'])->name('api.v1.clinica.escolher.store');
    Route::get('/clinica/configuracoes', [ClinicSettingsController::class, 'show'])->name('api.v1.clinica.configuracoes.show');
    Route::put('/clinica/configuracoes', [ClinicSettingsController::class, 'update'])->name('api.v1.clinica.configuracoes.update');
    Route::get('/clinica/logs', [AuditLogController::class, 'index'])->name('api.v1.clinica.logs.index');
    Route::get('/clinica/integracoes', [IntegrationsController::class, 'index'])->name('api.v1.clinica.integracoes.index');
    Route::post('/clinica/integracoes/tokens', [IntegrationsController::class, 'createToken'])->name('api.v1.clinica.integracoes.tokens.store');
    Route::delete('/clinica/integracoes/tokens/{token}', [IntegrationsController::class, 'revokeToken'])->name('api.v1.clinica.integracoes.tokens.destroy');
    Route::post('/clinica/integracoes/webhooks', [IntegrationsController::class, 'storeWebhook'])->name('api.v1.clinica.integracoes.webhooks.store');
    Route::put('/clinica/integracoes/webhooks/{webhook}', [IntegrationsController::class, 'updateWebhook'])->name('api.v1.clinica.integracoes.webhooks.update');
    Route::delete('/clinica/integracoes/webhooks/{webhook}', [IntegrationsController::class, 'destroyWebhook'])->name('api.v1.clinica.integracoes.webhooks.destroy');

    Route::get('/billing', [BillingController::class, 'index'])->name('api.v1.billing.index');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('api.v1.billing.checkout');

    Route::get('/notificacoes', [NotificationController::class, 'index'])->name('api.v1.notificacoes.index');
    Route::patch('/notificacoes/{id}/lida', [NotificationController::class, 'markAsRead'])->name('api.v1.notificacoes.read');
    Route::post('/notificacoes/marcar-todas', [NotificationController::class, 'markAllAsRead'])->name('api.v1.notificacoes.read.all');
    Route::delete('/notificacoes/limpar-tudo', [NotificationController::class, 'destroyAll'])->name('api.v1.notificacoes.destroy.all');
    Route::delete('/notificacoes/{id}', [NotificationController::class, 'destroy'])->name('api.v1.notificacoes.destroy');
});
