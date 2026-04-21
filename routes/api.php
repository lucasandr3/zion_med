<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\ChooseClinicController;
use App\Http\Controllers\Api\V1\DocumentSendController;
use App\Http\Controllers\Api\V1\ClinicSettingsController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\IntegrationsController;
use App\Http\Controllers\Api\V1\LinkBioController;
use App\Http\Controllers\Api\V1\LinksPublicosController;
use App\Http\Controllers\Api\V1\MeAppearanceController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\OrganizationPresenceController;
use App\Http\Controllers\Api\V1\OrganizationRoleController;
use App\Http\Controllers\Api\V1\PermissionCatalogController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PersonController;
use App\Http\Controllers\Api\V1\ProtocolController;
use App\Http\Controllers\Api\V1\TemplateController;
use App\Http\Controllers\Api\V1\ComeceController as ComeceApiController;
use App\Http\Controllers\Api\V1\DemonstrationRequestController;
use App\Http\Controllers\Api\V1\LandingController;
use App\Http\Controllers\Api\V1\PublicFormApiController;
use App\Http\Controllers\Api\V1\PublicFormOtpController;
use App\Http\Controllers\Api\V1\StatusController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WhatsappEvolutionController;
use App\Http\Controllers\Api\V1\Platform\AuditLogController as PlatformAuditLogController;
use App\Http\Controllers\Api\V1\Platform\BillingOverviewController as PlatformBillingOverviewController;
use App\Http\Controllers\Api\V1\Platform\DashboardController as PlatformDashboardController;
use App\Http\Controllers\Api\V1\Platform\LeadsController as PlatformLeadsController;
use App\Http\Controllers\Api\V1\Platform\PlanController as PlatformPlanController;
use App\Http\Controllers\Api\V1\Platform\SettingsController as PlatformSettingsController;
use App\Http\Controllers\Api\V1\Platform\TenantsController as PlatformTenantsController;
use App\Http\Controllers\Api\V1\Platform\OrganizationPresenceController as PlatformOrganizationPresenceController;
use App\Http\Controllers\Platform\PlatformStatusController;
use App\Models\DocumentSend;
use App\Models\FormSubmission;
use Illuminate\Support\Facades\Route;

// Auth e formulário público (sem auth:sanctum)
Route::prefix('v1')->middleware('throttle:api')->group(function () {
    Route::middleware('throttle:auth')->group(function () {
        Route::post('/auth/login', [AuthController::class, 'login'])->name('api.v1.auth.login');
        Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->name('api.v1.auth.forgot-password');
    });
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->name('api.v1.auth.reset-password');
    Route::get('/auth/verify-email', [AuthController::class, 'verifyEmail'])->name('verification.verify');

    Route::get('/landing', LandingController::class)->name('api.v1.landing');
    Route::get('/status', [StatusController::class, 'index'])->name('api.v1.status');
    Route::get('/link-bio/public/{slug}/go/{linkId}', [LinkBioController::class, 'publicRedirectLink'])
        ->whereNumber('linkId')
        ->name('api.v1.link-bio.public-go');
    Route::get('/link-bio/public/{slug}', [LinkBioController::class, 'publicBySlug'])->name('api.v1.link-bio.public');
    Route::post('/comece', [ComeceApiController::class, 'store'])->name('api.v1.comece.store');
    Route::post('/demonstracao', [DemonstrationRequestController::class, 'store'])->name('api.v1.demonstracao.store');
    Route::post('/organization-presence/leave-beacon', [OrganizationPresenceController::class, 'leaveBeacon'])
        ->middleware('throttle:120,1')
        ->name('api.v1.organization-presence.leave-beacon');
    Route::get('/formulario-publico/{token}', [PublicFormApiController::class, 'show'])->name('api.v1.formulario-publico.show');
    Route::get('/formulario-publico/{token}/feegow/disponibilidade', [PublicFormApiController::class, 'feegowAvailability'])->name('api.v1.formulario-publico.feegow.disponibilidade');
    Route::post('/formulario-publico/{token}/validate-person', [PublicFormApiController::class, 'validatePerson'])->name('api.v1.formulario-publico.validate-person');
    Route::post('/formulario-publico/{token}/submit', [PublicFormApiController::class, 'submit'])->name('api.v1.formulario-publico.submit');
    Route::post('/formulario-publico/{token}/otp/send', [PublicFormOtpController::class, 'send'])->name('api.v1.formulario-publico.otp.send');
    Route::post('/formulario-publico/{token}/otp/verify', [PublicFormOtpController::class, 'verify'])->name('api.v1.formulario-publico.otp.verify');
});

Route::bind('protocol', fn ($value) => FormSubmission::findOrFail($value));
Route::bind('documentSend', fn ($value) => DocumentSend::findOrFail($value));

// Rotas que qualquer usuário autenticado pode acessar (incl. platform_admin): logout, me, notificações
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
    Route::post('/auth/send-verification-email', [AuthController::class, 'sendVerificationEmail'])->name('api.v1.auth.send-verification-email');
    Route::get('/me', MeController::class)->name('api.v1.me');
    Route::patch('/me/appearance', [MeAppearanceController::class, 'update'])->name('api.v1.me.appearance');
    Route::get('/notificacoes', [NotificationController::class, 'index'])->name('api.v1.notificacoes.index');
    Route::patch('/notificacoes/{id}/lida', [NotificationController::class, 'markAsRead'])->name('api.v1.notificacoes.read');
    Route::post('/notificacoes/marcar-todas', [NotificationController::class, 'markAllAsRead'])->name('api.v1.notificacoes.read.all');
    Route::delete('/notificacoes/limpar-tudo', [NotificationController::class, 'destroyAll'])->name('api.v1.notificacoes.destroy.all');
    Route::delete('/notificacoes/{id}', [NotificationController::class, 'destroy'])->name('api.v1.notificacoes.destroy');

    // Área da plataforma: apenas platform_admin
    Route::prefix('platform')->middleware('platform')->group(function () {
        Route::get('/dashboard', PlatformDashboardController::class)->name('api.v1.platform.dashboard');
        Route::get('/tenants', [PlatformTenantsController::class, 'index'])->name('api.v1.platform.tenants.index');
        Route::get('/tenants/{tenant}', [PlatformTenantsController::class, 'show'])->name('api.v1.platform.tenants.show');
        Route::get('/leads', [PlatformLeadsController::class, 'index'])->name('api.v1.platform.leads.index');
        Route::get('/plans', [PlatformPlanController::class, 'index'])->name('api.v1.platform.plans.index');
        Route::get('/status', [StatusController::class, 'index'])->name('api.v1.platform.status');
        Route::put('/status', [PlatformStatusController::class, 'update'])->name('api.v1.platform.status.update');
        Route::get('/subscriptions', [PlatformBillingOverviewController::class, 'subscriptions'])->name('api.v1.platform.subscriptions.index');
        Route::get('/payments', [PlatformBillingOverviewController::class, 'payments'])->name('api.v1.platform.payments.index');
        Route::get('/invoices', [PlatformBillingOverviewController::class, 'payments'])->name('api.v1.platform.invoices.index');
        Route::get('/settings', [PlatformSettingsController::class, 'index'])->name('api.v1.platform.settings.index');
        Route::put('/settings', [PlatformSettingsController::class, 'update'])->name('api.v1.platform.settings.update');
        Route::get('/logs', [PlatformAuditLogController::class, 'index'])->name('api.v1.platform.logs.index');
        Route::get('/organization-presences', PlatformOrganizationPresenceController::class)->name('api.v1.platform.organization-presences.index');
    });
});

// Rotas de clínica: apenas usuários de clínica (tenant) com e-mail verificado. Dono da plataforma recebe 403.
Route::prefix('v1')->middleware(['auth:sanctum', 'verified', 'tenant', 'tenant.billing', 'throttle:api'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('api.v1.dashboard');
    Route::get('/permissions/catalog', PermissionCatalogController::class)->name('api.v1.permissions.catalog');
    Route::get('/organization-roles', [OrganizationRoleController::class, 'index'])->name('api.v1.organization-roles.index');
    Route::post('/organization-roles', [OrganizationRoleController::class, 'store'])->name('api.v1.organization-roles.store');
    Route::get('/organization-roles/{slug}', [OrganizationRoleController::class, 'show'])->name('api.v1.organization-roles.show');
    Route::put('/organization-roles/{slug}', [OrganizationRoleController::class, 'update'])->name('api.v1.organization-roles.update');
    Route::delete('/organization-roles/{slug}', [OrganizationRoleController::class, 'destroy'])->name('api.v1.organization-roles.destroy');

    Route::get('/usuarios/roles', [UserController::class, 'roles'])->name('api.v1.usuarios.roles');
    Route::apiResource('usuarios', UserController::class)->parameters(['usuarios' => 'usuario'])->names('api.v1.usuarios');

    Route::get('/templates', [TemplateController::class, 'index'])->name('api.v1.templates.index');
    Route::get('/templates/biblioteca', [TemplateController::class, 'biblioteca'])->name('api.v1.templates.biblioteca');
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
    Route::post('/templates/{template}/enviar', [TemplateController::class, 'enviarDocumento'])->name('api.v1.templates.enviar');
    Route::post('/templates/{template}/duplicar', [TemplateController::class, 'duplicar'])->name('api.v1.templates.duplicar');

    Route::apiResource('pessoas', PersonController::class)->parameters(['pessoas' => 'pessoa'])->names('api.v1.pessoas');

    Route::get('/document-sends', [DocumentSendController::class, 'index'])->name('api.v1.document-sends.index');
    Route::post('/document-sends', [DocumentSendController::class, 'store'])->name('api.v1.document-sends.store');
    Route::post('/document-sends/{documentSend}/reenvio', [DocumentSendController::class, 'reenvio'])->name('api.v1.document-sends.reenvio');
    Route::post('/document-sends/{documentSend}/cancel', [DocumentSendController::class, 'cancel'])->name('api.v1.document-sends.cancel');

    Route::get('/protocols/exportar', [ProtocolController::class, 'exportarCsv'])->name('api.v1.protocols.exportar');
    Route::get('/protocols', [ProtocolController::class, 'index'])->name('api.v1.protocols.index');
    Route::get('/protocols/{protocol}', [ProtocolController::class, 'show'])->name('api.v1.protocols.show');
    Route::get('/protocols/{protocol}/timeline', [ProtocolController::class, 'timeline'])->name('api.v1.protocols.timeline');
    Route::get('/protocols/{protocol}/pdf', [ProtocolController::class, 'pdf'])->name('api.v1.protocols.pdf');
    Route::get('/protocols/{protocol}/dossie', [ProtocolController::class, 'exportarDossie'])->name('api.v1.protocols.dossie');
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
    Route::post('/clinica/integracoes/webhook-deliveries/{delivery}/retry', [IntegrationsController::class, 'retryWebhookDelivery'])->name('api.v1.clinica.integracoes.webhook-deliveries.retry');
    Route::get('/clinica/integracoes/sistemas', [IntegrationsController::class, 'systemsIndex'])->name('api.v1.clinica.integracoes.sistemas.index');
    Route::get('/clinica/integracoes/sistemas/feegow', [IntegrationsController::class, 'feegowShow'])->name('api.v1.clinica.integracoes.sistemas.feegow.show');
    Route::put('/clinica/integracoes/sistemas/feegow', [IntegrationsController::class, 'feegowUpdate'])->name('api.v1.clinica.integracoes.sistemas.feegow.update');
    Route::post('/clinica/integracoes/sistemas/feegow/test', [IntegrationsController::class, 'feegowTest'])->name('api.v1.clinica.integracoes.sistemas.feegow.test');
    Route::get('/clinica/integracoes/sistemas/feegow/catalogos', [IntegrationsController::class, 'feegowCatalogs'])->name('api.v1.clinica.integracoes.sistemas.feegow.catalogos');
    Route::get('/clinica/integracoes/sistemas/feegow/disponibilidade', [IntegrationsController::class, 'feegowAvailableSchedule'])->name('api.v1.clinica.integracoes.sistemas.feegow.disponibilidade');
    Route::post('/clinica/integracoes/sistemas/feegow/agendamentos', [IntegrationsController::class, 'feegowCreateAppointment'])->name('api.v1.clinica.integracoes.sistemas.feegow.agendamentos.store');

    Route::get('/clinica/whatsapp/evolution', [WhatsappEvolutionController::class, 'show'])->name('api.v1.clinica.whatsapp.evolution.show');
    Route::post('/clinica/whatsapp/evolution/instance', [WhatsappEvolutionController::class, 'store'])->name('api.v1.clinica.whatsapp.evolution.instance.store');
    Route::post('/clinica/whatsapp/evolution/connect', [WhatsappEvolutionController::class, 'connect'])->name('api.v1.clinica.whatsapp.evolution.connect');
    Route::get('/clinica/whatsapp/evolution/qr', [WhatsappEvolutionController::class, 'qr'])->name('api.v1.clinica.whatsapp.evolution.qr');
    Route::post('/clinica/whatsapp/evolution/pair', [WhatsappEvolutionController::class, 'pair'])->name('api.v1.clinica.whatsapp.evolution.pair');
    Route::post('/clinica/whatsapp/evolution/disconnect', [WhatsappEvolutionController::class, 'disconnect'])->name('api.v1.clinica.whatsapp.evolution.disconnect');
    Route::delete('/clinica/whatsapp/evolution/instance', [WhatsappEvolutionController::class, 'destroy'])->name('api.v1.clinica.whatsapp.evolution.instance.destroy');
    Route::post('/clinica/whatsapp/evolution/test', [WhatsappEvolutionController::class, 'testMessage'])->name('api.v1.clinica.whatsapp.evolution.test');

    Route::get('/billing', [BillingController::class, 'index'])->name('api.v1.billing.index');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('api.v1.billing.checkout');
    Route::post('/billing/subscriptions/{subscription}/cancel', [BillingController::class, 'cancelSubscription'])->name('api.v1.billing.subscriptions.cancel');
    Route::post('/billing/change-plan', [BillingController::class, 'changePlan'])->name('api.v1.billing.change-plan');
});
