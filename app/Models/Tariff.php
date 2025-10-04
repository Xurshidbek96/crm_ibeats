<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tariff extends Model
{
    use HasFactory;
    
    protected $connection = 'shared';

    protected $fillable = [
        'name',
        'description',
        'contract_count',
        'price',
        'discount'
    ];
}
