<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Accessory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'model',
        'brand',
        'price',
        'quantity',
        'description',
        'image',
        'status',
        'billing_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'status' => 'boolean',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Get the order associated with this accessory.
     */
    public function order(): HasOne
    {
        return $this->hasOne(Order::class, 'device_id')->where('type', 'accessory');
    }

    /**
     * Check if accessory is available for sale.
     */
    public function isAvailable(): bool
    {
        return $this->status === true && $this->quantity > 0;
    }

    /**
     * Check if requested quantity is available.
     */
    public function hasAvailableQuantity(int $requestedQuantity): bool
    {
        return $this->quantity >= $requestedQuantity;
    }

    /**
     * Decrease the quantity by the specified amount.
     */
    public function decreaseQuantity(int $amount): bool
    {
        if ($this->hasAvailableQuantity($amount)) {
            $this->quantity -= $amount;
            return $this->save();
        }
        
        return false;
    }

    /**
     * Get the formatted price with currency.
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2) . ' UZS';
    }

    /**
     * Get the accessory's full name (brand + model).
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->brand . ' ' . $this->model);
    }
}
