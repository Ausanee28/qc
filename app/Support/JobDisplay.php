<?php

namespace App\Support;

use App\Models\TransactionHeader;
use Illuminate\Support\Carbon;

class JobDisplay
{
    private const DISPLAY_TIMEZONE = 'Asia/Bangkok';

    public static function shortDateTime($value): ?string
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value)->timezone(self::DISPLAY_TIMEZONE)->format('d-m-y H:i');
    }

    public static function labelsForHeaders(iterable $jobs): array
    {
        $jobs = collect($jobs)
            ->filter(fn ($job) => $job?->transaction_id && $job?->receive_date)
            ->values();

        if ($jobs->isEmpty()) {
            return [];
        }

        $dateKeys = $jobs
            ->map(fn ($job) => self::dateKey($job->receive_date))
            ->filter()
            ->unique()
            ->values();

        if ($dateKeys->isEmpty()) {
            return [];
        }

        $sequences = [];

        foreach ($dateKeys as $dateKey) {
            foreach (self::headersForDisplayDate($dateKey) as $index => $job) {
                $sequences[(int) $job->transaction_id] = $index + 1;
            }
        }

        return $jobs
            ->mapWithKeys(function ($job) use ($sequences) {
                $transactionId = (int) $job->transaction_id;

                return [$transactionId => self::label($sequences[$transactionId] ?? null)];
            })
            ->all();
    }

    public static function labelsForTransactionIds(iterable $transactionIds): array
    {
        $ids = collect($transactionIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $query = TransactionHeader::query();

        if (TransactionHeader::supportsSoftDeletes()) {
            $query->withTrashed();
        }

        return self::labelsForHeaders(
            $query
                ->whereIn('transaction_id', $ids)
                ->get(['transaction_id', 'receive_date'])
        );
    }

    public static function transactionIdsForSearch(string $search, ?Carbon $from = null, ?Carbon $to = null): ?array
    {
        $sequence = self::sequenceFromSearch($search);

        if ($sequence === null) {
            return null;
        }

        $query = TransactionHeader::query();

        if (TransactionHeader::supportsSoftDeletes()) {
            $query->withTrashed();
        }

        if ($from !== null) {
            $query->where('receive_date', '>=', $from);
        }

        if ($to !== null) {
            $query->where('receive_date', '<=', $to);
        }

        $matches = [];
        $currentDate = null;
        $currentSequence = 0;

        foreach ($query->select(['transaction_id', 'receive_date'])->orderBy('receive_date')->orderBy('transaction_id')->cursor() as $job) {
            $dateKey = self::dateKey($job->receive_date);

            if ($dateKey === null) {
                continue;
            }

            if ($dateKey !== $currentDate) {
                $currentDate = $dateKey;
                $currentSequence = 0;
            }

            $currentSequence++;

            if ($currentSequence === $sequence) {
                $matches[] = (int) $job->transaction_id;
            }
        }

        return $matches;
    }

    private static function sequenceFromSearch(string $search): ?int
    {
        if (!preg_match('/^\s*(?:job\s*#?\s*)?0*(\d+)\s*$/i', $search, $matches)) {
            return null;
        }

        $sequence = (int) $matches[1];

        return $sequence > 0 ? $sequence : null;
    }

    private static function headersForDisplayDate(string $dateKey)
    {
        $storageTimezone = config('app.timezone', self::DISPLAY_TIMEZONE);
        $start = Carbon::createFromFormat('Y-m-d H:i:s', "{$dateKey} 00:00:00", self::DISPLAY_TIMEZONE)
            ->timezone($storageTimezone);
        $end = Carbon::createFromFormat('Y-m-d H:i:s', "{$dateKey} 23:59:59", self::DISPLAY_TIMEZONE)
            ->timezone($storageTimezone);

        $query = TransactionHeader::query();

        if (TransactionHeader::supportsSoftDeletes()) {
            $query->withTrashed();
        }

        return $query
            ->whereBetween('receive_date', [$start, $end])
            ->orderBy('receive_date')
            ->orderBy('transaction_id')
            ->get(['transaction_id', 'receive_date']);
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
            return 'Job ---';
        }

        return 'Job ' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}
