<?php

declare(strict_types=1);

namespace App\Services\MobileTools;

use InvalidArgumentException;

class MobileToolPricingService
{
    /**
     * @return array{price: float, cost: float, fee: float, profit: float}
     */
    public function suggestPrice(
        float $materials,
        float $minutes,
        float $hourlyRate,
        float $feePercent,
        float $marginPercent,
    ): array {
        if ($materials < 0 || $minutes < 0 || $hourlyRate < 0 || $feePercent < 0 || $marginPercent < 0) {
            throw new InvalidArgumentException('Valores não podem ser negativos.');
        }

        $fee = $feePercent / 100;
        $margin = $marginPercent / 100;
        $base = $materials + ($minutes / 60) * $hourlyRate;

        if ($base <= 0) {
            throw new InvalidArgumentException('Informe um custo base maior que zero.');
        }
        if ($fee + $margin >= 1) {
            throw new InvalidArgumentException('A soma das taxas e da margem deve ser menor que 100%.');
        }

        $price = $base / (1 - $fee - $margin);

        return [
            'price' => round($price, 2),
            'cost' => round($base, 2),
            'fee' => round($price * $fee, 2),
            'profit' => round($price - $base - ($price * $fee), 2),
        ];
    }

    /**
     * @return array{total: float, sessions: int, message: string}
     */
    public function buildQuote(
        string $clientName,
        string $procedure,
        int $sessions,
        float $unitPrice,
        float $discount,
    ): array {
        if ($sessions < 1 || $unitPrice < 0 || $discount < 0) {
            throw new InvalidArgumentException('Confira sessões, valor e desconto.');
        }
        $gross = $sessions * $unitPrice;
        if ($discount > $gross) {
            throw new InvalidArgumentException('Desconto não pode ser maior que o total bruto.');
        }

        $total = round($gross - $discount, 2);
        $name = trim($clientName) !== '' ? trim($clientName) : 'Cliente';
        $proc = trim($procedure) !== '' ? trim($procedure) : 'Procedimento';

        $message = "Olá, {$name}!\n\n"
            ."Segue a proposta para {$proc}:\n"
            .'• '.$sessions." sessão(ões)\n"
            .'• Valor por sessão: '.$this->money($unitPrice)."\n"
            .'• Desconto: '.$this->money($discount)."\n\n"
            .'Total: '.$this->money($total)."\n\n"
            .'Se tiver dúvidas, estou à disposição!';

        return [
            'total' => $total,
            'sessions' => $sessions,
            'message' => $message,
        ];
    }

    public function money(float $value): string
    {
        return 'R$ '.number_format($value, 2, ',', '.');
    }

    /**
     * @return array{net: float, fee: float}
     */
    public function cardNet(float $amount, float $feePercent): array
    {
        if ($amount < 0 || $feePercent < 0 || $feePercent >= 100) {
            throw new InvalidArgumentException('Verifique o valor e a taxa.');
        }
        $fee = $amount * ($feePercent / 100);

        return [
            'net' => round($amount - $fee, 2),
            'fee' => round($fee, 2),
        ];
    }
}
