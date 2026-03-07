<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ProtocolGeneratorService
{
    /**
     * Gera o próximo número de protocolo para a organização no ano atual.
     * Formato: ZMD-AAAA-NNNNNN (ex: ZMD-2026-000001)
     */
    public function generate(int $organizationId): string
    {
        $year = (int) date('Y');

        $next = DB::transaction(function () use ($organizationId, $year) {
            $row = DB::table('protocol_sequences')
                ->where('organization_id', $organizationId)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($row) {
                $nextNumber = $row->last_number + 1;
                DB::table('protocol_sequences')
                    ->where('id', $row->id)
                    ->update(['last_number' => $nextNumber, 'updated_at' => now()]);

                return $nextNumber;
            }

            DB::table('protocol_sequences')->insert([
                'organization_id' => $organizationId,
                'year' => $year,
                'last_number' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return 1;
        });

        return 'ZMD-' . $year . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
