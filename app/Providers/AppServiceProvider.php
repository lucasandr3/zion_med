<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\Clinic;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\User;
use App\Services\PlatformConfigService;
use App\View\Composers\ThemeComposer;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        App::setLocale('pt_BR');

        try {
            PlatformConfigService::mergeIntoConfig();
        } catch (\Throwable) {
            // Tabelas platform_settings/plans podem ainda não existir (ex.: durante migrate)
        }

        // Listener AuditEvent → LogAuditListener registrado apenas por descoberta automática (app/Listeners)

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer', 'JWT')->as('bearerAuth')->setDescription('Token de API (Sanctum). Header: Authorization: Bearer {token}')
                );
            });

        View::composer('layouts.app', ThemeComposer::class);

        View::composer('layouts.platform', function (\Illuminate\View\View $view): void {
            $user = request()->user();
            $view->with('unreadNotifications', $user ? $user->unreadNotifications()->count() : 0);
        });

        Gate::define('manage-clinic', function (User $user) {
            return $user->role->canManageClinic();
        });
        Gate::define('manage-users', function (User $user) {
            return $user->role->canManageUsers();
        });
        Gate::define('manage-templates', function (User $user) {
            return $user->role->canManageTemplates();
        });
        Gate::define('approve-submissions', function (User $user) {
            return $user->role->canApproveSubmissions();
        });
        Gate::define('view-submissions', function (User $user) {
            return $user->role->canViewSubmissions();
        });
        Gate::define('viewApiDocs', function (?User $user) {
            return $user !== null;
        });

        Gate::define('viewLogViewer', function (?User $user) {
            if ($user === null) {
                return false;
            }
            return in_array($user->role, [Role::SuperAdmin, Role::PlatformAdmin], true);
        });

        /** Quem pode conceder "acessar todas as clínicas" a outro usuário (SuperAdmin ou Owner na mesma clínica). */
        Gate::define('grant-clinic-switch', function (User $user, ?User $target = null) {
            if ($user->role === Role::SuperAdmin) {
                return true;
            }
            if ($user->role !== Role::Owner) {
                return false;
            }
            if ($target === null) {
                return true;
            }
            return (string) $user->clinic_id === (string) $target->clinic_id;
        });

        Gate::define('update-clinic', function (User $user, Clinic $clinic) {
            if ($user->canSwitchClinic()) {
                return (string) $clinic->id === (string) session('current_clinic_id');
            }
            return $user->role === Role::Owner && $user->clinic_id === $clinic->id;
        });
        Gate::define('update-user', function (User $user, User $target) {
            if ($user->canSwitchClinic()) {
                return (string) $target->clinic_id === (string) session('current_clinic_id');
            }
            if ($user->role !== Role::Owner) {
                return false;
            }
            return $user->clinic_id === $target->clinic_id;
        });
        Gate::define('view-template', function (User $user, FormTemplate $template) {
            if (! $user->role->canViewSubmissions()) {
                return false;
            }
            if ($user->canSwitchClinic()) {
                return (string) $template->clinic_id === (string) session('current_clinic_id');
            }
            return $user->clinic_id === $template->clinic_id;
        });
        Gate::define('update-template', function (User $user, FormTemplate $template) {
            if (! $user->role->canManageTemplates()) {
                return false;
            }
            if ($user->canSwitchClinic()) {
                return (string) $template->clinic_id === (string) session('current_clinic_id');
            }
            return $user->clinic_id === $template->clinic_id;
        });
        Gate::define('view-submission', function (User $user, FormSubmission $submission) {
            if (! $user->role->canViewSubmissions()) {
                return false;
            }
            if ($user->canSwitchClinic()) {
                return (string) $submission->clinic_id === (string) session('current_clinic_id');
            }
            return $user->clinic_id === $submission->clinic_id;
        });
        Gate::define('approve-submission', function (User $user, FormSubmission $submission) {
            if (! $user->role->canApproveSubmissions()) {
                return false;
            }
            if ($user->canSwitchClinic()) {
                return (string) $submission->clinic_id === (string) session('current_clinic_id');
            }
            return $user->clinic_id === $submission->clinic_id;
        });
    }
}
