<?php

declare(strict_types=1);

namespace App\Support;

use Database\Seeders\Definitions\EsteticaStaffFieldsPack;

/**
 * Campos preenchidos apenas pela equipe no protocolo (não no formulário público).
 */
final class EsteticaStaffFieldRegistry
{
    /**
     * Definições para exibir/editar no detalhe do protocolo e no PDF.
     *
     * @return array<int, array{type: string, label: string, name_key: string, required?: bool, options?: array<int, string>, sort_order: int}>
     */
    public static function definitions(?string $templateName): array
    {
        if ($templateName === null || $templateName === '') {
            return [];
        }

        return EsteticaStaffFieldsPack::fieldsForTemplateName($templateName);
    }

    /**
     * @return array<int, string>
     */
    public static function allowedKeys(?string $templateName): array
    {
        $defs = self::definitions($templateName);

        return array_values(array_map(fn (array $f) => $f['name_key'], $defs));
    }
}
