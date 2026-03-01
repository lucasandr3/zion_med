<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ChooseClinicController;
use App\Http\Controllers\ClinicSettingsController;
use App\Http\Controllers\IntegrationsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FormSubmissionController;
use App\Http\Controllers\FormTemplateController;
use App\Http\Controllers\LinkBioController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PublicFormController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('landing'))->name('home');

Route::get('/privacidade', fn () => view('legal.privacidade'))->name('privacidade');
Route::get('/termos-de-uso', fn () => view('legal.termos'))->name('termos');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/l/{slug}', [LinkBioController::class, 'public'])->name('link-bio.public');
Route::get('/l/{slug}/out', [LinkBioController::class, 'out'])->name('link-bio.out');

Route::prefix('f')->name('formulario-publico.')->group(function () {
    Route::get('/sucesso', [PublicFormController::class, 'sucesso'])->name('sucesso');
    Route::get('/{token}', [PublicFormController::class, 'show'])->name('show');
    Route::post('/{token}', [PublicFormController::class, 'submit'])->name('submit');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/links-publicos', [FormTemplateController::class, 'linksPublicos'])->name('links-publicos.index');

    Route::prefix('link-bio')->name('link-bio.')->group(function () {
        Route::get('/', [LinkBioController::class, 'index'])->name('index');
        Route::post('/links', [LinkBioController::class, 'store'])->name('links.store');
        Route::put('/links/{link}', [LinkBioController::class, 'update'])->name('links.update');
        Route::delete('/links/{link}', [LinkBioController::class, 'destroy'])->name('links.destroy');
        Route::post('/links/reorder', [LinkBioController::class, 'reorder'])->name('links.reorder');
    });

    Route::prefix('clinica')->name('clinica.')->group(function () {
        Route::get('/escolher', [ChooseClinicController::class, 'show'])->name('escolher');
        Route::post('/escolher', [ChooseClinicController::class, 'store'])->name('escolher.store');
        Route::get('/configuracoes', [ClinicSettingsController::class, 'edit'])->name('configuracoes.edit');
        Route::put('/configuracoes', [ClinicSettingsController::class, 'update'])->name('configuracoes.update');
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

    Route::prefix('notificacoes')->name('notificacoes.')->group(function () {
        Route::get('/',                  [NotificationController::class, 'index'])->name('index');
        Route::match(['get', 'patch'], '/{id}/lida', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/marcar-todas',     [NotificationController::class, 'markAllAsRead'])->name('read.all');
        Route::delete('/limpar-tudo',    [NotificationController::class, 'destroyAll'])->name('destroy.all');
        Route::delete('/{id}',           [NotificationController::class, 'destroy'])->name('destroy');
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
