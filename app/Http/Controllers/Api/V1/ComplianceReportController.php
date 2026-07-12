<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesOrganizationContext;
use App\Http\Controllers\Controller;
use App\Services\ComplianceReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplianceReportController extends Controller
{
    use ResolvesOrganizationContext;

    public function __construct(
        private ComplianceReportService $complianceReportService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('view-dashboard');

        $orgId = (int) ($this->currentOrganizationId($request) ?? 0);
        if ($orgId <= 0) {
            return response()->json([
                'data' => [
                    'sem_clinica' => true,
                    'generated_at' => now()->toIso8601String(),
                    'summary' => [
                        'total_protocols' => 0,
                        'pending' => 0,
                        'consent_expired' => 0,
                        'without_snapshot' => 0,
                        'revoked' => 0,
                        'retention_anonymized' => 0,
                        'revocation_rate_percent' => 0,
                    ],
                    'by_status' => [],
                    'highlights' => [
                        'pending_today' => 0,
                        'expiring_next_30_days' => 0,
                    ],
                    'recent_issues' => [],
                ],
            ]);
        }

        $validated = $request->validate([
            'scope' => ['nullable', 'string', 'in:all,consent'],
            'recent_limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $consentOnly = ($validated['scope'] ?? 'consent') === 'consent';
        $recentLimit = (int) ($validated['recent_limit'] ?? 8);

        return response()->json([
            'data' => array_merge(
                ['sem_clinica' => false],
                $this->complianceReportService->build($orgId, $consentOnly, $recentLimit),
            ),
        ]);
    }
}
