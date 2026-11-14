<?php

namespace ItmoSoft\Reports\Tests;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ItmoSoft\Reports\ReportsManager;

class ReportsManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->decimal('total', 12, 2);
            $table->string('status');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->timestamp('created_at')->nullable();
        });

        $this->app['db']->table('orders')->insert([
            ['customer_id' => 1, 'total' => 100, 'status' => 'paid', 'created_at' => '2026-11-01 10:00:00'],
            ['customer_id' => 2, 'total' => 50, 'status' => 'pending', 'created_at' => '2026-11-01 12:00:00'],
            ['customer_id' => 1, 'total' => 200, 'status' => 'completed', 'created_at' => '2026-11-02 10:00:00'],
        ]);

        $this->app['db']->table('users')->insert([
            ['created_at' => '2026-11-01 10:00:00'],
            ['created_at' => '2026-11-03 10:00:00'],
        ]);
    }

    public function test_order_summary_aggregates_orders_and_paid_statuses(): void
    {
        $summary = app(ReportsManager::class)->orderSummary();

        $this->assertSame(3, $summary['orders']);
        $this->assertEquals(350.0, $summary['gross_total']);
        $this->assertEqualsWithDelta(116.6667, $summary['average_order_value'], 0.001);
        $this->assertSame(2, $summary['paid_orders']);
    }

    public function test_daily_sales_groups_orders_by_date(): void
    {
        $rows = app(ReportsManager::class)->dailySales();

        $this->assertCount(2, $rows);
        $this->assertSame('2026-11-01', $rows[0]['date']);
        $this->assertEquals(150.0, $rows[0]['total']);
    }

    public function test_date_filters_apply_to_order_and_customer_reports(): void
    {
        $from = Carbon::parse('2026-11-02 00:00:00');
        $to = Carbon::parse('2026-11-02 23:59:59');

        $this->assertSame(1, app(ReportsManager::class)->orderSummary($from, $to)['orders']);
        $this->assertSame(0, app(ReportsManager::class)->customerCount($from, $to));
    }

    public function test_customer_count_uses_configured_customer_source(): void
    {
        $this->assertSame(2, app(ReportsManager::class)->customerCount());
    }
}
