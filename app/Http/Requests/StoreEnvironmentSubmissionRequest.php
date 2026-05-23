<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnvironmentSubmissionRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'environment' => ['required', 'in:turibulo,altar'],
            'photo_path' => ['required', 'string', 'max:255'],
            'observation' => ['nullable', 'string'],
        ];
    }
}
