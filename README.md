# Postgres For Laravel Library

[![TravisCI]()]()
[![Maintainability]()]()
[![Test Coverage]()]()

Postgres For Laravel is a PHP Experts, Inc., Project meant to ease the use of the PostgreSQL database in Laravel.

## Installation

Via Composer

```bash
composer require phpexperts/postgres-for-laravel
```

## Usage

The library should be ready to be used immediately after including via composer.

### PostgreSQL Timestamps

Postgres' timestamp support is extremely suprior to MySQL's. Yet, Laravel only supports the dumbed-down timestamps
by default. For best performance -including- both timezone-aware and millisecond resolution timestamps, it is best
to let POstgres itself handle every table's timestamps. To do this, do the following:

**Automatic Autowiring**

1. Extend every model from PHPExperts\PostgresForLaravel\PostgresModel.
2. Run `./artisan migrate`

**Manual Wiring** 

1. Add `public $timestamps = false;` to your Model.
2. Create a new migration: `./artisan make:migration use_native_postgres_timestamps`
3. Add the following code to the migration:

```php
<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use PHPExperts\PostgresForLaravel\PostgresMigrationHelper;

class UseNativePostgresTimestamps extends Migration
{
    private const TABLES = [
        'packages',
    ];

    public function up(): void
    {
        PostgresMigrationHelper::addPostgresTimestamps(static::TABLES);
    }

    public function down(): void
    {
        PostgresMigrationHelper::dropPostgresTimestamps(static::TABLES);
    }
}
```

## Use cases

 ✔ Use PostgreSQL native timestamp generation code.  

## Testing

```bash
phpunit --testdox
```

## Contributors

[Theodore R. Smith](https://www.phpexperts.pro/]) <theodore@phpexperts.pro>  
GPG Fingerprint: 4BF8 2613 1C34 87AC D28F  2AD8 EB24 A91D D612 5690  
CEO: PHP Experts, Inc.

## License

MIT license. Please see the [license file](LICENSE) for more information.
:wq
