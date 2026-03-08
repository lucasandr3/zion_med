<?php

namespace App\Http\Requests;

use App\Services\ThemeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClinicSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-clinic') ?? false;
    }

    public function rules(): array
    {
        $validThemes = array_keys(app(ThemeService::class)->getAvailableThemes());

        // Dark-mode-only AJAX request
        if ($this->boolean('dark_mode_only')) {
            return [
                'dark_mode'      => ['required', 'boolean'],
                'dark_mode_only' => ['required'],
            ];
        }

        // Theme-only AJAX request
        if ($this->boolean('theme_only')) {
            return [
                'theme'      => ['required', 'string', Rule::in($validThemes)],
                'theme_only' => ['required'],
            ];
        }

        return [
            'name'               => ['required', 'string', 'max:255'],
            'notification_email' => ['nullable', 'email', 'max:255'],
            'address'            => ['nullable', 'string', 'max:500'],
            'phone'              => ['nullable', 'string', 'max:30'],
            'contact_email'      => ['nullable', 'email', 'max:255'],
            'short_description'  => ['nullable', 'string', 'max:200'],
            'specialties'        => ['nullable', 'string', 'max:500'],
            'founded_year'       => ['nullable', 'integer', 'min:1900', 'max:' . date('Y')],
            'meta_description'   => ['nullable', 'string', 'max:300'],
            'maps_url'           => ['nullable', 'url', 'max:500'],
            'business_hours'     => ['nullable', 'array'],
            'business_hours.*'   => ['nullable', 'array'],
            'business_hours.*.open'  => ['nullable', 'string', 'max:5'],
            'business_hours.*.close' => ['nullable', 'string', 'max:5'],
            'logo'               => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'cover_image'        => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:3072'],
            'theme'              => ['nullable', 'string', Rule::in($validThemes)],
            'public_theme'       => ['nullable', 'string', Rule::in(array_merge([''], $validThemes))],
            'cover_color'        => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'dark_mode'          => ['nullable', 'boolean'],
            'billing_name'       => ['nullable', 'string', 'max:255'],
            'billing_email'      => ['nullable', 'email', 'max:255'],
            'billing_document'   => ['nullable', 'string', 'max:25', 'regex:/^[\d\.\-\/\s]+$/'],
            'whatsapp_notifications_enabled' => ['nullable', 'boolean'],
            'whatsapp_notify_cobranca'       => ['nullable', 'boolean'],
            'whatsapp_notify_faturas_boleto' => ['nullable', 'boolean'],
            'whatsapp_notify_avisos'         => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'               => 'nome',
            'notification_email' => 'e-mail de notificação',
            'address'            => 'endereço',
            'logo'               => 'logo',
            'theme'              => 'tema',
            'dark_mode'          => 'modo escuro',
            'billing_document'   => 'CPF/CNPJ',
        ];
    }

    public function messages(): array
    {
        return [
            'billing_document.regex' => 'O CPF/CNPJ deve conter apenas números e pontuação (pontos, traços, barra).',
        ];
    }
}
