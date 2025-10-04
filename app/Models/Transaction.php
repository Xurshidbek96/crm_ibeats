<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $connection = 'shared';

    protected $fillable = [
        'trans_id',
        'amount',
        'provider',
        'status',
        'invoice_id',
        'user_id',
        'company_id',
        'prepare_id',
        'paydoc_id',
        'merchant_trans_id',
        'sign',
        'reason',
        'state',
        'create_time',
        'perform_time',
        'cancel_time',
        'provider_time'
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
