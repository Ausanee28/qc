<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $table = 'Departments';
    protected $primaryKey = 'department_id';
    public $timestamps = false;
    protected $fillable = ['department_name', 'internal_phone', 'is_active'];
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function externalUsers(): HasMany
    {
        return $this->hasMany(ExternalUser::class , 'department_id', 'department_id');
    }
}
