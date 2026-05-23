<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubstitutionRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }
    public function rules(): array
    {
        return [
            'event_id' => ['required', 'exists:events,id'],
            'replaced_user_id' => ['required', 'exists:users,id'],
            'replacement_user_id' => ['nullable', 'exists:users,id'],
            'replacement_name' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string'],
        ];
    }
}
