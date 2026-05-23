<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DecideEventCandidateRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }
    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:aprovar,rejeitar'],
            'candidate_id' => ['required', 'exists:event_candidates,id'],
        ];
    }
}
