<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;

class ThemeService
{
    public const DEFAULT_THEME = 'gestgo-blue';

    /**
     * Chaves antigas aceitas na API/UI; persistência deve usar {@see normalizeThemeValue()}.
     *
     * @var array<string, string>
     */
    public const LEGACY_THEME_ALIASES = [
        'zion-blue' => 'gestgo-blue',
    ];

    /**
     * Returns all available themes with their metadata and color palette.
     */
    public function getAvailableThemes(): array
    {
        return [
            'gestgo-blue'   => ['label' => 'Azul Gestgo',   'primary' => '#1e40af'],
            'ocean-blue'    => ['label' => 'Ocean Blue',    'primary' => '#2563eb'],
            'indigo-night'  => ['label' => 'Indigo Night',  'primary' => '#4f46e5'],
            'emerald-fresh' => ['label' => 'Emerald Fresh', 'primary' => '#10b981'],
            'rose-elegant'  => ['label' => 'Rose Elegant',  'primary' => '#f43f5e'],
            'amber-warm'    => ['label' => 'Amber Warm',    'primary' => '#f59e0b'],
            'violet-dream'  => ['label' => 'Violet Dream',  'primary' => '#8b5cf6'],
            'teal-ocean'    => ['label' => 'Teal Ocean',    'primary' => '#14b8a6'],
            'slate-pro'     => ['label' => 'Slate Pro',     'primary' => '#475569'],
            'cyan-tech'     => ['label' => 'Cyan Tech',     'primary' => '#06b6d4'],
            'fuchsia-bold'  => ['label' => 'Fuchsia Bold',  'primary' => '#d946ef'],
        ];
    }

    /**
     * Chaves aceitas em validação (inclui aliases legados como {@see LEGACY_THEME_ALIASES}).
     *
     * @return list<string>
     */
    public function themeKeysForValidation(): array
    {
        return array_merge(
            array_keys($this->getAvailableThemes()),
            array_keys(self::LEGACY_THEME_ALIASES),
        );
    }

    /**
     * Temas extras que só existem na página pública do Link Bio (não aparecem na configuração principal
     * da clínica). Útil para dar ao cliente opções visuais (preto sólido, cor livre) sem poluir o seletor
     * do sistema.
     *
     * @return array<string, array{label: string, primary: string}>
     */
    public function getPublicOnlyThemes(): array
    {
        return [
            'onyx-black' => ['label' => 'Onyx Black',   'primary' => '#1a1410'],
            'custom'     => ['label' => 'Personalizada', 'primary' => '#c9a84c'],
        ];
    }

    /**
     * Chaves aceitas em validação para o tema público do Link Bio (inclui presets da empresa,
     * aliases legados e os temas "só públicos" como `onyx-black` e `custom`).
     *
     * @return list<string>
     */
    public function publicThemeKeysForValidation(): array
    {
        return array_merge(
            $this->themeKeysForValidation(),
            array_keys($this->getPublicOnlyThemes()),
        );
    }

    /**
     * Normaliza um valor de tema público, preservando `onyx-black`/`custom`. Chaves desconhecidas
     * caem para {@see DEFAULT_THEME}.
     */
    public function normalizePublicThemeValue(?string $theme): ?string
    {
        if ($theme === null || $theme === '') {
            return $theme;
        }

        $resolved = $this->resolveThemeKey($theme);

        if (array_key_exists($resolved, $this->getAvailableThemes())) {
            return $resolved;
        }
        if (array_key_exists($resolved, $this->getPublicOnlyThemes())) {
            return $resolved;
        }

        return self::DEFAULT_THEME;
    }

    /**
     * Cor primária resolvida para uso na página pública. Quando o tema é `custom`, utiliza o `$customHex`
     * salvo na clínica; para `onyx-black` devolve a cor do onyx; demais temas caem nos presets padrão.
     */
    public function getPublicAccentHex(?string $theme, ?string $customHex = null): ?string
    {
        if ($theme === null || $theme === '') {
            return null;
        }

        $resolved = $this->resolveThemeKey($theme);

        if ($resolved === 'custom') {
            $hex = is_string($customHex) ? trim($customHex) : '';
            if (preg_match('/^#[0-9a-fA-F]{6}$/', $hex) === 1) {
                return strtolower($hex);
            }
            return $this->getPublicOnlyThemes()['custom']['primary'];
        }

        $publicOnly = $this->getPublicOnlyThemes();
        if (isset($publicOnly[$resolved])) {
            return $publicOnly[$resolved]['primary'];
        }

        $available = $this->getAvailableThemes();
        return $available[$resolved]['primary'] ?? null;
    }

    public function resolveThemeKey(string $theme): string
    {
        return self::LEGACY_THEME_ALIASES[$theme] ?? $theme;
    }

    /**
     * Converte valor persistido ou enviado pela API para a chave canônica (ex.: zion-blue → gestgo-blue).
     */
    public function normalizeThemeValue(?string $theme): ?string
    {
        if ($theme === null || $theme === '') {
            return $theme;
        }

        $resolved = $this->resolveThemeKey($theme);

        return array_key_exists($resolved, $this->getAvailableThemes())
            ? $resolved
            : self::DEFAULT_THEME;
    }

    public function getClinicTheme(?Organization $clinic): string
    {
        $theme = $clinic?->theme ?? self::DEFAULT_THEME;
        $theme = $this->resolveThemeKey($theme);

        return array_key_exists($theme, $this->getAvailableThemes())
            ? $theme
            : self::DEFAULT_THEME;
    }

    public function getBodyClasses(?Organization $clinic): string
    {
        $theme   = $this->getClinicTheme($clinic);
        $classes = "theme-{$theme}";

        if ($clinic?->dark_mode ?? false) {
            $classes .= ' dark';
        }

        return $classes;
    }

    public function getThemeColor(string $theme): string
    {
        $theme = $this->resolveThemeKey($theme);

        return $this->getAvailableThemes()[$theme]['primary']
            ?? $this->getAvailableThemes()[self::DEFAULT_THEME]['primary'];
    }
}
