<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionDetail extends Model
{
    use SoftDeletes;

    public const JUDGEMENT_OK = 'OK';
    public const JUDGEMENT_NG = 'NG';

    protected $table = 'Transaction_Detail';
    protected $primaryKey = 'detail_id';
    public $timestamps = false;
    protected $fillable = [
        'transaction_id', 'method_id', 'internal_id',
        'start_time', 'end_time', 'duration_sec', 'judgement', 'remark'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function transactionHeader(): BelongsTo
    {
        return $this->belongsTo(TransactionHeader::class , 'transaction_id', 'transaction_id')->withTrashed();
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
