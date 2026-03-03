<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestMethod extends Model
{
    protected $table = 'Test_Methods';
    protected $primaryKey = 'method_id';
    public $timestamps = false;
    protected $fillable = ['method_name', 'tool_name'];

    public function transactionDetails(): HasMany
    {
        return $this->hasMany(TransactionDetail::class , 'method_id', 'method_id');
    }
}
