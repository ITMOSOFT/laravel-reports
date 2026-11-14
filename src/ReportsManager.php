<?php

namespace ItmoSoft\Reports;

use Carbon\CarbonInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use InvalidArgumentException;

class ReportsManager
{
    public function __construct(
        protected DatabaseManager $database,
        protected array $config
    ) {
    }

    /**
     * Return order count, gross total, average order value and paid-order count.
     *
     * @return array{orders:int,gross_total:float,average_order_value:float,paid_orders:int}
     */
    public function orderSummary(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $source = $this->source('orders');
        $query = $this->dateRange($this->database->table($source['table']), $source['created_at'], $from, $to);

        $row = (clone $query)->selectRaw(
            'COUNT(*) as aggregate_orders, COALESCE(SUM('.$this->wrap($source['total']).'), 0) as aggregate_total, COALESCE(AVG('.$this->wrap($source['total']).'), 0) as aggregate_average'
        )->first();

        $paid = (clone $query)->whereIn($source['status'], $source['paid_statuses'])->count();

        return [
            'orders' => (int) ($row->aggregate_orders ?? 0),
            'gross_total' => (float) ($row->aggregate_total ?? 0),
            'average_order_value' => (float) ($row->aggregate_average ?? 0),
            'paid_orders' => (int) $paid,
        ];
    }

    /**
     * Return order totals grouped by calendar date.
     *
     * @return array<int, array{date:string,orders:int,total:float}>
     */
    public function dailySales(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $source = $this->source('orders');
        $query = $this->dateRange($this->database->table($source['table']), $source['created_at'], $from, $to);

        return $query
            ->selectRaw('DATE('.$this->wrap($source['created_at']).') as report_date')
            ->selectRaw('COUNT(*) as report_orders')
            ->selectRaw('COALESCE(SUM('.$this->wrap($source['total']).'), 0) as report_total')
            ->groupByRaw('DATE('.$this->wrap($source['created_at']).')')
            ->orderBy('report_date')
            ->get()
            ->map(fn ($row) => [
                'date' => (string) $row->report_date,
                'orders' => (int) $row->report_orders,
                'total' => (float) $row->report_total,
            ])->all();
    }

    /**
     * Return customer count created in the requested date range.
     */
    public function customerCount(?CarbonInterface $from = null, ?CarbonInterface $to = null): int
    {
        $source = $this->source('customers');
        return $this->dateRange(
            $this->database->table($source['table']),
            $source['created_at'],
            $from,
            $to
        )->count();
    }

    /**
     * Build a safe, composable query for an application-defined report.
     *
     * The caller remains responsible for selecting columns, applying tenant
     * scopes, authorization and any domain-specific filters.
     */
    public function query(string $table): Builder
    {
        if ($table === '' || str_contains($table, "\0")) {
            throw new InvalidArgumentException('A valid table name is required.');
        }

        return $this->database->table($table);
    }

    protected function source(string $key): array
    {
        $source = $this->config[$key] ?? null;
        if (! is_array($source) || empty($source['table']) || empty($source['created_at'])) {
            throw new InvalidArgumentException("The [{$key}] report source is not configured.");
        }

        return $source;
    }

    protected function dateRange(Builder $query, string $column, ?CarbonInterface $from, ?CarbonInterface $to): Builder
    {
        if ($from !== null) {
            $query->where($column, '>=', $from->toDateTimeString());
        }
        if ($to !== null) {
            $query->where($column, '<=', $to->toDateTimeString());
        }

        return $query;
    }

    protected function wrap(string $identifier): string
    {
        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
            throw new InvalidArgumentException("Unsafe SQL identifier [{$identifier}].");
        }

        return $this->database->connection()->getQueryGrammar()->wrap($identifier);
    }
}
