<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investor extends Model
{
    use HasFactory;
    protected $guarded = [] ;

    public function investments() { return $this->hasMany(Investment::class) ;}
    public function investorMonthlySalaries() { return $this->hasMany(InvestorMonthlySalary::class) ;}
}
