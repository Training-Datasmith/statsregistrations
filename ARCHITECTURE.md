# Architecture: statsregistrations

## Purpose

A PrestaShop statistics module that tracks new customer registrations over time and presents them as a time-series chart on the admin dashboard.

## Directory Structure

```
statsregistrations.php   - Module class (ModuleGraph subclass); all business logic
upgrade/                 - Migration scripts
tests/                   - PHPUnit test stubs and PHPStan bootstrap
translations/            - Locale string overrides
```

## Key Design Decisions

- **ModuleGraph inheritance**: Renders a line chart of daily/weekly/monthly registrations.
- **Date-range driven**: Uses the parent `getDate()` and `getLang()` helpers.

## Extension Points

- Override `getData()` to split registrations by source channel or country.

## Dependency Flow

```
statsregistrations (ModuleGraph)
  └─> hookDisplayAdminStatsModules() — renders the registration chart
  └─> getData()                      — registration count query grouped by period
        └─> Db::getInstance()
```
