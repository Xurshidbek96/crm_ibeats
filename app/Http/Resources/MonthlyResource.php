<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonthlyResource extends JsonResource
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
            'order_id' => $this->order->id,
            'payment_month' => $this->payment_month,
            'month' => $this->month,
            'summa' => $this->summa,
            'rest_summa' => $this->rest_summa,
            'comment' => $this->comment,
            'status' => $this->status,
            'created_at' => $this->created_at->format('d-m-Y h:m:i'),
            'payments' => PaymentResource::collection($this->payments)
        ];
    }
}
