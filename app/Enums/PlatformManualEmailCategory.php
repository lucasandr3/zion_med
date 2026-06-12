<?php

namespace App\Enums;

enum PlatformManualEmailCategory: string
{
    case Contact = 'contact';
    case Billing = 'billing';
    case General = 'general';
    case Support = 'support';

    public function label(): string
    {
        return match ($this) {
            self::Contact => 'Contato',
            self::Billing => 'Cobrança',
            self::General => 'Geral',
            self::Support => 'Suporte',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }
}
