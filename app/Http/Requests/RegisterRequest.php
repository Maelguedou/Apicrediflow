<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
   public function rules()
   {
        return [
            'company_name' => 'required|string|max:255',
            'pseudo' => 'required|string|max:255',
            'phone' => 'required|string|regex:/^\+22901[0-9]{8,15}$/|unique:users,phone',
            'password' => 'required|string|min:8|confirmed',
            'activity_type' => 'required|string|max:50',
            'location' => 'required|string|max:100',
            'registry' => 'nullable|file|mimes:pdf|max:5120',
            'identity' => 'required|file|mimes:pdf|max:2048',
            'email'=>'required|string|email|unique:users,email'
        ];
   }
   public function messages()
    {
        return [
            'phone.regex' => 'Number must come from Benin.',
        ];
    }
}
