<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionDetail extends Model
{
    public const JUDGEMENT_OK = 'OK';
    public const JUDGEMENT_NG = 'NG';

    protected $table = 'Transaction_Detail';
    protected $primaryKey = 'detail_id';
    public $timestamps = false;
    protected $fillable = [
        'transaction_id', 'method_id', 'internal_id',
        'start_time', 'end_time', 'judgement', 'remark'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function transactionHeader(): BelongsTo
    {
        return $this->belongsTo(TransactionHeader::class , 'transaction_id', 'transaction_id');
    }

    public function testMethod(): BelongsTo
    {
        return $this->belongsTo(TestMethod::class , 'method_id', 'method_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class , 'internal_id', 'user_id');
    }
}
