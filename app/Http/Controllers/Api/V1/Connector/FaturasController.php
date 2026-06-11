<?php

namespace App\Http\Controllers\Api\V1\Connector;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Support\Connector\ConnectorMapper;
use App\Support\Connector\ConnectorPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaturasController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Payment::query()
            ->with(['organization', 'subscription'])
            ->orderByDesc('due_date');

        return ConnectorPaginator::respond(
            $query,
            $request,
            fn (Payment $payment) => ConnectorMapper::fatura($payment)
        );
    }

    public function show(string $externalId): JsonResponse
    {
        $id = ConnectorPaginator::parseExternalId($externalId, 'pay-');
        if ($id === null) {
            return ConnectorPaginator::notFound('Fatura');
        }

        $payment = Payment::query()->with(['organization', 'subscription'])->find($id);
        if (! $payment) {
            return ConnectorPaginator::notFound('Fatura');
        }

        return response()->json(ConnectorMapper::fatura($payment));
    }
}
