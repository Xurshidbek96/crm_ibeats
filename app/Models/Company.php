<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'logo',
        'domain',
        'colors',
        'client_id',
        'schema_name',
        'company_uid'
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }
}
