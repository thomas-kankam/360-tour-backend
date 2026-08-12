<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'emailOrPhone' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', Rule::in(['login', 'registration'])],
        ];
    }
}
