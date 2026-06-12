<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlatformSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:128'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'grace_days' => ['required', 'integer', 'min:0', 'max:90'],
            'block_mode' => ['required', 'string', 'in:soft,hard'],
            'multi_empresa_plan' => ['required', 'string', 'max:64'],
            'asaas_base_url' => ['required', 'url', 'max:255'],
            'asaas_api_key' => ['nullable', 'string', 'max:512'],
            'asaas_webhook_secret' => ['nullable', 'string', 'max:512'],
            'minio_endpoint' => ['required', 'url', 'max:255'],
            'minio_access_key' => ['required', 'string', 'max:128'],
            'minio_secret_key' => ['nullable', 'string', 'max:512'],
            'minio_region' => ['required', 'string', 'max:64'],
            'minio_submissions_bucket' => ['required', 'string', 'max:128'],
            'minio_attachments_bucket' => ['required', 'string', 'max:128'],
            'minio_assets_bucket' => ['required', 'string', 'max:128'],
            'minio_invoices_bucket' => ['required', 'string', 'max:128'],
            'mail_mailer' => ['required', 'string', 'in:resend,log'],
            'resend_api_key' => ['nullable', 'string', 'max:512'],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'mail_from_name' => ['required', 'string', 'max:128'],
            'mail_support_email' => ['nullable', 'email', 'max:255'],
            'mail_logo_url' => ['nullable', 'url', 'max:512'],
            'mail_sender_name' => ['nullable', 'string', 'max:128'],
            'mail_sender_role' => ['nullable', 'string', 'max:128'],
            'mail_whatsapp_number' => ['nullable', 'string', 'max:32'],
            'mail_primary_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'mail_product_name' => ['nullable', 'string', 'max:128'],
        ];
    }

    public function attributes(): array
    {
        return [
            'product_name' => 'nome do produto',
            'trial_days' => 'trial (dias)',
            'grace_days' => 'grace (dias)',
            'block_mode' => 'modo de bloqueio',
            'multi_empresa_plan' => 'plano multi-empresa',
            'asaas_base_url' => 'URL da API Asaas',
            'asaas_api_key' => 'chave da API Asaas',
            'asaas_webhook_secret' => 'segredo do webhook Asaas',
            'minio_endpoint' => 'endpoint MinIO',
            'minio_access_key' => 'access key MinIO',
            'minio_secret_key' => 'secret key MinIO',
            'minio_region' => 'região MinIO',
            'minio_submissions_bucket' => 'bucket de submissões',
            'minio_attachments_bucket' => 'bucket de anexos',
            'minio_assets_bucket' => 'bucket de assets',
            'minio_invoices_bucket' => 'bucket de faturas',
            'mail_mailer' => 'driver de e-mail',
            'resend_api_key' => 'chave da API Resend',
            'mail_from_address' => 'remetente (e-mail)',
            'mail_from_name' => 'remetente (nome)',
            'mail_support_email' => 'e-mail de suporte',
            'mail_logo_url' => 'URL do logo nos e-mails',
            'mail_sender_name' => 'nome na assinatura do e-mail',
            'mail_sender_role' => 'cargo na assinatura do e-mail',
            'mail_whatsapp_number' => 'WhatsApp de contato',
            'mail_primary_color' => 'cor primária dos e-mails',
            'mail_product_name' => 'nome do produto nos e-mails',
        ];
    }
}
