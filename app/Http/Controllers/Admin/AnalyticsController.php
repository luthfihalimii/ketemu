<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'range' => ['nullable', 'in:7,30,90'],
        ]);

        $to = isset($filters['to']) ? CarbonImmutable::parse($filters['to']) : CarbonImmutable::now();
        $from = isset($filters['from'])
            ? CarbonImmutable::parse($filters['from'])
            : $to->subDays((int) ($filters['range'] ?? 30) - 1);

        if ($from->diffInDays($to) > 365) {
            $from = $to->subDays(364);
        }

        return view('admin.analytics', [
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'report' => $this->analytics->overview($from, $to),
        ]);
    }
}
