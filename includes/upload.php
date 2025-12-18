<?php
declare(strict_types=1);

function ensureUploadsDir(string $dir): void {
    if (!is_dir($dir)) mkdir($dir, 0777, true);
}

function uploadImage(string $inputName, string $uploadsDirAbs, string $prefix, int $maxBytes = 2_000_000): array {
    if (empty($_FILES[$inputName]['name'])) {
        return ['success' => false, 'error' => 'File tidak ada'];
    }

    $tmp = $_FILES[$inputName]['tmp_name'];
    $size = (int)($_FILES[$inputName]['size'] ?? 0);
    $ext = strtolower(pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];

    if ($size <= 0 || !is_uploaded_file($tmp)) return ['success' => false, 'error' => 'Upload tidak valid'];
    if ($size > $maxBytes) return ['success' => false, 'error' => 'Ukuran file terlalu besar'];
    if (!in_array($ext, $allowed, true)) return ['success' => false, 'error' => 'Format harus jpg/png/webp'];

    ensureUploadsDir($uploadsDirAbs);

    $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = rtrim($uploadsDirAbs, '/\\') . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmp, $dest)) {
        return ['success' => false, 'error' => 'Gagal memindahkan file'];
    }

    return ['success' => true, 'filename' => $filename];
}
