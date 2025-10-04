<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentResource extends JsonResource
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
            'investor' => $this->investor->name,
            'investor_id' => $this->investor->id,
            'amount' => $this->amount,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
