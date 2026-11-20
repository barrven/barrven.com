<?php

declare(strict_types=1);

namespace Core;

/**
 * Validated file uploads. The file type is decided by reading the file's
 * contents (finfo), never by its name or the browser's claim, and stored
 * files get a random name with an extension chosen by us. Store uploads
 * under storage/ (not reachable from the web) and serve them through a
 * route that checks who is asking.
 */
final class Upload
{
    /** Formats every current browser can display. SVG is left out on purpose: it can contain scripts. */
    public const MEDIA_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
        'image/bmp' => 'bmp',
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/quicktime' => 'mov',
        'video/ogg' => 'ogv',
        'audio/ogg' => 'ogg',
    ];

    /**
     * Turn PHP's $_FILES['field'] (one or many files) into a list of
     * ['name', 'tmp_name', 'error', 'size'] entries.
     */
    public static function normalize(mixed $field): array
    {
        if (!is_array($field) || !isset($field['tmp_name'])) {
            return [];
        }

        if (!is_array($field['tmp_name'])) {
            return [$field];
        }

        $files = [];
        foreach (array_keys($field['tmp_name']) as $i) {
            $files[] = [
                'name' => (string) ($field['name'][$i] ?? ''),
                'tmp_name' => (string) $field['tmp_name'][$i],
                'error' => (int) $field['error'][$i],
                'size' => (int) $field['size'][$i],
            ];
        }

        return $files;
    }

    /**
     * Move one uploaded file into $directory if its real type is in
     * $allowedTypes (mime => extension). Returns the stored file name, or
     * null if the file was rejected.
     */
    public static function store(array $file, string $directory, array $allowedTypes = self::MEDIA_TYPES): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }

        $mime = self::detectType($file['tmp_name']);
        if ($mime === null || !isset($allowedTypes[$mime])) {
            return null;
        }

        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new \RuntimeException("Could not create upload directory $directory");
        }

        $name = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) {
            return null;
        }
        chmod($directory . '/' . $name, 0640);

        return $name;
    }

    public static function detectType(string $path): ?string
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);

        return is_string($mime) ? $mime : null;
    }
}
