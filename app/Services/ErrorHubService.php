<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use App\Support\ErrorHubSanitizer;
use App\Support\OrganizationContext;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ErrorHubService
{
    /** @var array<string, int> */
    private static array $recentFingerprints = [];

    /**
     * @param  array{
     *     business_title?: string,
     *     business_context?: array<string, mixed>,
     *     level?: string,
     * }  $options
     */
    public function reportException(Throwable $exception, array $options = []): void
    {
        if (! $this->isEnabled() || ! $this->shouldReport($exception)) {
            return;
        }

        $request = $this->resolveRequest();
        $payload = $this->buildPayload($exception, $request, $options);

        $this->send($payload);
    }

    public function shouldReport(Throwable $exception): bool
    {
        if ($exception instanceof ValidationException) {
            return false;
        }

        if ($exception instanceof AuthenticationException) {
            return false;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();

            return match (true) {
                $status === 401,
                $status === 403,
                $status === 404,
                $status === 405,
                $status === 422,
                $status === 429 => false,
                $status >= 500 => true,
                default => $status >= 400,
            };
        }

        return true;
    }

    private function isEnabled(): bool
    {
        $apiKey = config('errorhub.api_key');

        return (bool) config('errorhub.enabled')
            && is_string($apiKey)
            && $apiKey !== '';
    }

    /**
     * @param  array{
     *     business_title?: string,
     *     business_context?: array<string, mixed>,
     *     level?: string,
     * }  $options
     * @return array<string, mixed>
     */
    private function buildPayload(Throwable $exception, ?Request $request, array $options): array
    {
        [$customerId, $customerName] = $this->resolveOrganizationContext($request);

        $payload = [
            'environment' => (string) config('errorhub.environment'),
            'level' => $options['level'] ?? $this->inferLevel($exception),
            'message' => $this->truncate($exception->getMessage() ?: class_basename($exception)),
            'exception' => $exception::class,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $this->truncate($exception->getTraceAsString(), 8000),
            'business_title' => $options['business_title'] ?? 'Erro não tratado na API',
            'business_context' => array_filter([
                'feature' => (string) config('errorhub.feature'),
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                ...($options['business_context'] ?? []),
            ], static fn (mixed $value): bool => $value !== null && $value !== ''),
        ];

        if ($request !== null) {
            $payload['request'] = [
                'method' => $request->method(),
                'url' => '/'.$request->path().($request->getQueryString() ? '?'.$request->getQueryString() : ''),
                'payload' => ErrorHubSanitizer::sanitize($request->all()),
                'ip' => $request->ip(),
                'userAgent' => $request->userAgent(),
            ];
            $payload['business_context']['action'] = $payload['business_context']['action']
                ?? $request->method().' '.$payload['request']['url'];
        }

        $userContext = $this->buildUserContext($request);
        if ($userContext !== null) {
            $payload['user'] = $userContext;
        }

        return $payload;
    }

    /** @param array<string, mixed> $payload */
    private function send(array $payload): void
    {
        $fingerprint = sha1(($payload['level'] ?? '').'|'.($payload['message'] ?? '').'|'.substr((string) ($payload['trace'] ?? ''), 0, 200));
        if ($this->isDuplicate($fingerprint)) {
            return;
        }

        try {
            Http::withToken((string) config('errorhub.api_key'))
                ->acceptJson()
                ->asJson()
                ->connectTimeout(2)
                ->timeout(3)
                ->post((string) config('errorhub.api_url'), $payload);
        } catch (Throwable) {
            // Falha silenciosa — monitoramento não pode derrubar a aplicação.
        }
    }

    private function inferLevel(Throwable $exception): string
    {
        if ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() >= 500) {
            return 'critical';
        }

        if ($exception instanceof HttpExceptionInterface) {
            return 'error';
        }

        return 'critical';
    }

    private function resolveRequest(): ?Request
    {
        if (! app()->bound('request')) {
            return null;
        }

        $request = request();

        return $request instanceof Request ? $request : null;
    }

    /** @return array{0: ?string, 1: ?string} */
    private function resolveOrganizationContext(?Request $request): array
    {
        $orgId = OrganizationContext::id($request?->user());

        if ($orgId === null || $orgId === '') {
            return [null, null];
        }

        $organization = Organization::query()->find($orgId);

        return [(string) $orgId, $organization?->name];
    }

    /** @return array{id: string, name: string, email: string}|null */
    private function buildUserContext(?Request $request): ?array
    {
        $user = $request?->user();

        if (! $user instanceof User) {
            return null;
        }

        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }

    private function isDuplicate(string $fingerprint): bool
    {
        $now = time();
        $last = self::$recentFingerprints[$fingerprint] ?? null;

        if ($last !== null && ($now - $last) < 3) {
            return true;
        }

        self::$recentFingerprints[$fingerprint] = $now;

        return false;
    }

    private function truncate(string $value, int $max = 2000): string
    {
        if (strlen($value) <= $max) {
            return $value;
        }

        return substr($value, 0, $max);
    }
}
