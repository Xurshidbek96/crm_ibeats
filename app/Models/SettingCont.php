<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingCont extends Model
{
    use HasFactory;

    protected $table = 'setting_conts';

    protected $fillable = ['name', 'label'];

    public function actions()
    {
        return $this->hasMany(\App\Models\SettingAction::class, 'conts_id');
    }




}
