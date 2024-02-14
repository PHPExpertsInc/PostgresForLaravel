<?php

namespace PHPExperts\PostgresForLaravel;

use Illuminate\Support\Facades\DB;

abstract class PostgresMigrationHelper
{
    public const DEFAULT_TIMESTAMPS = 6;
    public const ONLY_CREATED_AT = 2;
    public const ONLY_UPDATED_AT = 3;
    public const CUSTOM_COLUMN = 4;

    private static function checkIfSetTimestampFunctionExists(): bool
    {
        $sql = <<<SQL
        SELECT EXISTS (
            SELECT 1
            FROM pg_proc
            WHERE proname = 'trigger_set_timestamp'
        );
        SQL;
        //dump(str_replace("\n", ' ', $sql));
        $exists = DB::selectOne($sql)->exists ?? false;

        return $exists;
    }

    private static function createAutoUpdatedTimestampFunction(string $column = 'updated_at'): void
    {
        if (self::checkIfSetTimestampFunctionExists() === true) {
            return;
        }

        $sql = <<<SQL
            CREATE FUNCTION trigger_set_timestamp()
            RETURNS TRIGGER AS $$
            BEGIN
              NEW.{$column} = NOW();
            RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL;

         DB::unprepared($sql);
    }

    /**
     * @param array|string $tables
     */
    public static function addPostgresTimestamps(array|string $tables, int $createMode = self::DEFAULT_TIMESTAMPS): void
    {
        if (is_string($tables)) {
            $tables = [$tables];
        }

        DB::transaction(function () use ($tables, $createMode) {
            self::createAutoUpdatedTimestampFunction();

            foreach ($tables as $table) {
                $column = null;
                if (is_array($table)) {
                    $table[2] ??= null;
                    [$table, $createMode, $column] = $table;
                }

                if ($createMode === self::CUSTOM_COLUMN) {
                    if (!$column) {
                        throw new \InvalidArgumentException("The custom column name can't be NULL.");
                    }

                    self::addPostgresTimestamp($table, $column);
                    continue;
                }

                if ($createMode % self::ONLY_CREATED_AT === 0) {
                    self::addPostgresTimestamp($table, 'created_at');
                }

                if ($createMode % self::ONLY_UPDATED_AT === 0) {
                    self::addPostgresTimestamp($table, 'updated_at');
                }
            }
        });
    }

    /**
     * @param array|string $tables
     */
    public static function revertToDefaultEloquentTimestamps(array|string $tables, int $createMode = self::DEFAULT_TIMESTAMPS): void
    {
        if (is_string($tables)) {
            $tables = [$tables];
        }

        DB::transaction(function () use ($tables, $createMode) {
            foreach ($tables as $table) {
                if (is_array($table)) {
                    $table[2] ??= null;
                    [$table, $createMode, $column] = $table;
                }

                if ($createMode === self::CUSTOM_COLUMN) {
                    if (!$column) {
                        throw new \InvalidArgumentException("The custom column name can't be NULL.");
                    }

                    self::revertToDefaultEloquentTimestamp($table, $column);
                    continue;
                }

                if ($createMode % self::ONLY_CREATED_AT === 0) {
                    self::revertToDefaultEloquentTimestamp($table, 'created_at');
                }

                if ($createMode % self::ONLY_UPDATED_AT === 0) {
                    self::revertToDefaultEloquentTimestamp($table, 'updated_at');
                }
            }
        });
    }

    public static function addPostgresTimestamp(string $table, string $column = null): void
    {
        $sql = <<<SQL
        ALTER TABLE "$table"
            ALTER COLUMN "$column" TYPE timestamp WITH TIME ZONE,
            ALTER COLUMN "$column" SET NOT NULL,
            ALTER COLUMN "$column" SET DEFAULT NOW();
        SQL;

        DB::unprepared($sql);

        if ($column === 'updated_at') {
            $sql = <<<SQL
            CREATE TRIGGER set_timestamp
            BEFORE
            UPDATE ON "$table"
            FOR EACH ROW
            EXECUTE PROCEDURE trigger_set_timestamp();
            SQL;

            DB::unprepared($sql);
        }
    }

    public static function revertToDefaultEloquentTimestamp(string $table, string $column): void
    {
        DB::transaction(function () use ($table, $column) {
            $sql = <<<SQL
            ALTER TABLE "$table"
                ALTER COLUMN "$column" TYPE timestamp(0) WITHOUT TIME ZONE USING "$column" AT TIME ZONE 'UTC',
                ALTER COLUMN "$column" DROP NOT NULL,
                ALTER COLUMN "$column" DROP DEFAULT;
            SQL;

            DB::unprepared($sql);

            if ($column === 'updated_at') {
                DB::unprepared("DROP TRIGGER IF EXISTS set_timestamp ON $table;");
            }
        });
    }
}
