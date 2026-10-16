<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStoryRequest extends FormRequest
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
            'captions' => ['required_without:media', 'string', 'max:255'],
            'media' => ['required_without:captions', 'mimetypes:image/jpg,image/jpeg,image/png,image/gif,video/mp4,video/mov,video/flv,video/wmp', 'max:15000'],
        ];
    }
}
