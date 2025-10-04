<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingRole extends Model
{
    use HasFactory;

    protected $table = 'setting_roles';

    protected $fillable = ['name', 'label'];

    public function permissions()
    {
        return $this->hasMany(\App\Models\SettingPermission::class, 'role_id');
    }

    public function permissionsWith()
    {
        return $this->hasMany(\App\Models\SettingPermission::class, 'role_id')->with(['controller', 'action']);
    }
}
