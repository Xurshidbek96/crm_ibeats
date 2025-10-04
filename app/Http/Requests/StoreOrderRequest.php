<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
        return [
            'client_id' => 'required|integer|exists:clients,id',
            'type' => 'required|in:device,accessory,cash',
            'device_id' => 'required_if:type,device|integer|exists:devices,id',
            'accessory_id' => 'required_if:type,accessory|integer|exists:accessories,id',
            'quantity' => 'required_if:type,accessory|integer|min:1',
            'price' => 'required|numeric|min:0',
            'prepayment' => 'required|numeric|min:0',
            'monthly_payment' => 'required|numeric|min:0',
            'month' => 'required|integer|min:1|max:60',
            'comment' => 'nullable|string|max:1000',
            'status' => 'sometimes|in:active,completed,cancelled',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'client_id.required' => 'Client is required',
            'client_id.exists' => 'Selected client does not exist',
            'type.required' => 'Order type is required',
            'type.in' => 'Order type must be device, accessory, or cash',
            'device_id.required_if' => 'Device is required for device orders',
            'device_id.exists' => 'Selected device does not exist',
            'accessory_id.required_if' => 'Accessory is required for accessory orders',
            'accessory_id.exists' => 'Selected accessory does not exist',
            'quantity.required_if' => 'Quantity is required for accessory orders',
            'quantity.min' => 'Quantity must be at least 1',
            'price.required' => 'Price is required',
            'price.min' => 'Price must be greater than or equal to 0',
            'prepayment.required' => 'Prepayment is required',
            'prepayment.min' => 'Prepayment must be greater than or equal to 0',
            'monthly_payment.required' => 'Monthly payment is required',
            'monthly_payment.min' => 'Monthly payment must be greater than or equal to 0',
            'month.required' => 'Number of months is required',
            'month.min' => 'Number of months must be at least 1',
            'month.max' => 'Number of months cannot exceed 60',
            'status.in' => 'Status must be active, completed, or cancelled',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Custom validation: prepayment should not exceed price
            if ($this->prepayment > $this->price) {
                $validator->errors()->add('prepayment', 'Prepayment cannot exceed the total price');
            }

            // Custom validation: monthly payment calculation
            $remainingAmount = $this->price - $this->prepayment;
            $expectedMonthlyPayment = $remainingAmount / $this->month;
            
            if (abs($this->monthly_payment - $expectedMonthlyPayment) > 0.01) {
                $validator->errors()->add('monthly_payment', 'Monthly payment does not match the calculated amount');
            }
        });
    }
}