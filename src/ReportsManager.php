<?php
namespace ItmoSoft\Reports;
use Carbon\CarbonInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use InvalidArgumentException;
class ReportsManager
{
    public function __construct(protected DatabaseManager $database, protected array $config) {}
    public function orderSummary(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $source = $this->source('orders', ['table','total','status','created_at','paid_statuses']);
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
    public function dailySales(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $source = $this->source('orders', ['table','total','created_at']);
        $query = $this->dateRange($this->database->table($source['table']), $source['created_at'], $from, $to);
        return $query
            ->selectRaw('DATE('.$this->wrap($source['created_at']).') as report_date')
            ->selectRaw('COUNT(*) as report_orders')
            ->selectRaw('COALESCE(SUM('.$this->wrap($source['total']).'), 0) as report_total')
            ->groupByRaw('DATE('.$this->wrap($source['created_at']).')')
            ->orderBy('report_date')->get()
            ->map(fn ($row) => ['date'=>(string)$row->report_date,'orders'=>(int)$row->report_orders,'total'=>(float)$row->report_total])->all();
    }
    public function customerCount(?CarbonInterface $from = null, ?CarbonInterface $to = null): int
    {
        $source = $this->source('customers', ['table','created_at']);
        return $this->dateRange($this->database->table($source['table']), $source['created_at'], $from, $to)->count();
    }
    public function query(string $table): Builder
    {
        if (! $this->isSafeIdentifier($table, true)) {
            throw new InvalidArgumentException('A valid table identifier is required.');
        }
        return $this->database->table($table);
    }
    protected function source(string $key, array $required): array
    {
        $source = $this->config[$key] ?? null;
        if (! is_array($source)) {
            throw new InvalidArgumentException("The [{$key}] report source is not configured.");
        }
        foreach ($required as $field) {
            if (! array_key_exists($field, $source) || $source[$field] === '' || $source[$field] === null) {
                throw new InvalidArgumentException("The [{$key}.{$field}] report setting is required.");
            }
            if ($field !== 'paid_statuses' && ! $this->isSafeIdentifier((string)$source[$field], $field === 'table')) {
                throw new InvalidArgumentException("The [{$key}.{$field}] setting must be a valid SQL identifier.");
            }
        }
        if (in_array('paid_statuses', $required, true)) {
            if (! is_array($source['paid_statuses']) || array_filter($source['paid_statuses'], fn($v) => !is_string($v) && !is_int($v)) !== []) {
                throw new InvalidArgumentException('The [orders.paid_statuses] setting must be an array of strings or integers.');
            }
        }
        return $source;
    }
    protected function dateRange(Builder $query, string $column, ?CarbonInterface $from, ?CarbonInterface $to): Builder
    {
        if ($from !== null && $to !== null && $from->greaterThan($to)) {
            throw new InvalidArgumentException('The report start date must not be later than the end date.');
        }
        if ($from !== null) $query->where($column, '>=', $from->toDateTimeString());
        if ($to !== null) $query->where($column, '<=', $to->toDateTimeString());
        return $query;
    }
    protected function wrap(string $identifier): string
    {
        if (! $this->isSafeIdentifier($identifier)) {
            throw new InvalidArgumentException("Unsafe SQL identifier [{$identifier}].");
        }
        return $this->database->connection()->getQueryGrammar()->wrap($identifier);
    }
    private function isSafeIdentifier(string $identifier, bool $allowQualified = false): bool
    {
        $pattern = $allowQualified
            ? '/^[A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z_][A-Za-z0-9_]*)*$/'
            : '/^[A-Za-z_][A-Za-z0-9_]*$/';
        return preg_match($pattern, $identifier) === 1;
    }
}
