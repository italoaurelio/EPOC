<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventFunctionRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'is_initially_active' => ['nullable', 'boolean'],
        ];
    }
}
