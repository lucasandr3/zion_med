<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeAppearanceController extends Controller
{
    /**
     * Atualiza tema da interface (SPA). Campos opcionais; apenas os enviados são persistidos.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ui_theme' => ['sometimes', 'nullable', 'string', 'max:64', 'regex:/^[a-z0-9-]+$/'],
            'ui_dark_mode' => ['sometimes', 'nullable', 'boolean'],
            'ui_shell_preset' => ['sometimes', 'nullable', 'string', 'max:32', 'regex:/^(tinted|sidebar_dark)$/'],
        ]);

        $user = $request->user();

        if (array_key_exists('ui_theme', $validated)) {
            $user->ui_theme = $validated['ui_theme'];
        }
        if (array_key_exists('ui_dark_mode', $validated)) {
            $user->ui_dark_mode = $validated['ui_dark_mode'];
        }
        if (array_key_exists('ui_shell_preset', $validated)) {
            $v = $validated['ui_shell_preset'];
            $user->ui_shell_preset = ($v === null || $v === '') ? null : $v;
        }

        $user->save();

        return response()->json([
            'data' => [
                'user' => new UserResource($user->fresh()),
            ],
        ]);
    }
}
