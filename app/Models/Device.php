<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Device extends Model
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
        'color',
        'memory',
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
        'memory' => 'integer',
        'status' => 'boolean',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Get the order associated with this device.
     */
    public function order(): HasOne
    {
        return $this->hasOne(Order::class, 'device_id')->where('type', 'device');
    }

    /**
     * Check if device is available for sale.
     */
    public function isAvailable(): bool
    {
        return $this->status === true && !$this->order()->exists();
    }

    /**
     * Get the formatted price with currency.
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2) . ' UZS';
    }

    /**
     * Get the device's full name (brand + model).
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->brand . ' ' . $this->model);
    }
}
