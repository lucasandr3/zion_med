<?php

namespace App\Services;

use App\Models\Clinic;

class ThemeService
{
    public const DEFAULT_THEME = 'zion-blue';

    /**
     * Returns all available themes with their metadata and color palette.
     */
    public function getAvailableThemes(): array
    {
        return [
            'zion-blue'     => ['label' => 'Zion Blue',     'primary' => '#1e40af'],
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

    public function getClinicTheme(?Clinic $clinic): string
    {
        $theme = $clinic?->theme ?? self::DEFAULT_THEME;

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
        return $this->getAvailableThemes()[$theme]['primary']
            ?? $this->getAvailableThemes()[self::DEFAULT_THEME]['primary'];
    }
}
