<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }
    public function rules(): array
    {
        return [
            'event_function_slot_id' => ['required', 'exists:event_function_slots,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'ghost_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
