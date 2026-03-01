<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmissionCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view-submission', $this->route('submissao')) ?? false;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'body' => 'comentário',
        ];
    }
}
