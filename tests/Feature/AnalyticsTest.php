<?php

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Enums\Role;
use App\Models\Item;
use App\Models\User;
use App\Services\AnalyticsService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsItemFlow;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use BuildsItemFlow, RefreshDatabase;

    #[Test]
    public function overview_counts_found_lost_and_return_rate(): void
    {
        $reporter = $this->student();
        $this->storedItem([], $reporter);
        $this->storedItem([
            'status' => ItemStatus::Returned,
            'stored_at' => now()->subDays(5),
            'returned_at' => now()->subDay(),
        ], $reporter);
        Item::factory()->lost()->create(['user_id' => $reporter->id]);

        $report = app(AnalyticsService::class)->overview(
            CarbonImmutable::now()->subDays(7), CarbonImmutable::now(),
        );

        $this->assertSame(2, $report['totals']['found']);
        $this->assertSame(1, $report['totals']['lost']);
        $this->assertSame(1, $report['totals']['returned']);
        $this->assertSame(50.0, $report['totals']['return_rate']);
        $this->assertNotEmpty($report['daily']);
        $this->assertNotEmpty($report['top_categories']);
    }

    #[Test]
    public function admin_can_open_analytics_with_date_filters(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $this->storedItem();

        $this->actingAs($admin)->get(route('admin.analytics'))->assertOk()->assertSee('Analitik');
        $this->actingAs($admin)
            ->get(route('admin.analytics', ['from' => now()->subDays(7)->toDateString(), 'to' => now()->toDateString()]))
            ->assertOk()->assertSee('Return rate');
    }

    #[Test]
    public function students_cannot_open_analytics(): void
    {
        $this->actingAs($this->student())->get(route('admin.analytics'))->assertForbidden();
    }
}
