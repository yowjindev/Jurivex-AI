<?php

namespace App\Modules\Superadmin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateInvitationCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role'       => ['required', 'string', 'in:admin,manager,staff'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
