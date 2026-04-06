<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Clinic;

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

    public function getClinicTheme(?Clinic $clinic): string
    {
        $theme = $clinic?->theme ?? self::DEFAULT_THEME;
        $theme = $this->resolveThemeKey($theme);

        return array_key_exists($theme, $this->getAvailableThemes())
            ? $theme
            : self::DEFAULT_THEME;
    }

    public function getBodyClasses(?Clinic $clinic): string
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
