<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

trait ValidationTrait
{
    /**
     * Validate request data with custom rules.
     */
    protected function validateRequest(Request $request, array $rules, array $messages = []): array
    {
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate data array with custom rules.
     */
    protected function validateData(array $data, array $rules, array $messages = []): array
    {
        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Get common client validation rules.
     */
    protected function getClientValidationRules(bool $isUpdate = false): array
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'birth_date' => 'required|date|before:today',
            'phone_number' => 'required|string|regex:/^[0-9+\-\s()]+$/|max:20',
            'additional_phone' => 'nullable|string|regex:/^[0-9+\-\s()]+$/|max:20',
            'address' => 'required|string|max:500',
            'passport_series' => 'required|string|max:10',
            'passport_number' => 'required|string|max:20',
            'passport_issued_by' => 'required|string|max:255',
            'passport_issued_date' => 'required|date|before_or_equal:today',
            'passport_expiry_date' => 'required|date|after:passport_issued_date',
            'workplace' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'monthly_income' => 'nullable|numeric|min:0',
            'file' => 'nullable|file|mimes:jpeg,png,pdf|max:2048',
            'file_passport' => 'nullable|file|mimes:jpeg,png,pdf|max:2048',
        ];

        if ($isUpdate) {
            // Make some fields optional for updates
            $rules['first_name'] = 'sometimes|required|string|max:255';
            $rules['last_name'] = 'sometimes|required|string|max:255';
            $rules['birth_date'] = 'sometimes|required|date|before:today';
            $rules['phone_number'] = 'sometimes|required|string|regex:/^[0-9+\-\s()]+$/|max:20';
            $rules['address'] = 'sometimes|required|string|max:500';
            $rules['passport_series'] = 'sometimes|required|string|max:10';
            $rules['passport_number'] = 'sometimes|required|string|max:20';
            $rules['passport_issued_by'] = 'sometimes|required|string|max:255';
            $rules['passport_issued_date'] = 'sometimes|required|date|before_or_equal:today';
            $rules['passport_expiry_date'] = 'sometimes|required|date|after:passport_issued_date';
        }

        return $rules;
    }

    /**
     * Get common order validation rules.
     */
    protected function getOrderValidationRules(): array
    {
        return [
            'client_id' => 'required|integer|exists:clients,id',
            'order_type' => 'required|in:device,accessory',
            'device_id' => 'required_if:order_type,device|integer|exists:devices,id',
            'accessory_id' => 'required_if:order_type,accessory|integer|exists:accessories,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'prepayment' => 'required|numeric|min:0',
            'month' => 'required|integer|min:1|max:36',
            'monthly_payment' => 'required|numeric|min:0',
            'status' => 'nullable|in:pending,approved,rejected,completed,cancelled',
        ];
    }

    /**
     * Get common device validation rules.
     */
    protected function getDeviceValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'specifications' => 'nullable|string|max:2000',
            'category' => 'nullable|string|max:255',
            'stock_quantity' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get common accessory validation rules.
     */
    protected function getAccessoryValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:255',
            'stock_quantity' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get common investment validation rules.
     */
    protected function getInvestmentValidationRules(): array
    {
        return [
            'investor_id' => 'required|integer|exists:investors,id',
            'amount' => 'required|numeric|min:0',
            'investment_date' => 'required|date',
            'expected_return_rate' => 'required|numeric|min:0|max:100',
            'duration_months' => 'required|integer|min:1',
            'status' => 'required|in:active,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get common employee validation rules.
     */
    protected function getEmployeeValidationRules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'phone' => 'required|string|max:20',
            'position' => 'required|string|max:255',
            'salary' => 'nullable|numeric|min:0',
            'hire_date' => 'required|date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get common payment validation rules.
     */
    protected function getPaymentValidationRules(): array
    {
        return [
            'order_id' => 'required|integer|exists:orders,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,card,bank_transfer,online',
            'status' => 'required|in:pending,completed,failed,refunded',
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get common company validation rules.
     */
    protected function getCompanyValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'website' => 'nullable|url|max:255',
            'tax_number' => 'nullable|string|max:50',
            'registration_number' => 'nullable|string|max:50',
            'logo' => 'nullable|file|mimes:jpeg,png,svg|max:2048',
        ];
    }

    /**
     * Get validation error messages.
     */
    protected function getValidationMessages(): array
    {
        return [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute must be a string.',
            'max' => 'The :attribute may not be greater than :max characters.',
            'min' => 'The :attribute must be at least :min.',
            'numeric' => 'The :attribute must be a number.',
            'integer' => 'The :attribute must be an integer.',
            'email' => 'The :attribute must be a valid email address.',
            'unique' => 'The :attribute has already been taken.',
            'exists' => 'The selected :attribute is invalid.',
            'date' => 'The :attribute is not a valid date.',
            'before' => 'The :attribute must be a date before :date.',
            'after' => 'The :attribute must be a date after :date.',
            'file' => 'The :attribute must be a file.',
            'mimes' => 'The :attribute must be a file of type: :values.',
            'regex' => 'The :attribute format is invalid.',
            'in' => 'The selected :attribute is invalid.',
            'boolean' => 'The :attribute field must be true or false.',
            'url' => 'The :attribute format is invalid.',
        ];
    }

    /**
     * Validate phone number format.
     */
    protected function validatePhoneNumber(string $phone): bool
    {
        return preg_match('/^[0-9+\-\s()]+$/', $phone);
    }

    /**
     * Validate email format.
     */
    protected function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate date format.
     */
    protected function validateDate(string $date, string $format = 'Y-m-d'): bool
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}