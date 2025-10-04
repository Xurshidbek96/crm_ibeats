<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $connection = 'shared';

    protected $fillable = [
        'total_amount',
        'discount_summa',
        'discount_percentage',
        'tariff_id',
        'user_id',
        'company_id',
        'date',
        'provider',
        'status'
    ];
    
}
