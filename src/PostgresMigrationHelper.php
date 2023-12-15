<?php

namespace PHPExperts\PostgresForLaravel;

use Illuminate\Support\Facades\DB;

abstract class PostgresMigrationHelper
{
    /**
     * @param array|string $tables
     */
    public static function addPostgresTimestamps($tables): void
    {
        if (is_string($tables)) {
            $tables = [$tables];
        }

        DB::transaction(function () use ($tables) {
            foreach ($tables as $table) {
                self::addPostgresTimestampsWorker($table);
            }
        });
    }

    /**
     * @param array|string $tables
     */
    public static function dropPostgresTimestamps($tables): void
    {
        if (is_string($tables)) {
            $tables = [$tables];
        }

        DB::transaction(function () use ($tables) {
            foreach ($tables as $table) {
                $sql = <<<SQL
                ALTER TABLE $table ALTER COLUMN created_at SET DEFAULT NULL, 
                                   ALTER COLUMN updated_at SET DEFAULT NULL;
                SQL;
                DB::statement($sql);
            }
        });
    }

    private static function addPostgresTimestampsWorker(string $table): void
    {
        $sql = <<<SQL
        ALTER TABLE "$table" ALTER COLUMN created_at SET DEFAULT NOW(),
                             ALTER COLUMN updated_at SET DEFAULT NOW();
        SQL;
        DB::unprepared($sql);


        $sql = <<<SQL
        CREATE FUNCTION trigger_set_timestamp()
        RETURNS TRIGGER AS $$
        BEGIN
          NEW.updated_at = NOW();
        RETURN NEW;
        END;
        $$ LANGUAGE plpgsql;
        
        CREATE TRIGGER set_timestamp
        BEFORE
        UPDATE ON $table
        FOR EACH ROW
        EXECUTE PROCEDURE trigger_set_timestamp();
        SQL;

        DB::unprepared($sql);
    }
}
