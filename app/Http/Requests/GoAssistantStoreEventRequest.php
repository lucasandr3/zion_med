<?php

namespace App\Http\Requests;

use App\Models\GoAssistantEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GoAssistantStoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kind' => ['required', 'string', Rule::in(GoAssistantEvent::KINDS)],
            'screen_id' => ['nullable', 'string', 'max:120'],
            'intent_id' => ['nullable', 'string', 'max:120'],
            'route' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'query' => ['nullable', 'string', 'max:500'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
