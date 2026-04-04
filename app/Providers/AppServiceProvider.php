<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\Clinic;
use App\Models\Organization;
use App\Support\Permission;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\User;
use App\Services\PlatformConfigService;
use App\Support\MailBrand;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Link de redefinição de senha no e-mail: apontar para o frontend (SPA) quando FRONTEND_URL estiver definida
        $frontendUrl = config('app.frontend_url');
        $spaPasswordResetUrl = static function (object $notifiable, string $token) use ($frontendUrl): string {
            if ($frontendUrl !== null && $frontendUrl !== '') {
                $base = rtrim((string) $frontendUrl, '/');
                $email = $notifiable->getEmailForPasswordReset();

                return $base . '/redefinir-senha?' . http_build_query([
                    'token' => $token,
                    'email' => $email,
                ]);
            }

            return url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
        };

        ResetPassword::createUrlUsing($spaPasswordResetUrl);

        $brandName = (string) (config('mail.branding.product_name')
            ?: config('asaas.product_name')
            ?: config('app.name'));

        ResetPassword::toMailUsing(function (object $notifiable, string $token) use ($spaPasswordResetUrl, $brandName): MailMessage {
            $url = $spaPasswordResetUrl($notifiable, $token);
            $expire = (int) config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

            return (new MailMessage)
                ->subject('Redefinir sua senha — ' . $brandName)
                ->view('emails.auth.reset-password', MailBrand::with([
                    'userName' => $notifiable->name ?? '',
                    'actionUrl' => $url,
                    'emailTitle' => 'Redefinir senha',
                    'expireMinutes' => $expire,
                ]));
        });

        if ($frontendUrl !== null && $frontendUrl !== '') {
            // Link de verificação de e-mail: assinatura gerada para a API; no e-mail enviamos o link do frontend com os mesmos query params.
            VerifyEmail::createUrlUsing(function (object $notifiable) use ($frontendUrl) {
                $apiUrl = URL::temporarySignedRoute(
                    'verification.verify',
                    now()->addMinutes(60),
                    [
                        'id' => $notifiable->getKey(),
                        'hash' => sha1($notifiable->getEmailForVerification()),
                    ]
                );
                $query = parse_url($apiUrl, PHP_URL_QUERY);

                return rtrim((string) $frontendUrl, '/') . '/verificar-email?' . $query;
            });
        }

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) use ($brandName): MailMessage {
            return (new MailMessage)
                ->subject('Confirme seu e-mail — ' . $brandName)
                ->view('emails.auth.verify-email', MailBrand::with([
                    'userName' => $notifiable->name ?? '',
                    'actionUrl' => $url,
                    'emailTitle' => 'Confirmar e-mail',
                ]));
        });

        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer', 'JWT')->as('bearerAuth')->setDescription('Token de API (Sanctum). Header: Authorization: Bearer {token}')
                );
            });

        Gate::define('view-dashboard', function (User $user) {
            return $user->hasPermission(Permission::DASHBOARD_ACCESS);
        });
        Gate::define('view-notifications', function (User $user) {
            return $user->hasPermission(Permission::NOTIFICATIONS_ACCESS);
        });
        Gate::define('manage-billing', function (User $user) {
            return $user->hasPermission(Permission::BILLING_MANAGE);
        });
        Gate::define('manage-clinic', function (User $user) {
            return $user->hasPermission(Permission::ORGANIZATION_MANAGE);
        });
        Gate::define('manage-users', function (User $user) {
            return $user->hasPermission(Permission::USERS_MANAGE);
        });
        Gate::define('manage-templates', function (User $user) {
            return $user->hasPermission(Permission::TEMPLATES_MANAGE);
        });
        Gate::define('approve-submissions', function (User $user) {
            return $user->hasPermission(Permission::SUBMISSIONS_APPROVE);
        });
        Gate::define('view-submissions', function (User $user) {
            return $user->hasPermission(Permission::SUBMISSIONS_VIEW);
        });
        Gate::define('viewApiDocs', function (?User $user) {
            return $user !== null;
        });

        Gate::define('viewLogViewer', function (?User $user) {
            if ($user === null) {
                return false;
            }

            return in_array($user->roleEnum(), [Role::SuperAdmin, Role::PlatformAdmin], true);
        });

        /** Quem pode conceder "acessar todas as clínicas" a outro usuário (SuperAdmin ou Owner na mesma clínica). */
        Gate::define('grant-clinic-switch', function (User $user, ?User $target = null) {
            if ($user->roleEnum() === Role::SuperAdmin) {
                return true;
            }
            if (! $user->isOwner()) {
                return false;
            }
            if ($target === null) {
                return true;
            }
            return (string) $user->clinic_id === (string) $target->clinic_id;
        });

        Gate::define('update-clinic', function (User $user, Organization $organization) {
            $contextId = session('current_organization_id') ?? session('current_clinic_id');
            if ($user->canSwitchClinic()) {
                return (string) $organization->id === (string) $contextId;
            }

            return $user->isOwner() && $user->clinic_id === $organization->id;
        });
        Gate::define('update-user', function (User $user, User $target) {
            if ($user->canSwitchClinic()) {
                return (string) $target->clinic_id === (string) session('current_clinic_id');
            }
            if (! $user->isOwner()) {
                return false;
            }
            return $user->clinic_id === $target->clinic_id;
        });
        Gate::define('view-template', function (User $user, FormTemplate $template) {
            if (! $user->hasPermission(Permission::SUBMISSIONS_VIEW)) {
                return false;
            }
            if ($user->canSwitchClinic()) {
                return (string) $template->clinic_id === (string) session('current_clinic_id');
            }
            return $user->clinic_id === $template->clinic_id;
        });
        Gate::define('update-template', function (User $user, FormTemplate $template) {
            if (! $user->hasPermission(Permission::TEMPLATES_MANAGE)) {
                return false;
            }
            if ($user->canSwitchClinic()) {
                return (string) $template->clinic_id === (string) session('current_clinic_id');
            }
            return $user->clinic_id === $template->clinic_id;
        });
        Gate::define('view-submission', function (User $user, FormSubmission $submission) {
            if (! $user->hasPermission(Permission::SUBMISSIONS_VIEW)) {
                return false;
            }
            if ($user->canSwitchClinic()) {
                return (string) $submission->clinic_id === (string) session('current_clinic_id');
            }
            return $user->clinic_id === $submission->clinic_id;
        });
        Gate::define('approve-submission', function (User $user, FormSubmission $submission) {
            if (! $user->hasPermission(Permission::SUBMISSIONS_APPROVE)) {
                return false;
            }
            if ($user->canSwitchClinic()) {
                return (string) $submission->clinic_id === (string) session('current_clinic_id');
            }
            return $user->clinic_id === $submission->clinic_id;
        });
        Gate::define('people-deactivate', function (User $user) {
            return $user->hasPermission(Permission::PEOPLE_DEACTIVATE);
        });
    }
}
