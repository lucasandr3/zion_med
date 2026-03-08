<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ComeceController;
use App\Http\Controllers\ChooseClinicController;
use App\Http\Controllers\ClinicSettingsController;
use App\Http\Controllers\IntegrationsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Platform\DashboardController as PlatformDashboardController;
use App\Http\Controllers\Platform\TenantController as PlatformTenantController;
use App\Http\Controllers\Platform\BillingOverviewController;
use App\Http\Controllers\Platform\DemonstrationRequestController;
use App\Http\Controllers\Platform\PlanController as PlatformPlanController;
use App\Http\Controllers\Platform\PlatformSettingsController;
use App\Http\Controllers\DemonstracaoController;
use App\Http\Controllers\FormSubmissionController;
use App\Http\Controllers\FormTemplateController;
use App\Http\Controllers\LinkBioController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PublicFormController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Webhook\AsaasWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('landing'))->name('home');
Route::post('/demonstracao', [DemonstracaoController::class, 'store'])->name('demonstracao.store');

Route::get('/privacidade', fn () => view('legal.privacidade'))->name('privacidade');
Route::get('/termos-de-uso', fn () => view('legal.termos'))->name('termos');

Route::get('/robots.txt', function () {
    $base = config('app.url');
    return response("User-agent: *\nDisallow:\n\nSitemap: {$base}/sitemap.xml\n", 200, [
        'Content-Type' => 'text/plain; charset=UTF-8',
    ]);
})->name('robots');

Route::get('/sitemap.xml', function () {
    $base = rtrim(config('app.url'), '/');
    $urls = [
        ['loc' => $base . '/', 'priority' => '1.0', 'changefreq' => 'weekly'],
        ['loc' => $base . '/comece', 'priority' => '0.9', 'changefreq' => 'monthly'],
        ['loc' => $base . '/privacidade', 'priority' => '0.3', 'changefreq' => 'monthly'],
        ['loc' => $base . '/termos-de-uso', 'priority' => '0.3', 'changefreq' => 'monthly'],
        ['loc' => $base . '/login', 'priority' => '0.5', 'changefreq' => 'monthly'],
    ];
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u) {
        $xml .= '  <url><loc>' . htmlspecialchars($u['loc']) . '</loc><changefreq>' . $u['changefreq'] . '</changefreq><priority>' . $u['priority'] . '</priority></url>' . "\n";
    }
    $xml .= '</urlset>';
    return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
})->name('sitemap');

Route::get('/comece', [ComeceController::class, 'show'])->name('comece.show');
Route::post('/comece', [ComeceController::class, 'store'])->name('comece.store');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth', 'platform'])
    ->prefix('admin')
    ->name('platform.')
    ->group(function () {
        Route::get('/', PlatformDashboardController::class)->name('dashboard');
        Route::get('/tenants', [PlatformTenantController::class, 'index'])->name('tenants.index');
        Route::get('/tenants/{tenant}', [PlatformTenantController::class, 'show'])->name('tenants.show');

        Route::get('/leads', [DemonstrationRequestController::class, 'index'])->name('leads.index');

        Route::get('/assinaturas', [BillingOverviewController::class, 'subscriptions'])->name('subscriptions.index');
        Route::get('/faturas', [BillingOverviewController::class, 'payments'])->name('payments.index');

        Route::get('/planos', [PlatformPlanController::class, 'index'])->name('plans.index');
        Route::get('/planos/criar', [PlatformPlanController::class, 'create'])->name('plans.create');
        Route::post('/planos', [PlatformPlanController::class, 'store'])->name('plans.store');
        Route::get('/planos/{plan}/editar', [PlatformPlanController::class, 'edit'])->name('plans.edit');
        Route::put('/planos/{plan}', [PlatformPlanController::class, 'update'])->name('plans.update');
        Route::delete('/planos/{plan}', [PlatformPlanController::class, 'destroy'])->name('plans.destroy');

        Route::get('/configuracoes', [PlatformSettingsController::class, 'index'])->name('settings.index');
        Route::put('/configuracoes', [PlatformSettingsController::class, 'update'])->name('settings.update');

        Route::get('/logs', [AuditLogController::class, 'platformIndex'])->name('logs.index');
    });

Route::get('/l/{slug}', [LinkBioController::class, 'public'])->name('link-bio.public');
Route::get('/l/{slug}/out', [LinkBioController::class, 'out'])->name('link-bio.out');

Route::prefix('f')->name('formulario-publico.')->group(function () {
    Route::get('/sucesso', [PublicFormController::class, 'sucesso'])->name('sucesso');
    Route::get('/{token}', [PublicFormController::class, 'show'])->name('show');
    Route::post('/{token}', [PublicFormController::class, 'submit'])->name('submit');
});

Route::post('/webhooks/asaas', [AsaasWebhookController::class, 'handle'])->name('webhooks.asaas');

// Notificações: acessível por qualquer usuário autenticado (cliente ou admin da plataforma). Cada um vê só as suas.
Route::middleware('auth')->group(function () {
    Route::prefix('notificacoes')->name('notificacoes.')->group(function () {
        Route::get('/',                  [NotificationController::class, 'index'])->name('index');
        Route::match(['get', 'patch'], '/{id}/lida', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/marcar-todas',     [NotificationController::class, 'markAllAsRead'])->name('read.all');
        Route::delete('/limpar-tudo',    [NotificationController::class, 'destroyAll'])->name('destroy.all');
        Route::delete('/{id}',           [NotificationController::class, 'destroy'])->name('destroy');
    });
});

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/', [BillingController::class, 'index'])->name('index');
        Route::post('/checkout', [BillingController::class, 'checkout'])->name('checkout');
    });

    Route::get('/links-publicos', [FormTemplateController::class, 'linksPublicos'])->name('links-publicos.index');

    Route::prefix('link-bio')->name('link-bio.')->group(function () {
        Route::get('/', [LinkBioController::class, 'index'])->name('index');
        Route::post('/links', [LinkBioController::class, 'store'])->name('links.store');
        Route::put('/links/{link}', [LinkBioController::class, 'update'])->name('links.update');
        Route::delete('/links/{link}', [LinkBioController::class, 'destroy'])->name('links.destroy');
        Route::post('/links/reorder', [LinkBioController::class, 'reorder'])->name('links.reorder');
        Route::put('/aparencia', [LinkBioController::class, 'updateAparencia'])->name('aparencia.update');
    });

    Route::prefix('clinica')->name('clinica.')->group(function () {
        Route::get('/escolher', [ChooseClinicController::class, 'show'])->name('escolher');
        Route::post('/escolher', [ChooseClinicController::class, 'store'])->name('escolher.store');
        Route::get('/configuracoes', [ClinicSettingsController::class, 'edit'])->name('configuracoes.edit');
        Route::put('/configuracoes', [ClinicSettingsController::class, 'update'])->name('configuracoes.update');
        Route::post('/empresas', [ClinicSettingsController::class, 'storeEmpresa'])->name('empresas.store');
        Route::get('/logs', [AuditLogController::class, 'index'])->name('logs.index');
        Route::get('/integracoes', [IntegrationsController::class, 'index'])->name('integracoes.index');
        Route::post('/integracoes/tokens', [IntegrationsController::class, 'createToken'])->name('integracoes.tokens.store');
        Route::delete('/integracoes/tokens/{token}', [IntegrationsController::class, 'revokeToken'])->name('integracoes.tokens.destroy');
        Route::post('/integracoes/webhooks', [IntegrationsController::class, 'storeWebhook'])->name('integracoes.webhooks.store');
        Route::put('/integracoes/webhooks/{webhook}', [IntegrationsController::class, 'updateWebhook'])->name('integracoes.webhooks.update');
        Route::delete('/integracoes/webhooks/{webhook}', [IntegrationsController::class, 'destroyWebhook'])->name('integracoes.webhooks.destroy');
    });

    Route::prefix('usuarios')->name('usuarios.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/criar', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{usuario}/editar', [UserController::class, 'edit'])->name('edit');
        Route::put('/{usuario}', [UserController::class, 'update'])->name('update');
        Route::delete('/{usuario}', [UserController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('templates')->name('templates.')->group(function () {
        Route::get('/', [FormTemplateController::class, 'index'])->name('index');
        Route::get('/criar', [FormTemplateController::class, 'create'])->name('create');
        Route::get('/criar/em-branco', [FormTemplateController::class, 'createBlank'])->name('create.blank');
        Route::post('/a-partir-de/{template}', [FormTemplateController::class, 'storeFromTemplate'])->name('store.from');
        Route::post('/', [FormTemplateController::class, 'store'])->name('store');
        Route::get('/{template}/editar', [FormTemplateController::class, 'edit'])->name('edit');
        Route::put('/{template}', [FormTemplateController::class, 'update'])->name('update');
        Route::delete('/{template}', [FormTemplateController::class, 'destroy'])->name('destroy');
        Route::get('/{template}/campos', [FormTemplateController::class, 'campos'])->name('campos.index');
        Route::post('/{template}/campos', [FormTemplateController::class, 'storeCampo'])->name('campos.store');
        Route::put('/{template}/campos/{campo}', [FormTemplateController::class, 'updateCampo'])->name('campos.update');
        Route::delete('/{template}/campos/{campo}', [FormTemplateController::class, 'destroyCampo'])->name('campos.destroy');
        Route::post('/{template}/link-publico', [FormTemplateController::class, 'gerarLink'])->name('link.gerar');
        Route::delete('/{template}/link-publico', [FormTemplateController::class, 'desativarLink'])->name('link.desativar');
    });

    Route::prefix('protocolos')->name('protocolos.')->group(function () {
        Route::get('/', [FormSubmissionController::class, 'index'])->name('index');
        Route::get('/exportar', [FormSubmissionController::class, 'exportarCsv'])->name('exportar');
        Route::get('/exportar-pdf', [FormSubmissionController::class, 'exportarPdfLote'])->name('exportar-pdf');
        Route::get('/{submissao}', [FormSubmissionController::class, 'show'])->name('show');
        Route::get('/{submissao}/pdf', [FormSubmissionController::class, 'pdf'])->name('pdf');
        Route::post('/{submissao}/revisao', [FormSubmissionController::class, 'aprovar'])->name('revisao');
        Route::post('/{submissao}/comentario', [FormSubmissionController::class, 'comentario'])->name('comentario');
    });
});
