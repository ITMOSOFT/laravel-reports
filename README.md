# ITMOSOFT Laravel Reports

Composable database-backed reporting and aggregation tools for Laravel 12 and 13.

## Features

- Order summary: order count, gross total, average order value and paid-order count.
- Daily sales aggregation.
- Customer registration count.
- Configurable source tables and column names.
- Date-range filtering.
- A query-builder entry point for application-defined reports.
- Laravel service-container integration and publishable configuration.

## Requirements

- PHP 8.3+
- Laravel 12 or 13

## Installation

```bash
composer require itmosoft/laravel-reports
php artisan vendor:publish --tag=itmosoft-reports-config
```

Edit `config/itmosoft-reports.php` to match your schema. The defaults assume an `orders` table with `total`, `status`, `created_at`, and a `users` table with `created_at`.

## Usage

```php
use ItmoSoft\Reports\ReportsManager;

$reports = app(ReportsManager::class);

$summary = $reports->orderSummary(
    now()->startOfMonth(),
    now()->endOfMonth(),
);

$daily = $reports->dailySales(
    now()->startOfMonth(),
    now()->endOfMonth(),
);

$newCustomers = $reports->customerCount(
    now()->startOfMonth(),
    now()->endOfMonth(),
);
```

Example order summary:

```php
[
    'orders' => 125,
    'gross_total' => 24850.00,
    'average_order_value' => 198.80,
    'paid_orders' => 109,
]
```

Use `query()` for custom reports:

```php
$topProducts = $reports->query('order_items')
    ->select('product_id')
    ->selectRaw('SUM(quantity) as units_sold')
    ->groupBy('product_id')
    ->orderByDesc('units_sold')
    ->limit(10)
    ->get();
```

## Configuration

The published config supports these keys:

- `orders.table`, `id`, `customer_id`, `total`, `status`, `created_at`, `paid_statuses`
- `customers.table`, `id`, `created_at`

The configured `id` and `customer_id` values are available for schema documentation and future application-level query composition; the built-in aggregate methods currently use only the fields required by their calculations.

## Important scope and production notes

This package is a small reporting query layer, not a full analytics warehouse or dashboard. It does not create report tables, cache results, schedule exports, render charts, or provide an admin UI.

- `gross_total` sums all matching orders regardless of status. It is not net revenue and does not subtract refunds, discounts, tax, shipping, or chargebacks.
- `paid_orders` counts only configured `paid_statuses`; it does not change the gross total calculation.
- Daily grouping uses the database `DATE()` function and the database/session timezone. Normalize timezone handling in your application.
- Date filters are inclusive and compare the supplied timestamps as-is.
- Add tenant/authorization constraints before exposing report output. Built-in methods are global to the configured source tables.
- Configure the table and column names to match your own schema. SQL identifier validation is applied to aggregate identifiers.
- For large datasets, add indexes to date/status columns and consider read replicas, pre-aggregations or caching in the host application.
- Currency conversion and mixed-currency aggregation are not performed. Do not sum amounts from different currencies as though they were one currency.
- Database query semantics and performance vary by driver.

## Testing

```bash
composer install
composer validate --strict
composer test
```

## License

MIT. See [LICENSE](LICENSE).
