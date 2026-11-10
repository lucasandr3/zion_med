<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Models\ProductFeedback;
use Illuminate\Http\JsonResponse;

class FeedbackController extends Controller
{
    public function index(): JsonResponse
    {
        $items = ProductFeedback::query()
            ->with(['user:id,name,email', 'organization:id,name'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $items->map(fn (ProductFeedback $f) => [
                'id' => $f->id,
                'type' => $f->type,
                'title' => $f->title,
                'message' => $f->message,
                'source' => $f->source,
                'page_url' => $f->page_url,
                'user_name' => $f->user?->name,
                'user_email' => $f->user?->email,
                'organization_name' => $f->organization?->name,
                'created_at' => $f->created_at?->toIso8601String(),
            ]),
        ]);
    }
}
