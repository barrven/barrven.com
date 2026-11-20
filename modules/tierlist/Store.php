<?php

declare(strict_types=1);

namespace Modules\Tierlist;

use Core\Db;
use Core\HttpException;
use PDO;

/**
 * Data access for the tier list module. Every method that takes a list ID
 * also takes the current user's ID and only touches lists that user owns.
 */
final class Store
{
    public const TIERS = ['S', 'A', 'B', 'C', 'D'];   // 'L' (unranked) is stored as NULL

    private PDO $db;

    public function __construct()
    {
        $this->db = Db::module('tierlist');
    }

    // ---- Users ----

    public function findUserByName(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT user_id, username FROM users WHERE username = ?');
        $stmt->execute([$username]);

        return $stmt->fetch() ?: null;
    }

    public function createUser(string $username): int
    {
        // home_directory is a legacy NOT NULL column; files are stored by user ID now
        $stmt = $this->db->prepare("INSERT INTO users (username, home_directory) VALUES (?, '')");
        $stmt->execute([$username]);

        return (int) $this->db->lastInsertId();
    }

    public static function uploadDir(int $userId): string
    {
        return STORAGE_ROOT . '/uploads/tierlist/' . $userId;
    }

    // ---- Lists ----

    public function listsForUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT list_id, list_name FROM lists WHERE user_id = ? ORDER BY created_at, list_id');
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    /** The list if this user owns it; otherwise a 404 (same answer whether it exists or not). */
    public function ownedList(int $listId, int $userId): array
    {
        $stmt = $this->db->prepare('SELECT list_id, list_name FROM lists WHERE list_id = ? AND user_id = ?');
        $stmt->execute([$listId, $userId]);
        $list = $stmt->fetch();

        if (!$list) {
            throw new HttpException(404, 'List not found');
        }

        return $list;
    }

    public function createList(int $userId, string $name): int
    {
        $stmt = $this->db->prepare('INSERT INTO lists (user_id, list_name) VALUES (?, ?)');
        $stmt->execute([$userId, $name]);

        return (int) $this->db->lastInsertId();
    }

    /** Deletes the list, its file records (via ON DELETE CASCADE) and the files on disk. */
    public function deleteList(int $listId, int $userId): void
    {
        $this->ownedList($listId, $userId);
        $files = $this->fileNames($listId);

        $stmt = $this->db->prepare('DELETE FROM lists WHERE list_id = ? AND user_id = ?');
        $stmt->execute([$listId, $userId]);

        $this->unlinkFiles($userId, $files);
    }

    // ---- Files ----

    public function addFile(int $listId, string $fileName): void
    {
        $stmt = $this->db->prepare('INSERT INTO list_files (list_id, file_name) VALUES (?, ?)');
        $stmt->execute([$listId, $fileName]);
    }

    /** @return list<string> */
    public function fileNames(int $listId): array
    {
        $stmt = $this->db->prepare('SELECT file_name FROM list_files WHERE list_id = ?');
        $stmt->execute([$listId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /** Removes the named files from an owned list and from disk. Returns how many were removed. */
    public function deleteFiles(int $listId, int $userId, array $fileNames): int
    {
        $this->ownedList($listId, $userId);
        $fileNames = array_values(array_intersect($this->fileNames($listId), $fileNames));
        if ($fileNames === []) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($fileNames), '?'));
        $stmt = $this->db->prepare("DELETE FROM list_files WHERE list_id = ? AND file_name IN ($placeholders)");
        $stmt->execute([$listId, ...$fileNames]);

        $this->unlinkFiles($userId, $fileNames);

        return $stmt->rowCount();
    }

    /** Whether this user has a file with this name in any of their lists. */
    public function userOwnsFile(int $userId, string $fileName): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM list_files f JOIN lists l ON l.list_id = f.list_id WHERE l.user_id = ? AND f.file_name = ? LIMIT 1'
        );
        $stmt->execute([$userId, $fileName]);

        return (bool) $stmt->fetchColumn();
    }

    // ---- Tiers ----

    /** @return array{S: list<string>, A: list<string>, B: list<string>, C: list<string>, D: list<string>, L: list<string>} */
    public function tierData(int $listId): array
    {
        $tiers = array_fill_keys([...self::TIERS, 'L'], []);

        $stmt = $this->db->prepare('SELECT file_name, file_tier FROM list_files WHERE list_id = ? ORDER BY file_id');
        $stmt->execute([$listId]);

        foreach ($stmt->fetchAll() as $row) {
            $tier = in_array($row['file_tier'], self::TIERS, true) ? $row['file_tier'] : 'L';
            $tiers[$tier][] = $row['file_name'];
        }

        return $tiers;
    }

    /** @param array<string, list<string>> $tierData tier letter => file names */
    public function saveTiers(int $listId, array $tierData): int
    {
        $stmt = $this->db->prepare('UPDATE list_files SET file_tier = ? WHERE list_id = ? AND file_name = ?');
        $updated = 0;

        $this->db->beginTransaction();
        try {
            foreach ($tierData as $tier => $fileNames) {
                $value = in_array($tier, self::TIERS, true) ? $tier : null;
                foreach ($fileNames as $fileName) {
                    $stmt->execute([$value, $listId, $fileName]);
                    $updated += $stmt->rowCount();
                }
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return $updated;
    }

    public function resetTiers(int $listId): void
    {
        $stmt = $this->db->prepare('UPDATE list_files SET file_tier = NULL WHERE list_id = ?');
        $stmt->execute([$listId]);
    }

    private function unlinkFiles(int $userId, array $fileNames): void
    {
        $dir = self::uploadDir($userId);
        foreach ($fileNames as $fileName) {
            $path = $dir . '/' . basename($fileName);
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
