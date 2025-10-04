<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvestmentRequest extends FormRequest
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
            'investor_id' => 'sometimes|required|integer|exists:investors,id',
            'amount' => 'sometimes|required|numeric|min:0',
            'investment_date' => 'sometimes|required|date|before_or_equal:today',
            'expected_return_rate' => 'nullable|numeric|min:0|max:100',
            'duration_months' => 'nullable|integer|min:1|max:120',
            'status' => 'sometimes|in:active,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'investor_id.required' => 'Investor is required',
            'investor_id.exists' => 'Selected investor does not exist',
            'amount.required' => 'Investment amount is required',
            'amount.min' => 'Investment amount must be greater than or equal to 0',
            'investment_date.required' => 'Investment date is required',
            'investment_date.before_or_equal' => 'Investment date cannot be in the future',
            'expected_return_rate.min' => 'Expected return rate must be greater than or equal to 0',
            'expected_return_rate.max' => 'Expected return rate cannot exceed 100%',
            'duration_months.min' => 'Duration must be at least 1 month',
            'duration_months.max' => 'Duration cannot exceed 120 months',
            'status.in' => 'Status must be active, completed, or cancelled',
        ];
    }
}
