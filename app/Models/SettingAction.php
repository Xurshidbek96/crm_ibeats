<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingAction extends Model
{
    use HasFactory;

    protected $table = 'setting_actions';

    protected $fillable = ['name', 'conts_id', 'code'];

    public function controller()
    {
        return $this->belongsTo(\App\Models\SettingCont::class, 'conts_id');
    }

}
