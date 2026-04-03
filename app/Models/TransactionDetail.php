<?php

namespace App\Models;

use App\Support\SchemaCapabilities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionDetail extends Model
{
    use SoftDeletes {
        performDeleteOnModel as protected softPerformDeleteOnModel;
        restore as protected softRestore;
        trashed as protected softTrashed;
    }

    public const JUDGEMENT_OK = 'OK';
    public const JUDGEMENT_NG = 'NG';

    protected $table = 'Transaction_Detail';
    protected $primaryKey = 'detail_id';
    public $timestamps = false;
    protected $fillable = [
        'transaction_id', 'method_id', 'internal_id',
        'start_time', 'end_time', 'duration_sec', 'max_value', 'min_value', 'judgement', 'remark'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public static function supportsSoftDeletes(): bool
    {
        static $supports = null;

        if ($supports !== null) {
            return $supports;
        }

        try {
            $instance = new static();
            $supports = SchemaCapabilities::hasColumn($instance->getTable(), $instance->getDeletedAtColumn());
        } catch (\Throwable) {
            $supports = false;
        }

        return $supports;
    }

    public static function bootSoftDeletes()
    {
        if (static::supportsSoftDeletes()) {
            static::addGlobalScope(new SoftDeletingScope);
        }
    }

    public function scopeWithTrashed(Builder $query, bool $withTrashed = true): Builder
    {
        if (!static::supportsSoftDeletes()) {
            return $query;
        }

        return $withTrashed
            ? $query->withoutGlobalScope(SoftDeletingScope::class)
            : $query->whereNull($this->getQualifiedDeletedAtColumn());
    }

    public function scopeOnlyTrashed(Builder $query): Builder
    {
        if (!static::supportsSoftDeletes()) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->whereNotNull($this->getQualifiedDeletedAtColumn());
    }

    public function scopeWithoutTrashed(Builder $query): Builder
    {
        if (!static::supportsSoftDeletes()) {
            return $query;
        }

        return $query
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->whereNull($this->getQualifiedDeletedAtColumn());
    }

    protected function performDeleteOnModel()
    {
        if (!static::supportsSoftDeletes()) {
            return tap($this->setKeysForSaveQuery($this->newModelQuery())->forceDelete(), function () {
                $this->exists = false;
            });
        }

        return $this->softPerformDeleteOnModel();
    }

    public function restore()
    {
        if (!static::supportsSoftDeletes()) {
            return true;
        }

        return $this->softRestore();
    }

    public function trashed()
    {
        if (!static::supportsSoftDeletes()) {
            return false;
        }

        return $this->softTrashed();
    }

    public function transactionHeader(): BelongsTo
    {
        $relation = $this->belongsTo(TransactionHeader::class , 'transaction_id', 'transaction_id');

        if (TransactionHeader::supportsSoftDeletes()) {
            $relation->withTrashed();
        }

        return $relation;
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
