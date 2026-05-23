<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:missa,reuniao'],
            'name' => ['required', 'string'],
            'event_date' => ['required', 'date'],
            'event_time' => ['required'],
            'notes' => ['nullable', 'string'],
            'audience' => ['nullable', 'in:all,specific'],
            'invitee_user_ids' => ['nullable', 'array'],
            'invitee_user_ids.*' => ['integer', 'exists:users,id'],
            'location' => ['nullable', 'array'],
            'location.name' => ['required_with:location', 'string', 'max:255'],
            'location.street' => ['nullable', 'string', 'max:255'],
            'location.number' => ['nullable', 'string', 'max:30'],
            'location.district' => ['nullable', 'string', 'max:255'],
            'location.city' => ['nullable', 'string', 'max:255'],
            'location.state' => ['nullable', 'string', 'max:255'],
            'location.complement' => ['nullable', 'string', 'max:255'],
            'liturgical_color' => ['nullable', 'in:branco,vermelho,verde,roxo,rosa,preto'],
            'function_ids' => ['nullable', 'array'],
            'function_ids.*' => ['integer', 'exists:event_functions,id'],
            'slot_assignments' => ['nullable', 'array'],
            'slot_assignments.*.mode' => ['nullable', 'in:vacancy,member,ghost'],
            'slot_assignments.*.user_id' => ['nullable', 'integer', 'exists:users,id'],
            'slot_assignments.*.ghost_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
