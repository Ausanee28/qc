<?php

namespace App\Models;

use App\Support\SchemaCapabilities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionHeader extends Model
{
    use SoftDeletes {
        performDeleteOnModel as protected softPerformDeleteOnModel;
        restore as protected softRestore;
        trashed as protected softTrashed;
    }

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
