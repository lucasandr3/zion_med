<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmissionReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('approve-submission', $this->route('submissao')) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:approved,rejected'],
            'review_comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'status',
            'review_comment' => 'comentário',
        ];
    }
}
