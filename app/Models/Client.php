<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'surname',
        'passport',
        'passport_status',
        'date_of_issue',
        'date_of_birth',
        'gender',
        'number_of_children',
        'email',
        'phones',
        'file',
        'file_passport',
        'address',
        'billing_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_issue' => 'date',
        'date_of_birth' => 'date',
        'gender' => 'integer',
        'number_of_children' => 'integer',
        'phones' => 'array',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Get all orders for this client.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the client's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->name . ' ' . $this->surname);
    }

    /**
     * Get the file URL attribute.
     */
    public function getFileUrlAttribute(): ?string
    {
        return $this->file ? url('files/' . $this->file) : null;
    }

    /**
     * Get the passport file URL attribute.
     */
    public function getFilePassportUrlAttribute(): ?string
    {
        return $this->file_passport ? url('files/' . $this->file_passport) : null;
    }

    /**
     * Get the formatted phone numbers.
     */
    public function getFormattedPhonesAttribute(): ?array
    {
        return $this->phones;
    }
}
