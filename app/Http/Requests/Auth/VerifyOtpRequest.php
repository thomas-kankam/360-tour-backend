<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'emailOrPhone' => ['required', 'string', 'max:255'],
            'otp' => ['required', 'digits:6'],
            'type' => ['nullable', 'string', 'in:login,registration'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('otp')) {
            $this->merge([
                'otp' => str_pad((string) $this->input('otp'), 6, '0', STR_PAD_LEFT),
            ]);
        }

        if ($this->has('emailOrPhone')) {
            $this->merge([
                'emailOrPhone' => trim((string) $this->input('emailOrPhone')),
            ]);
        }
    }
}
