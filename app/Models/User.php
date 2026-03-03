<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'Internal_Users';
    protected $primaryKey = 'user_id';
    public $timestamps = false;

    protected $fillable = [
        'user_name',
        'user_password',
        'employee_id',
        'name',
        'role',
    ];

    protected $hidden = [
        'user_password',
    ];

    // Laravel Auth uses 'password' field — map to user_password
    public function getAuthPassword()
    {
        return $this->user_password;
    }
}
