<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlatformSettingsRequest;
use App\Models\PlatformSetting;
use App\Services\AsaasConfigService;
use App\Services\MinioConfigService;
use App\Services\PlatformConfigService;
use App\Services\PlatformEmailBrandingService;
use App\Services\ResendConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(
        private readonly AsaasConfigService $asaasConfig,
        private readonly MinioConfigService $minioConfig,
        private readonly ResendConfigService $resendConfig,
        private readonly PlatformEmailBrandingService $emailBranding,
    ) {}

    public function index(): JsonResponse
    {
        $componentsRaw = PlatformSetting::get('service_status_components');
        $components = $componentsRaw ? json_decode($componentsRaw, true) : [];

        $data = [
            'product_name' => PlatformSetting::get('product_name', config('app.name')),
            'trial_days' => (int) PlatformSetting::get('trial_days', 14),
            'grace_days' => (int) PlatformSetting::get('grace_days', 7),
            'block_mode' => PlatformSetting::get('block_mode', 'soft'),
            'multi_empresa_plan' => PlatformSetting::get('multi_empresa_plan', 'enterprise'),
            'service_status' => PlatformSetting::get('service_status', 'operational'),
            'service_status_severity' => PlatformSetting::get('service_status_severity', 'none'),
            'service_status_message' => PlatformSetting::get('service_status_message', ''),
            'service_status_components' => is_array($components) ? $components : [],
            'component_options' => PlatformSetting::SERVICE_COMPONENTS,
            ...$this->asaasConfig->toSettingsPayload(),
            'minio' => $this->minioConfig->toSettingsPayload(),
            'resend' => $this->resendConfig->toSettingsPayload(),
        ];

        return response()->json(['data' => $data]);
    }

    public function update(PlatformSettingsRequest $request): JsonResponse
    {
        PlatformSetting::set('product_name', $request->input('product_name'));
        PlatformSetting::set('trial_days', $request->input('trial_days'));
        PlatformSetting::set('grace_days', $request->input('grace_days'));
        PlatformSetting::set('block_mode', $request->input('block_mode'));
        PlatformSetting::set('multi_empresa_plan', $request->input('multi_empresa_plan'));

        $this->asaasConfig->update([
            'base_url' => $request->input('asaas_base_url'),
            'api_key' => $request->input('asaas_api_key'),
            'webhook_secret' => $request->input('asaas_webhook_secret'),
        ]);

        $this->minioConfig->update([
            'endpoint' => $request->input('minio_endpoint'),
            'access_key' => $request->input('minio_access_key'),
            'secret_key' => $request->input('minio_secret_key'),
            'region' => $request->input('minio_region'),
            'submissions_bucket' => $request->input('minio_submissions_bucket'),
            'attachments_bucket' => $request->input('minio_attachments_bucket'),
            'assets_bucket' => $request->input('minio_assets_bucket'),
            'invoices_bucket' => $request->input('minio_invoices_bucket'),
        ]);

        $this->resendConfig->update([
            'mailer' => $request->input('mail_mailer'),
            'api_key' => $request->input('resend_api_key'),
            'from_address' => $request->input('mail_from_address'),
            'from_name' => $request->input('mail_from_name'),
            'support_email' => $request->input('mail_support_email'),
            'logo_url' => $request->input('mail_logo_url'),
            'sender_name' => $request->input('mail_sender_name'),
            'sender_role' => $request->input('mail_sender_role'),
            'whatsapp_number' => $request->input('mail_whatsapp_number'),
            'primary_color' => $request->input('mail_primary_color'),
            'product_name' => $request->input('mail_product_name'),
        ]);

        $this->asaasConfig->mergeIntoConfig();
        $this->minioConfig->applyFilesystemConfig();
        $this->resendConfig->mergeIntoConfig();

        return response()->json([
            'message' => 'Configurações salvas.',
            'data' => $this->buildResponseData($request),
        ]);
    }

    public function uploadEmailBranding(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:logo,signature'],
            'file' => ['required', 'file', 'image', 'max:2048', 'mimes:jpeg,jpg,png,webp,gif'],
        ], [
            'file.required' => 'Selecione uma imagem para enviar.',
            'file.image' => 'O arquivo deve ser uma imagem.',
            'file.max' => 'A imagem deve ter no máximo 2 MB.',
        ]);

        PlatformConfigService::mergeIntoConfig();

        $uploaded = $validated['type'] === 'logo'
            ? $this->emailBranding->uploadLogo($validated['file'])
            : $this->emailBranding->uploadSignaturePhoto($validated['file']);

        $this->resendConfig->mergeIntoConfig();

        return response()->json([
            'message' => $validated['type'] === 'logo' ? 'Logo enviado.' : 'Foto de assinatura enviada.',
            'data' => [
                'type' => $validated['type'],
                'path' => $uploaded['path'],
                'url' => $uploaded['url'],
                'resend' => $this->resendConfig->toSettingsPayload(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildResponseData(PlatformSettingsRequest $request): array
    {
        $componentsRaw = PlatformSetting::get('service_status_components');
        $components = $componentsRaw ? json_decode($componentsRaw, true) : [];

        return [
            'product_name' => $request->input('product_name'),
            'trial_days' => (int) $request->input('trial_days'),
            'grace_days' => (int) $request->input('grace_days'),
            'block_mode' => $request->input('block_mode'),
            'multi_empresa_plan' => $request->input('multi_empresa_plan'),
            'service_status' => PlatformSetting::get('service_status', 'operational'),
            'service_status_severity' => PlatformSetting::get('service_status_severity', 'none'),
            'service_status_message' => PlatformSetting::get('service_status_message', ''),
            'service_status_components' => is_array($components) ? $components : [],
            ...$this->asaasConfig->toSettingsPayload(),
            'minio' => $this->minioConfig->toSettingsPayload(),
            'resend' => $this->resendConfig->toSettingsPayload(),
        ];
    }
}
