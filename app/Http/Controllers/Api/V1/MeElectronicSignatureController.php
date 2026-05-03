<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MeElectronicSignatureController extends Controller
{
    /**
     * Atualiza a assinatura eletrônica persistida no armazenamento (PNG).
     *
     * Corpo JSON: `{ "image_base64": "data:image/png;base64,..." }` ou `{ "clear": true }`.
     */
    public function update(Request $request): JsonResponse
    {
        $clear = filter_var($request->input('clear'), FILTER_VALIDATE_BOOLEAN);
        $user = $request->user();

        if ($clear) {
            $this->deleteStored($user->electronic_signature_path ?? null);
            $user->electronic_signature_path = null;
            $user->electronic_signature_updated_at = null;
            $user->save();

            return response()->json([
                'data' => [
                    'user' => new UserResource($user->fresh()),
                ],
            ]);
        }

        $validated = $request->validate([
            'image_base64' => ['required', 'string', 'max:900000'],
        ]);

        $base64 = (string) $validated['image_base64'];
        $data = preg_replace('#^data:image/\w+;base64,#i', '', $base64);
        $decoded = base64_decode($data, true);
        if ($decoded === false || strlen($decoded) > 512000) {
            throw ValidationException::withMessages([
                'image_base64' => ['Imagem da assinatura inválida ou muito grande.'],
            ]);
        }

        if (@getimagesizefromstring($decoded) === false) {
            throw ValidationException::withMessages([
                'image_base64' => ['Envie uma imagem PNG ou JPEG válida.'],
            ]);
        }

        $this->deleteStored($user->electronic_signature_path ?? null);

        $path = 'users/' . $user->id . '/electronic-signature-' . uniqid('', true) . '.png';
        Storage::disk('minio_submissions')->put($path, $decoded);
        $user->electronic_signature_path = $path;
        $user->electronic_signature_updated_at = now();
        $user->save();

        return response()->json([
            'data' => [
                'user' => new UserResource($user->fresh()),
            ],
        ]);
    }

    private function deleteStored(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        try {
            if (Storage::disk('minio_submissions')->exists($path)) {
                Storage::disk('minio_submissions')->delete($path);
            }
        } catch (\Throwable) {
            // disco indisponível — segue fluxo sem bloquear
        }
    }
}
