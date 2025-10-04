<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingPermission extends Model
{
    use HasFactory;

    protected $table = 'setting_permissions';

    protected $fillable = ['role_id', 'conts_id', 'actions', 'action_id'];

    public function role()
    {
        return $this->belongsTo(\App\Models\SettingRole::class, 'role_id');
    }

    public function controller()
    {
        return $this->belongsTo(\App\Models\SettingCont::class, 'conts_id');
    }

    public function action()
    {
        return $this->belongsTo(\App\Models\SettingAction::class, 'action_id');
    }

}
