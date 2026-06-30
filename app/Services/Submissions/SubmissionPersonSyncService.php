<?php

namespace App\Services\Submissions;

use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\Person;
use App\Support\PersonPiiHasher;
use Illuminate\Support\Str;

class SubmissionPersonSyncService
{
    /**
     * @return array<string, string>
     */
    public function submissionValuesAsFlatData(FormSubmission $submission): array
    {
        $data = [];
        foreach ($submission->values as $v) {
            if ($v->value_text !== null && trim((string) $v->value_text) !== '') {
                $data[$v->key] = trim((string) $v->value_text);

                continue;
            }
            if ($v->value_json !== null && ! is_array($v->value_json)) {
                $s = trim((string) $v->value_json);
                if ($s !== '') {
                    $data[$v->key] = $s;
                }
            }
        }

        return $data;
    }

    public function syncPersonFromApprovedSubmission(FormSubmission $submission): void
    {
        if (! $submission->person_id) {
            return;
        }

        $orgId = $submission->organization_id ?? $submission->clinic_id;
        if (! $orgId) {
            return;
        }

        $person = Person::withoutGlobalScopes()
            ->where('organization_id', $orgId)
            ->find($submission->person_id);

        if (! $person) {
            return;
        }

        $submission->loadMissing(['template.fields', 'values']);

        if (! $submission->template) {
            return;
        }

        $flat = $this->submissionValuesAsFlatData($submission);
        $fields = $this->extractPersonFieldsFromSubmission($submission->template, $flat, $submission);

        $nome = trim((string) ($fields['name'] ?? ''));
        $cpfNorm = $this->normalizeCpf($fields['cpf'] ?? null);
        $email = $this->normalizeEmail($fields['email'] ?? null);
        $phone = $this->normalizePhone($fields['phone'] ?? null);
        $birth = $this->normalizeBirthDate($fields['birth_date'] ?? null);

        $updates = [];
        if ($nome !== '') {
            $updates['name'] = $nome;
        }
        if ($phone) {
            $updates['phone'] = $phone;
        }
        if ($email) {
            $updates['email'] = $email;
        }
        if ($cpfNorm) {
            $updates['cpf'] = $cpfNorm;
        }
        if ($birth) {
            $updates['birth_date'] = $birth;
        }

        if ($updates === []) {
            return;
        }

        $person->fill($updates)->save();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function ensurePersonFromPublicForm(FormSubmission $submission, FormTemplate $template, array $data): ?Person
    {
        $orgId = $submission->organization_id ?? $submission->clinic_id;
        if (! $orgId) {
            return null;
        }

        $fields = $this->extractPersonFieldsFromSubmission($template, $data, $submission);
        $nome = trim((string) ($fields['name'] ?? ''));
        if ($nome === '') {
            return null;
        }

        $cpfNorm = $this->normalizeCpf($fields['cpf'] ?? null);
        $email = $this->normalizeEmail($fields['email'] ?? null);
        $phone = $this->normalizePhone($fields['phone'] ?? null);
        $birth = $this->normalizeBirthDate($fields['birth_date'] ?? null);

        $query = Person::withoutGlobalScopes()->where('organization_id', $orgId);

        $match = null;
        if ($cpfNorm) {
            $match = (clone $query)->where('cpf_hash', PersonPiiHasher::cpf($cpfNorm))->first();
        }
        if (! $match && $email) {
            $match = (clone $query)->where('email_hash', PersonPiiHasher::email($email))->first();
        }
        if (! $match && $birth) {
            $match = (clone $query)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($nome)])
                ->whereDate('birth_date', $birth)
                ->first();
        }

        if ($match) {
            $updates = array_filter([
                'phone' => $match->phone ?: $phone,
                'email' => $match->email ?: $email,
                'cpf' => $match->cpf ?: $cpfNorm,
                'birth_date' => $match->birth_date ?: $birth,
            ], fn ($v) => $v !== null && $v !== '');
            if ($updates) {
                $match->fill($updates)->save();
            }

            return $match;
        }

        $person = Person::withoutGlobalScopes()->create([
            'organization_id' => $orgId,
            'code' => '_tmp_'.bin2hex(random_bytes(8)),
            'name' => $nome,
            'phone' => $phone,
            'email' => $email,
            'birth_date' => $birth,
            'cpf' => $cpfNorm,
            'status' => 'active',
        ]);
        $person->update([
            'code' => 'P-'.str_pad((string) $person->id, 6, '0', STR_PAD_LEFT),
        ]);

        return $person->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{name: ?string, cpf: ?string, email: ?string, phone: ?string, birth_date: ?string}
     */
    public function extractPersonFieldsFromSubmission(FormTemplate $template, array $data, FormSubmission $submission): array
    {
        $groups = [
            'name' => ['nome', 'nome_completo', 'name', 'fullname', 'full_name', 'paciente', 'cliente'],
            'cpf' => ['cpf', 'documento', 'doc'],
            'email' => ['email', 'e_mail', 'e-mail', 'correio_eletronico'],
            'phone' => ['telefone', 'celular', 'whatsapp', 'wa', 'phone', 'mobile', 'contato_telefone'],
            'birth_date' => ['data_nascimento', 'dt_nascimento', 'nascimento', 'birth_date', 'birthdate', 'data_de_nascimento'],
        ];

        $findValueByKey = function (string $needle) use ($data): ?string {
            $needleNorm = strtolower($needle);
            foreach ($data as $k => $v) {
                if (! is_string($k)) {
                    continue;
                }
                if (strtolower($k) === $needleNorm && is_scalar($v) && trim((string) $v) !== '') {
                    return (string) $v;
                }
            }

            return null;
        };

        $result = ['name' => null, 'cpf' => null, 'email' => null, 'phone' => null, 'birth_date' => null];

        foreach ($template->fields as $field) {
            $keyNorm = strtolower((string) $field->name_key);
            foreach ($groups as $target => $candidates) {
                if ($result[$target] !== null) {
                    continue;
                }
                foreach ($candidates as $cand) {
                    if ($keyNorm === $cand || str_contains($keyNorm, $cand)) {
                        $val = $findValueByKey($field->name_key);
                        if ($val !== null) {
                            $result[$target] = $val;
                            break 2;
                        }
                    }
                }
            }
        }

        if (! $result['name'] && ! empty($submission->submitter_name)) {
            $result['name'] = $submission->submitter_name;
        }
        if (! $result['email'] && ! empty($submission->submitter_email)) {
            $result['email'] = $submission->submitter_email;
        }

        return $result;
    }

    protected function normalizeCpf(?string $raw): ?string
    {
        if (! $raw) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        return strlen($digits) === 11 ? $digits : null;
    }

    protected function normalizeEmail(?string $raw): ?string
    {
        if (! $raw) {
            return null;
        }
        $email = strtolower(trim($raw));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    protected function normalizePhone(?string $raw): ?string
    {
        if (! $raw) {
            return null;
        }
        $s = trim($raw);

        return $s !== '' ? Str::limit($s, 50, '') : null;
    }

    protected function normalizeBirthDate(?string $raw): ?string
    {
        if (! $raw) {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
