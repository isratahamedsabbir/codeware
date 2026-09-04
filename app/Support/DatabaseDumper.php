<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Writes a plain-SQL dump of the current MySQL database — schema (DROP + CREATE TABLE)
 * and data (INSERT statements, chunked) — without shelling out to `mysqldump`, since
 * this admin panel is reused across projects/hosts where that binary may not be on PATH.
 */
class DatabaseDumper
{
    public static function dumpTo(string $path): void
    {
        $handle = fopen($path, 'w');

        $database = config('database.connections.mysql.database');

        fwrite($handle, "-- Database dump for `{$database}`\n-- Generated at ".now()->toDateTimeString()."\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach (self::tables() as $table) {
            self::writeTable($handle, $table);
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");

        fclose($handle);
    }

    /**
     * @return array<int, string>
     */
    protected static function tables(): array
    {
        return collect(DB::select('SHOW TABLES'))
            ->map(fn (object $row) => array_values((array) $row)[0])
            ->all();
    }

    /**
     * @param  resource  $handle
     */
    protected static function writeTable($handle, string $table): void
    {
        $createTable = DB::select("SHOW CREATE TABLE `{$table}`")[0]->{'Create Table'};

        fwrite($handle, "-- ----------------------------\n-- Table: {$table}\n-- ----------------------------\n\n");
        fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
        fwrite($handle, $createTable.";\n\n");

        $pdo = DB::connection()->getPdo();

        // Plain chunk() (offset-based) rather than chunkById(), since a few pivot
        // tables here have no single-column primary key for chunkById() to page by.
        DB::table($table)->orderBy(self::orderColumn($table))->chunk(500, function ($rows) use ($handle, $table, $pdo) {
            foreach ($rows as $row) {
                $data = (array) $row;
                $columns = implode(', ', array_map(fn (string $column) => "`{$column}`", array_keys($data)));
                $values = implode(', ', array_map(
                    fn (mixed $value) => is_null($value) ? 'NULL' : $pdo->quote((string) $value),
                    array_values($data)
                ));

                fwrite($handle, "INSERT INTO `{$table}` ({$columns}) VALUES ({$values});\n");
            }
        });

        fwrite($handle, "\n");
    }

    /**
     * A stable column to order by while chunking — most tables here use `id`, but a
     * few (pivot tables) don't, so fall back to the table's first column.
     */
    protected static function orderColumn(string $table): string
    {
        $columns = DB::select("SHOW COLUMNS FROM `{$table}`");

        foreach ($columns as $column) {
            if ($column->Key === 'PRI') {
                return $column->Field;
            }
        }

        return $columns[0]->Field;
    }
}
