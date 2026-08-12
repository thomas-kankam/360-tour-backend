<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clientId = $this->user()?->id;

        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('clients', 'email')->ignore($clientId)],
            'location' => ['nullable', 'string', 'max:255'],
            'profile_image' => ['nullable', 'string', 'starts_with:data:,http://,https://'],
        ];
    }
}
