<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ValidationService
{
    /**
     * Validate client data for creation or update.
     */
    public function validateClientData(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'father_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'phone2' => 'nullable|string|max:20',
            'address' => 'required|string|max:500',
            'passport_serial' => 'required|string|max:20',
            'passport_number' => 'required|string|max:20',
            'passport_date' => 'required|date',
            'passport_location' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'work_address' => 'nullable|string|max:500',
            'work_position' => 'nullable|string|max:255',
            'salary' => 'nullable|numeric|min:0',
            'additional_phone' => 'nullable|string|max:20',
            'additional_phone2' => 'nullable|string|max:20',
        ];

        if (!$isUpdate) {
            $rules['file'] = 'required|file|mimes:jpg,jpeg,png,pdf|max:2048';
            $rules['file_passport'] = 'required|file|mimes:jpg,jpeg,png,pdf|max:2048';
        } else {
            $rules['file'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048';
            $rules['file_passport'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048';
        }

        return $request->validate($rules);
    }

    /**
     * Validate order data for creation.
     */
    public function validateOrderData(Request $request): array
    {
        return $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'device_id' => 'required|integer',
            'type' => 'required|string|in:device,accessory',
            'summa' => 'required|numeric|min:0',
            'initial_payment' => 'required|numeric|min:0',
            'pay_type' => 'required|integer|min:1|max:24',
            'is_cash' => 'required|boolean',
            'quantity' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:1000',
            'startDate' => 'nullable|date',
            'body_price' => 'nullable|numeric|min:0',
        ]);
    }

    /**
     * Validate order update data.
     */
    public function validateOrderUpdateData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|integer|in:0,1,2',
        ]);
    }

    /**
     * Validate device data.
     */
    public function validateDeviceData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'color' => 'required|string|max:100',
            'memory' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'incoming_price' => 'required|numeric|min:0',
            'imei' => 'required|string|max:50|unique:devices,imei',
            'status' => 'required|integer|in:0,1',
        ]);
    }

    /**
     * Validate accessory data.
     */
    public function validateAccessoryData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'color' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'incoming_price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
        ]);
    }

    /**
     * Validate user authentication data.
     */
    public function validateAuthData(Request $request): array
    {
        return $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
    }

    /**
     * Validate user registration data.
     */
    public function validateRegistrationData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'nullable|string|in:admin,employee,manager',
        ]);
    }

    /**
     * Custom validation for business rules.
     */
    public function validateBusinessRules(array $data): void
    {
        // Check if initial payment is not greater than total amount
        if (isset($data['initial_payment']) && isset($data['summa'])) {
            if ($data['initial_payment'] > $data['summa']) {
                throw ValidationException::withMessages([
                    'initial_payment' => ['Initial payment cannot be greater than total amount.']
                ]);
            }
        }

        // Check if cash order has full payment
        if (isset($data['is_cash']) && $data['is_cash'] && isset($data['initial_payment']) && isset($data['summa'])) {
            if ($data['initial_payment'] < $data['summa']) {
                throw ValidationException::withMessages([
                    'initial_payment' => ['Cash orders must have full payment.']
                ]);
            }
        }
    }
}