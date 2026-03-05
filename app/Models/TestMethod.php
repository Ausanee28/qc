<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestMethod extends Model
{
    protected $table = 'Test_Methods';
    protected $primaryKey = 'method_id';
    public $timestamps = false;
    protected $fillable = ['method_name', 'tool_name', 'equipment_id'];

    public function transactionDetails(): HasMany
    {
        return $this->hasMany(TransactionDetail::class , 'method_id', 'method_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id', 'equipment_id');
    }
}
