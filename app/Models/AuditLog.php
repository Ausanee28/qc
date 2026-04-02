<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'Audit_Logs';
    protected $primaryKey = 'audit_id';
    public $timestamps = false;

    protected $fillable = [
        'module',
        'action',
        'record_type',
        'record_id',
        'performed_by',
        'performed_by_name',
        'before_data',
        'after_data',
    ];

    protected $casts = [
        'before_data' => 'array',
        'after_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by', 'user_id');
    }
}
