<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionHeader extends Model
{
    protected $table = 'Transaction_Header';
    protected $primaryKey = 'transaction_id';
    public $timestamps = false;
    protected $fillable = [
        'external_id', 'internal_id', 'detail',
        'dmc', 'line', 'receive_date', 'return_date'
    ];

    protected $casts = [
        'receive_date' => 'datetime',
        'return_date' => 'datetime',
    ];

    public function externalUser(): BelongsTo
    {
        return $this->belongsTo(ExternalUser::class , 'external_id', 'external_id');
    }

    public function internalUser(): BelongsTo
    {
        return $this->belongsTo(User::class , 'internal_id', 'user_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(TransactionDetail::class , 'transaction_id', 'transaction_id');
    }
}
