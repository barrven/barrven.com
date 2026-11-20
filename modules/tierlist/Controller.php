<?php

declare(strict_types=1);

namespace Modules\Tierlist;

use Core\HttpException;
use Core\Log;
use Core\Request;
use Core\Response;
use Core\Security;
use Core\Upload;
use Core\View;

/**
 * Tier List Maker: pages (login, dashboard, list editor), the JSON API the
 * editor talks to, and serving uploaded files to their owner.
 *
 * Sign-in is username-only by design (no password): a username is a
 * workspace label, not an account. All list/file access is still limited
 * to the signed-in username's own lists.
 */
final class Controller
{
    private const STYLES = ['tierlist/tierlist.css'];

    private Store $store;

    public function __construct()
    {
        $this->store = new Store();
    }

    // ---------------------------------------------------------------------
    // Pages
    // ---------------------------------------------------------------------

    public function dashboard(): Response
    {
        $user = $this->currentUser();
        if ($user === null) {
            return Response::redirect('/tierlist/login');
        }

        return View::page('tierlist', 'dashboard', [
            'user' => $user,
            'lists' => $this->store->listsForUser($user['id']),
        ], [
            'title' => 'Tier List Maker',
            'styles' => self::STYLES,
            'scripts' => ['tierlist/dashboard.js'],
            'layout' => 'wide',
        ]);
    }

    public function loginForm(array $params = [], ?string $error = null, string $username = ''): Response
    {
        if ($this->currentUser() !== null) {
            return Response::redirect('/tierlist');
        }

        return View::page('tierlist', 'login', [
            'error' => $error,
            'username' => $username,
        ], [
            'title' => 'Tier List Maker',
            'styles' => self::STYLES,
        ]);
    }

    public function login(): Response
    {
        $username = Request::post('username');

        $error = $this->validateUsername($username);
        if ($error !== null) {
            $response = $this->loginForm([], $error, $username);
            $response->status = 422;
            return $response;
        }

        $user = $this->store->findUserByName($username);
        $userId = $user !== null ? (int) $user['user_id'] : $this->store->createUser($username);

        Security::regenerateSession();
        $_SESSION['tierlist_user'] = ['id' => $userId, 'username' => $user['username'] ?? $username];

        return Response::redirect('/tierlist');
    }

    public function logout(): Response
    {
        unset($_SESSION['tierlist_user']);
        Security::regenerateSession();

        return Response::redirect('/tierlist/login');
    }

    public function editor(array $params): Response
    {
        $user = $this->currentUser();
        if ($user === null) {
            return Response::redirect('/tierlist/login');
        }

        $list = $this->store->ownedList((int) $params['id'], $user['id']);

        return View::page('tierlist', 'editor', [
            'user' => $user,
            'list' => $list,
        ], [
            'title' => $list['list_name'],
            'styles' => self::STYLES,
            'scripts' => ['tierlist/editor.js'],
            'layout' => 'wide',
        ]);
    }

    // ---------------------------------------------------------------------
    // JSON API
    // ---------------------------------------------------------------------

    public function createList(): Response
    {
        $user = $this->requireUser();
        $this->guardUploadSize();

        $name = Request::post('listName');
        // 1-100 characters (not bytes); the mbstring extension isn't installed here
        if (!preg_match('/^.{1,100}$/us', $name)) {
            throw new HttpException(400, 'List name must be between 1 and 100 characters');
        }

        $listId = $this->store->createList($user['id'], $name);
        $result = $this->storeUploads($listId, $user['id']);

        return Response::json(['success' => true, 'list_id' => $listId] + $result);
    }

    public function deleteList(array $params): Response
    {
        $user = $this->requireUser();
        $this->store->deleteList((int) $params['id'], $user['id']);

        return Response::json(['success' => true]);
    }

    public function addFiles(array $params): Response
    {
        $user = $this->requireUser();
        $this->guardUploadSize();

        $list = $this->store->ownedList((int) $params['id'], $user['id']);
        $result = $this->storeUploads((int) $list['list_id'], $user['id']);

        return Response::json(['success' => true, 'list_id' => (int) $list['list_id']] + $result);
    }

    public function deleteFiles(array $params): Response
    {
        $user = $this->requireUser();
        $fileNames = Request::json()['filenames'] ?? null;

        if (!is_array($fileNames) || $fileNames === []) {
            throw new HttpException(400, 'filenames must be a non-empty array');
        }

        $fileNames = array_map(static fn ($name): string => basename((string) $name), $fileNames);
        $deleted = $this->store->deleteFiles((int) $params['id'], $user['id'], $fileNames);

        return Response::json(['success' => true, 'deleted' => $deleted]);
    }

    public function loadTiers(array $params): Response
    {
        $user = $this->requireUser();
        $list = $this->store->ownedList((int) $params['id'], $user['id']);

        return Response::json($this->store->tierData((int) $list['list_id']));
    }

    public function saveTiers(array $params): Response
    {
        $user = $this->requireUser();
        $list = $this->store->ownedList((int) $params['id'], $user['id']);

        $tierData = Request::json()['tierData'] ?? null;
        if (!is_array($tierData)) {
            throw new HttpException(400, 'tierData is required');
        }

        // Keep only known tiers, and reduce each entry to a bare file name
        $clean = [];
        foreach ([...Store::TIERS, 'L'] as $tier) {
            $entries = $tierData[$tier] ?? [];
            if (!is_array($entries)) {
                throw new HttpException(400, "Tier $tier must be an array");
            }
            $clean[$tier] = array_map(static fn ($path): string => basename((string) $path), $entries);
        }

        $updated = $this->store->saveTiers((int) $list['list_id'], $clean);

        return Response::json(['success' => true, 'updated' => $updated]);
    }

    public function resetTiers(array $params): Response
    {
        $user = $this->requireUser();
        $list = $this->store->ownedList((int) $params['id'], $user['id']);
        $this->store->resetTiers((int) $list['list_id']);

        return Response::json(['success' => true]);
    }

    // ---------------------------------------------------------------------
    // Uploaded files
    // ---------------------------------------------------------------------

    /** Streams one of the signed-in user's own uploads. */
    public function file(array $params): Response
    {
        $user = $this->currentUser();
        $name = basename($params['name']);

        if ($user === null || !$this->store->userOwnsFile($user['id'], $name)) {
            throw new HttpException(404, 'File not found');
        }

        $path = Store::uploadDir($user['id']) . '/' . $name;
        $mime = is_file($path) ? Upload::detectType($path) : null;

        // Only ever serve types a browser renders as plain media
        if ($mime === null || !isset(Upload::MEDIA_TYPES[$mime])) {
            throw new HttpException(404, 'File not found');
        }

        return Response::file($path, $mime, [
            'Cache-Control' => 'private, max-age=86400',
            'Content-Disposition' => 'inline',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    /** @return array{id: int, username: string}|null */
    private function currentUser(): ?array
    {
        $user = $_SESSION['tierlist_user'] ?? null;

        return is_array($user) && isset($user['id'], $user['username']) ? $user : null;
    }

    /** @return array{id: int, username: string} */
    private function requireUser(): array
    {
        return $this->currentUser() ?? throw new HttpException(401, 'Not signed in');
    }

    private function validateUsername(string $username): ?string
    {
        if (strlen($username) < 3 || strlen($username) > 30) {
            return 'Username must be between 3 and 30 characters.';
        }
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]*$/', $username)) {
            return 'Username can only contain letters, numbers, underscores, and hyphens, and must start with a letter or number.';
        }

        return null;
    }

    /**
     * When a request is bigger than PHP's post_max_size, PHP silently drops
     * the whole body. Report that instead of "no files".
     */
    private function guardUploadSize(): void
    {
        $length = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($length > 0 && $_POST === [] && $_FILES === []) {
            throw new HttpException(413, 'Upload too large. Try fewer or smaller files.');
        }
    }

    /** @return array{uploaded: list<string>, skipped: list<string>} */
    private function storeUploads(int $listId, int $userId): array
    {
        $uploaded = [];
        $skipped = [];
        $dir = Store::uploadDir($userId);

        foreach (Upload::normalize($_FILES['filesInput'] ?? null) as $file) {
            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $stored = Upload::store($file, $dir);
            if ($stored === null) {
                $skipped[] = $file['name'];
                Log::info("Rejected upload '{$file['name']}' (error {$file['error']}) for user $userId", 'tierlist');
                continue;
            }

            $this->store->addFile($listId, $stored);
            $uploaded[] = $stored;
        }

        return ['uploaded' => $uploaded, 'skipped' => $skipped];
    }
}
