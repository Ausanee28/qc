<?php

namespace App\Support;

use App\Models\TransactionDetail;
use Illuminate\Support\Carbon;

class ResultDisplay
{
    private const DISPLAY_TIMEZONE = 'Asia/Bangkok';

    public static function labelsForDetails(iterable $details): array
    {
        $details = collect($details)
            ->filter(fn ($detail) => $detail?->detail_id && $detail?->start_time)
            ->values();

        if ($details->isEmpty()) {
            return [];
        }

        $dateKeys = $details
            ->map(fn ($detail) => self::dateKey($detail->start_time))
            ->filter()
            ->unique()
            ->values();

        if ($dateKeys->isEmpty()) {
            return [];
        }

        $sequences = [];

        foreach ($dateKeys as $dateKey) {
            foreach (self::detailsForDisplayDate($dateKey) as $index => $detail) {
                $sequences[(int) $detail->detail_id] = $index + 1;
            }
        }

        return $details
            ->mapWithKeys(function ($detail) use ($sequences) {
                $detailId = (int) $detail->detail_id;

                return [$detailId => self::label($sequences[$detailId] ?? null)];
            })
            ->all();
    }

    public static function labelsForDetailIds(iterable $detailIds): array
    {
        $ids = collect($detailIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $query = TransactionDetail::query();

        if (TransactionDetail::supportsSoftDeletes()) {
            $query->withTrashed();
        }

        return self::labelsForDetails(
            $query
                ->whereIn('detail_id', $ids)
                ->get(['detail_id', 'start_time'])
        );
    }

    private static function detailsForDisplayDate(string $dateKey)
    {
        $storageTimezone = config('app.timezone', self::DISPLAY_TIMEZONE);
        $start = Carbon::createFromFormat('Y-m-d H:i:s', "{$dateKey} 00:00:00", self::DISPLAY_TIMEZONE)
            ->timezone($storageTimezone);
        $end = Carbon::createFromFormat('Y-m-d H:i:s', "{$dateKey} 23:59:59", self::DISPLAY_TIMEZONE)
            ->timezone($storageTimezone);

        $query = TransactionDetail::query();

        if (TransactionDetail::supportsSoftDeletes()) {
            $query->withTrashed();
        }

        return $query
            ->whereBetween('start_time', [$start, $end])
            ->orderBy('start_time')
            ->orderBy('detail_id')
            ->get(['detail_id', 'start_time']);
    }

    private static function dateKey($value): ?string
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value)->timezone(self::DISPLAY_TIMEZONE)->format('Y-m-d');
    }

    private static function label(?int $sequence): string
    {
        if (!$sequence) {
            return 'Result ---';
        }

        return 'Result ' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}
