<?php

namespace App\Services\Submissions;

use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\FormTemplateVersion;
use App\Models\SubmissionSignature;
use App\Services\SignatureFieldResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubmissionSignatureService
{
    public function __construct(
        private SignatureFieldResolver $signatureFieldResolver,
    ) {}

    /**
     * @param  array<string, string>  $signatures
     */
    public function persistForSubmission(
        FormSubmission $submission,
        FormTemplateVersion $templateVersion,
        array $signatures,
        array $data,
        string $documentHash,
        string $signingChannel,
        string $locale,
        string $timezone,
        \Carbon\CarbonInterface $acceptedTextAt,
        ?Request $request = null,
        ?FormTemplate $template = null,
    ): void {
        $orgId = $submission->organization_id ?? $submission->clinic_id;
        $template ??= $submission->relationLoaded('template')
            ? $submission->template
            : $submission->load('template')->template;

        foreach ($signatures as $fieldKey => $signatureBase64) {
            if (! is_string($signatureBase64) || $signatureBase64 === '') {
                continue;
            }

            $imagePath = $this->storeImage((int) $orgId, $submission->id, $signatureBase64);
            $signedAt = now();
            $isProfessionalCosign = ! empty($data['_assisted_mode'])
                && $template instanceof FormTemplate
                && $this->signatureFieldResolver->isProfessionalCosignField((string) $fieldKey, $template);

            if ($isProfessionalCosign) {
                $signedName = trim((string) ($data['_professional_name'] ?? ''));
                $channel = 'assisted_cosign';
            } else {
                $signedName = $data['_submitter_name'] ?? null;
                $channel = $signingChannel;
            }

            $evidencePayload = implode('|', [
                (string) $submission->id,
                $fieldKey,
                $signedAt->toIso8601String(),
                (string) $signedName,
            ]);
            $signatureHash = hash('sha256', $evidencePayload);
            $evidencePackage = [
                'submission_id' => $submission->id,
                'field_key' => $fieldKey,
                'template_version_id' => $templateVersion->id,
                'signed_name' => $signedName,
                'signed_ip' => $request?->ip(),
                'signed_user_agent' => $request ? Str::limit($request->userAgent(), 512) : null,
                'signed_at' => $signedAt->toIso8601String(),
                'signed_hash' => $signatureHash,
                'document_hash' => $documentHash,
                'channel' => $channel,
                'locale' => $locale,
                'timezone' => $timezone,
                'accepted_text_at' => $acceptedTextAt->toIso8601String(),
            ];
            $evidenceHash = hash('sha256', json_encode($evidencePackage));

            SubmissionSignature::create([
                'submission_id' => $submission->id,
                'form_template_version_id' => $templateVersion->id,
                'image_path' => $imagePath,
                'field_key' => $fieldKey,
                'document_hash' => $documentHash,
                'evidence_hash' => $evidenceHash,
                'channel' => $channel,
                'status' => 'completed',
                'accepted_text_at' => $acceptedTextAt,
                'locale' => $locale,
                'timezone' => $timezone,
                'signed_name' => $signedName !== '' ? $signedName : null,
                'signed_ip' => $request?->ip(),
                'signed_user_agent' => $request ? Str::limit($request->userAgent(), 512) : null,
                'signed_hash' => $signatureHash,
                'signed_at' => $signedAt,
            ]);
        }
    }

    public function storeImage(int $organizationId, int $submissionId, string $base64): string
    {
        $data = preg_replace('#^data:image/\w+;base64,#i', '', $base64);
        $decoded = base64_decode($data, true);
        if ($decoded === false) {
            throw ValidationException::withMessages(['signature' => ['Assinatura inválida.']]);
        }
        $dir = 'organizations/'.$organizationId.'/signatures/'.$submissionId;
        $filename = Str::random(10).'.png';
        $path = $dir.'/'.$filename;
        \Illuminate\Support\Facades\Storage::disk('minio_submissions')->put($path, $decoded);

        return $path;
    }
}
