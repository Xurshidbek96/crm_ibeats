<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'user_id',
        'device_id',
        'type',
        'quantity',
        'summa',
        'initial_payment',
        'body_price',
        'pay_type',
        'pay_day',
        'startDate',
        'is_cash',
        'status',
        'billing_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'summa' => 'decimal:2',
        'initial_payment' => 'decimal:2',
        'body_price' => 'decimal:2',
        'quantity' => 'integer',
        'pay_day' => 'integer',
        'is_cash' => 'boolean',
        'startDate' => 'date',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Get the client that owns the order.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user that created the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the device for this order (polymorphic).
     */
    public function device(): BelongsTo
    {
        if ($this->type === 'device') {
            return $this->belongsTo(Device::class, 'device_id');
        }
        
        return $this->belongsTo(Accessory::class, 'device_id');
    }

    /**
     * Get the monthly payments for this order.
     */
    public function monthlies(): HasMany
    {
        return $this->hasMany(Monthly::class);
    }

    /**
     * Get the model name of the ordered item.
     */
    public function getTypeModelAttribute(): ?string
    {
        if ($this->type === 'device') {
            return $this->device?->model;
        } elseif ($this->type === 'accessory') {
            return $this->device?->model;
        }
        
        return null;
    }

    /**
     * Check if the order is a cash order.
     */
    public function isCashOrder(): bool
    {
        return $this->is_cash === true;
    }

    /**
     * Check if the order is for a device.
     */
    public function isDeviceOrder(): bool
    {
        return $this->type === 'device';
    }

    /**
     * Check if the order is for an accessory.
     */
    public function isAccessoryOrder(): bool
    {
        return $this->type === 'accessory';
    }

    /**
     * Get the remaining balance for this order.
     */
    public function getRemainingBalanceAttribute(): float
    {
        return $this->summa - $this->initial_payment;
    }
}
