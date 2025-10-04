<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'passport' => $this->passport,
            'phone' => $this->phone,
            'percentage' => $this->percentage,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'investments' => InvestmentResource::collection($this->investments),
            'investorMonthlySalary' => InvestorMonthlySalaryResource::collection($this->investorMonthlySalaries),
        ];
    }
}
