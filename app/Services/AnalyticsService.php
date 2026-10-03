<?php

namespace App\Services;

use App\Enums\ClaimStatus;
use App\Enums\ItemStatus;
use App\Models\Claim;
use App\Models\Item;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Analytics P2 — agregasi operasional murni dari DB, tanpa tracker eksternal.
 *
 * Semua query DB-agnostic (MySQL + SQLite) agar test tetap jalan.
 * Tidak menyentuh PII / jawaban verifikasi — hanya hitungan + rata-rata.
 */
class AnalyticsService
{
    /**
     * @return array{
     *   from: string, to: string, days: int,
     *   totals: array{found: int, lost: int, claims: int, returned: int, return_rate: float|null},
     *   sla: array{median_days_to_return: float|null, avg_claim_attempts: float|null},
     *   daily: array<int, array{date: string, found: int, lost: int, claims: int, returned: int}>,
     *   top_categories: array<int, array{id: int|null, name: string, total: int}>,
     *   top_locations: array<int, array{id: int|null, name: string, total: int}>,
     *   funnel: array<string, int>
     * }
     */
    public function overview(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $from = $from->startOfDay();
        $to = $to->endOfDay();

        $found = Item::query()->whereNotNull('deposit_location_id')
            ->whereBetween('created_at', [$from->toDateTimeString(), $to->toDateTimeString()]);
        $lost = Item::query()->whereNull('deposit_location_id')
            ->whereBetween('created_at', [$from->toDateTimeString(), $to->toDateTimeString()]);
        $claims = Claim::query()
            ->whereBetween('created_at', [$from->toDateTimeString(), $to->toDateTimeString()]);
        $returned = Item::query()->where('status', ItemStatus::Returned->value)
            ->whereBetween('returned_at', [$from->toDateTimeString(), $to->toDateTimeString()]);

        $foundCount = (clone $found)->count();
        $lostCount = (clone $lost)->count();
        $claimsCount = (clone $claims)->count();
        $returnedCount = (clone $returned)->count();

        $daily = $this->dailySeries($from, $to);

        $topCategories = DB::table('items')
            ->leftJoin('categories', 'categories.id', '=', 'items.category_id')
            ->whereBetween('items.created_at', [$from->toDateTimeString(), $to->toDateTimeString()])
            ->selectRaw('items.category_id as id, COALESCE(categories.name, ?) as name, count(*) as total', ['Tanpa kategori'])
            ->groupBy('items.category_id', 'categories.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn (object $row) => ['id' => $row->id === null ? null : (int) $row->id, 'name' => (string) $row->name, 'total' => (int) $row->total])
            ->all();

        $topLocations = DB::table('items')
            ->leftJoin('locations', 'locations.id', '=', 'items.location_id')
            ->whereBetween('items.created_at', [$from->toDateTimeString(), $to->toDateTimeString()])
            ->selectRaw('items.location_id as id, COALESCE(locations.name, ?) as name, count(*) as total', ['Tanpa lokasi'])
            ->groupBy('items.location_id', 'locations.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn (object $row) => ['id' => $row->id === null ? null : (int) $row->id, 'name' => (string) $row->name, 'total' => (int) $row->total])
            ->all();

        $funnel = [
            'REPORTED' => Item::query()->where('status', ItemStatus::Reported->value)->whereBetween('created_at', [$from->toDateTimeString(), $to->toDateTimeString()])->count(),
            'WAITING_DEPOSIT' => Item::query()->where('status', ItemStatus::WaitingDeposit->value)->whereBetween('created_at', [$from->toDateTimeString(), $to->toDateTimeString()])->count(),
            'STORED' => Item::query()->where('status', ItemStatus::Stored->value)->whereBetween('created_at', [$from->toDateTimeString(), $to->toDateTimeString()])->count(),
            'CLAIMED' => Item::query()->where('status', ItemStatus::Claimed->value)->whereBetween('created_at', [$from->toDateTimeString(), $to->toDateTimeString()])->count(),
            'READY_FOR_PICKUP' => Item::query()->where('status', ItemStatus::ReadyForPickup->value)->whereBetween('created_at', [$from->toDateTimeString(), $to->toDateTimeString()])->count(),
            'RETURNED' => $returnedCount,
        ];

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'days' => (int) $from->diffInDays($to) + 1,
            'totals' => [
                'found' => $foundCount,
                'lost' => $lostCount,
                'claims' => $claimsCount,
                'returned' => $returnedCount,
                'return_rate' => $foundCount > 0 ? round($returnedCount / $foundCount * 100, 1) : null,
            ],
            'sla' => [
                'median_days_to_return' => $this->medianDaysToReturn($from, $to),
                'avg_claim_attempts' => $this->avgClaimAttempts($from, $to),
            ],
            'daily' => $daily,
            'top_categories' => $topCategories,
            'top_locations' => $topLocations,
            'funnel' => $funnel,
        ];
    }

    /**
     * @return array<int, array{date: string, found: int, lost: int, claims: int, returned: int}>
     */
    private function dailySeries(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $foundByDay = $this->countByDay(Item::query()->whereNotNull('deposit_location_id'), 'created_at', $from, $to);
        $lostByDay = $this->countByDay(Item::query()->whereNull('deposit_location_id'), 'created_at', $from, $to);
        $claimsByDay = $this->countByDay(Claim::query(), 'created_at', $from, $to);
        $returnedByDay = $this->countByDay(
            Item::query()->where('status', ItemStatus::Returned->value), 'returned_at', $from, $to,
        );

        $days = [];
        for ($day = $from; $day->lte($to); $day = $day->addDay()) {
            $key = $day->toDateString();
            $days[] = [
                'date' => $key,
                'found' => $foundByDay[$key] ?? 0,
                'lost' => $lostByDay[$key] ?? 0,
                'claims' => $claimsByDay[$key] ?? 0,
                'returned' => $returnedByDay[$key] ?? 0,
            ];
        }

        return $days;
    }

    /**
     * @param  Builder<Item>|Builder<Claim>  $query
     * @return array<string, int>
     */
    private function countByDay(Builder $query, string $column, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return (clone $query)
            ->whereBetween($column, [$from->toDateTimeString(), $to->toDateTimeString()])
            ->selectRaw("DATE({$column}) as day, count(*) as total")
            ->groupBy('day')
            ->pluck('total', 'day')
            ->mapWithKeys(fn ($total, $day) => [(string) $day => (int) $total])
            ->all();
    }

    private function medianDaysToReturn(CarbonImmutable $from, CarbonImmutable $to): ?float
    {
        $durations = Item::query()
            ->where('status', ItemStatus::Returned->value)
            ->whereBetween('returned_at', [$from->toDateTimeString(), $to->toDateTimeString()])
            ->whereNotNull('stored_at')
            ->orderBy('returned_at')
            ->limit(2000)
            ->get(['stored_at', 'returned_at'])
            ->map(fn (Item $item) => $item->stored_at->diffInHours($item->returned_at) / 24)
            ->sort()
            ->values();

        if ($durations->isEmpty()) {
            return null;
        }

        $mid = intdiv($durations->count(), 2);

        return round(
            $durations->count() % 2 === 1
                ? $durations[$mid]
                : ($durations[$mid - 1] + $durations[$mid]) / 2,
            1,
        );
    }

    private function avgClaimAttempts(CarbonImmutable $from, CarbonImmutable $to): ?float
    {
        $avg = Claim::query()
            ->where('status', ClaimStatus::Completed->value)
            ->whereBetween('completed_at', [$from->toDateTimeString(), $to->toDateTimeString()])
            ->avg('attempt_count');

        return $avg === null ? null : round((float) $avg, 1);
    }

    /**
     * Ringkasan kecil untuk dasbor admin (7 hari terakhir).
     *
     * @return array{found_7d: int, returned_7d: int, return_rate_30d: float|null}
     */
    public function headline(): array
    {
        $now = CarbonImmutable::now();
        $week = $this->overview($now->subDays(6), $now);
        $month = $this->overview($now->subDays(29), $now);

        return [
            'found_7d' => $week['totals']['found'],
            'returned_7d' => $week['totals']['returned'],
            'return_rate_30d' => $month['totals']['return_rate'],
        ];
    }
}
