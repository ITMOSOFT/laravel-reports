# Changelog

## [1.0.1] - 2026-11-25
- Validate required report source settings and SQL identifiers before aggregate queries.
- Validate `paid_statuses` as an array of strings or integers.
- Restrict the custom query-builder table argument to simple or schema-qualified identifiers.
- Reject reversed date ranges.
- Added regression tests for invalid identifiers, paid-status configuration, and date-range boundaries.

## [1.0.0] - 2026-11-14
- Initial release.
- Added order summary, daily sales and customer count reports.
- Added configurable report data sources and date-range filtering.
- Added a composable query-builder entry point.
- Added Laravel 12/13 Testbench CI matrix.
