<?php

declare(strict_types=1);

namespace Core;

use PDO;

/**
 * One SQLite database per module, at storage/db/<module>.sqlite, so a bug
 * in one module can never read or damage another module's data.
 *
 * Schema changes go in modules/<module>/migrations/NNN_description.sql.
 * Each file runs once, in order; the number of the last one applied is kept
 * in the database's PRAGMA user_version.
 */
final class Db
{
    /** @var array<string, PDO> */
    private static array $connections = [];

    public static function module(string $module): PDO
    {
        if (isset(self::$connections[$module])) {
            return self::$connections[$module];
        }

        if (!preg_match('/^[a-z0-9_-]+$/', $module)) {
            throw new \InvalidArgumentException("Invalid module name: $module");
        }

        $pdo = new PDO('sqlite:' . STORAGE_ROOT . '/db/' . $module . '.sqlite', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 5000');

        self::migrate($pdo, $module);

        return self::$connections[$module] = $pdo;
    }

    private static function migrate(PDO $pdo, string $module): void
    {
        $files = glob(MODULES_ROOT . '/' . $module . '/migrations/*.sql') ?: [];
        sort($files, SORT_NATURAL);

        $current = (int) $pdo->query('PRAGMA user_version')->fetchColumn();

        foreach ($files as $file) {
            $number = (int) basename($file);
            if ($number <= $current) {
                continue;
            }

            $pdo->beginTransaction();
            try {
                $pdo->exec((string) file_get_contents($file));
                $pdo->exec('PRAGMA user_version = ' . $number);
                $pdo->commit();
            } catch (\Throwable $e) {
                $pdo->rollBack();
                throw new \RuntimeException("Migration failed: $module/" . basename($file), 0, $e);
            }

            $current = $number;
        }
    }
}
