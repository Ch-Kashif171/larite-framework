<?php

namespace Lumite\Support\Requests;

class RequestFiles
{
    public function get(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    public function all(): array
    {
        return $_FILES ?? [];
    }

    public function has(string $key): bool
    {
        return isset($_FILES[$key]) && !empty($_FILES[$key]['name']);
    }

    public function validate(
        array $file,
        array $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'],
        int $maxSize = 2097152 // 2MB
    ): bool|string {
        if (!isset($file['error']) || is_array($file['error'])) {
            return 'Invalid file parameters.';
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'File upload error.';
        }

        if ($file['size'] > $maxSize) {
            return 'File size exceeds limit.';
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $allowedTypes)) {
            return 'Invalid file type.';
        }

        return true;
    }

    public function move(array $file, string $destination): bool|string
    {
        $validation = $this->validate($file);
        
        if ($validation !== true) {
            return $validation;
        }

        if (!is_dir(dirname($destination))) {
            mkdir(dirname($destination), 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return true;
        }

        return 'Failed to move uploaded file.';
    }

    public function getExtension(array $file): string
    {
        return pathinfo($file['name'], PATHINFO_EXTENSION);
    }

    public function getMimeType(array $file): string|false
    {
        if (!isset($file['tmp_name'])) {
            return false;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($file['tmp_name']);
    }
}
