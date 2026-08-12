<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:20', Rule::unique('clients', 'phone_number')],
            'email' => ['required', 'email', 'max:255', Rule::unique('clients', 'email')],
            'location' => ['nullable', 'string', 'max:255'],
            'profile_image'   => 'nullable|starts_with:data:,http://,https://',
        ];
    }
}
