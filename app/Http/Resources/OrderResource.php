<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'NumberOrder' => $this->NumberOrder,
            'status' => $this->status,
            'client' => $this->client->name,
            'passport' => $this->client->passport,
            'bail_name' => $this->client->bail_name,
            'bail_phone' => $this->client->bail_phone,
            'device' => $this->device->model,
            'pay_type' => $this->pay_type,
            'body_price' => $this->body_price,
            'summa' => $this->summa,
            'initial_payment' => $this->initial_payment,
            'pay_day' => $this->pay_day,
            'rest_summa' => $this->rest_summa,
            'discount' => $this->discount,
            'benefit' => $this->benefit,
            'box' => $this->box,
            'startDate' => $this->startDate,
            'notes' => $this->notes,
            'admin' => $this->user->name,
            'monthlies' => MonthlyResource::collection($this->monthlies)
        ];
    }
}
