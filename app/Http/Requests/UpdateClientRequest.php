<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $clientId = $this->route('client') ?? $this->route('id');
        
        return [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'birth_date' => 'sometimes|required|date|before:today',
            'phone' => 'sometimes|required|string|regex:/^[0-9+\-\s()]+$/|max:20',
            'phone2' => 'nullable|string|regex:/^[0-9+\-\s()]+$/|max:20',
            'address' => 'sometimes|required|string|max:500',
            'passport_serial' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('clients', 'passport_serial')->ignore($clientId)
            ],
            'passport_number' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('clients', 'passport_number')->ignore($clientId)
            ],
            'passport_given_date' => 'sometimes|required|date|before_or_equal:today',
            'passport_given_by' => 'sometimes|required|string|max:255',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'file_passport' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'comment' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required',
            'last_name.required' => 'Last name is required',
            'birth_date.required' => 'Birth date is required',
            'birth_date.before' => 'Birth date must be before today',
            'phone.required' => 'Phone number is required',
            'phone.regex' => 'Phone number format is invalid',
            'phone2.regex' => 'Second phone number format is invalid',
            'address.required' => 'Address is required',
            'passport_serial.required' => 'Passport serial is required',
            'passport_serial.unique' => 'This passport serial already exists',
            'passport_number.required' => 'Passport number is required',
            'passport_number.unique' => 'This passport number already exists',
            'passport_given_date.required' => 'Passport given date is required',
            'passport_given_date.before_or_equal' => 'Passport given date cannot be in the future',
            'passport_given_by.required' => 'Passport given by field is required',
            'file.mimes' => 'File must be a valid image or PDF',
            'file.max' => 'File size cannot exceed 2MB',
            'file_passport.mimes' => 'Passport file must be a valid image or PDF',
            'file_passport.max' => 'Passport file size cannot exceed 2MB',
        ];
    }
}