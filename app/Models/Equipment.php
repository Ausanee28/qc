<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends Model
{
    protected $table = 'Equipments';
    protected $primaryKey = 'equipment_id';
    public $timestamps = false;
    protected $fillable = ['equipment_name', 'is_active'];
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function transactionHeaders(): HasMany
    {
        return $this->hasMany(TransactionHeader::class , 'equipment_id', 'equipment_id');
    }
}
