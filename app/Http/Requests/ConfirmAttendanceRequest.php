<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmAttendanceRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:compareceu,nao_compareceu,nao_computado,substituido'],
            'replacement_user_id' => ['nullable', 'exists:users,id'],
            'replacement_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
