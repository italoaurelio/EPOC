<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManualAttendanceUpdateRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:pendente,compareceu,nao_compareceu,substituido,nao_computado'],
        ];
    }
}
