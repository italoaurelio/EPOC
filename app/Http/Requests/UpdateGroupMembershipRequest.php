<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupMembershipRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:aprovado,rejeitado,pendente'],
            'role' => ['nullable', 'in:membro,coordenador'],
            'action' => ['required', 'in:approve,reject,role,remove'],
        ];
    }
}
