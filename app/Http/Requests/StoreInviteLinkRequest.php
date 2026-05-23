<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInviteLinkRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }
    public function rules(): array { return ['role' => ['required', 'in:membro,coordenador'], 'requires_approval' => ['required', 'boolean']]; }
}
