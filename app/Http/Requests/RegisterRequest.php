<?php

namespace App\Http\Requests;

use App\Rules\PasswordRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users', Rule::unique('users', 'email')->whereNotNull('deleted_at')],
            'phone' => ['required', 'string', 'max:15', 'unique:users', Rule::unique('users', 'phone')->whereNotNull('deleted_at')],
            'password' => PasswordRule::required(),
        ];
    }
}
