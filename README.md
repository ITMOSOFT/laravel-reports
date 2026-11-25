# ITMOSOFT Laravel Reports

Composable database-backed reporting and aggregation tools for Laravel 12 and 13.

## Features
- Order summary: order count, gross total, average order value and paid-order count.
- Daily sales aggregation.
- Customer registration count.
- Configurable source tables and column names.
- Inclusive date-range filtering with start/end validation.
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
Edit `config/itmosoft-reports.php` to match your schema. Defaults assume `orders.total`, `orders.status`, `orders.created_at`, and `users.created_at`.

## Usage
```php
use ItmoSoft\Reports\ReportsManager;
$reports = app(ReportsManager::class);
$summary = $reports->orderSummary(now()->startOfMonth(), now()->endOfMonth());
$daily = $reports->dailySales(now()->startOfMonth(), now()->endOfMonth());
$newCustomers = $reports->customerCount(now()->startOfMonth(), now()->endOfMonth());
```

Use `query()` for custom reports:
```php
$topProducts = $reports->query('order_items')
    ->select('product_id')
    ->selectRaw('SUM(quantity) as units_sold')
    ->groupBy('product_id')->orderByDesc('units_sold')->limit(10)->get();
```
`query()` accepts simple or schema-qualified identifiers such as `reporting.order_items`; SQL fragments, aliases and whitespace are rejected.

## Configuration
- `orders.table`, `id`, `customer_id`, `total`, `status`, `created_at`, `paid_statuses`
- `customers.table`, `id`, `created_at`

The configured `id` and `customer_id` values are retained for schema documentation and future application-level composition; built-in aggregates currently use only the fields needed for their calculations.

## Important scope and production notes
This package is a small reporting query layer, not a full analytics warehouse or dashboard. It does not create report tables, cache results, schedule exports, render charts, or provide an admin UI.
- `gross_total` sums all matching orders regardless of status. It is not net revenue and does not subtract refunds, discounts, tax, shipping, or chargebacks.
- `paid_orders` counts only configured `paid_statuses`; it does not change gross total calculation.
- Daily grouping uses database `DATE()` and database/session timezone. Normalize timezone handling in your application.
- Date filters are inclusive and compare supplied timestamps as-is. A start date later than the end date is rejected.
- Add tenant/authorization constraints before exposing report output. Built-in methods are global to configured source tables.
- Configure table and column names to match your schema. Aggregate identifiers are validated before use in raw SQL expressions.
- For large datasets, add indexes to date/status columns and consider read replicas, pre-aggregations or caching.
- Currency conversion and mixed-currency aggregation are not performed.
- Database query semantics and performance vary by driver.

## Testing
```bash
composer install
composer validate --strict
composer test
```
MIT. See [LICENSE](LICENSE).
