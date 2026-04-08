<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalUser extends Model
{
    protected $table = 'External_Users';
    protected $primaryKey = 'external_id';
    public $timestamps = false;
    protected $fillable = ['external_name', 'department_id', 'is_active'];
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class , 'department_id', 'department_id');
    }

    public function transactionHeaders(): HasMany
    {
        return $this->hasMany(TransactionHeader::class , 'external_id', 'external_id');
    }
}
