<?php
/** Global constants (BASE_PATH, BASE_URL, upload dirs). */

if (!defined('BASE_PATH')) {
    // public/index.php -> one level up is the project root.
    define('BASE_PATH', realpath(__DIR__ . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR);
}

if (!defined('BASE_URL')) {
    // "/" on a Virtual Host, else the public sub-folder path.
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $pubDir  = rtrim(str_replace('\\', '/', realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'public') ?: ''), '/');

    if ($docRoot !== '' && $pubDir !== '' && str_starts_with($pubDir, $docRoot)) {
        $rel = substr($pubDir, strlen($docRoot));
        $base = ($rel === '') ? '/' : $rel . '/';
    } else {
        // Fallback: derive the base from SCRIPT_NAME.
        $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/index.php';
        $scriptName = '/' . ltrim(str_replace('\\', '/', $scriptName), '/');
        $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        $base = ($dir === '' || $dir === '/') ? '/' : $dir . '/';
    }
    define('BASE_URL', $base);
}

if (!defined('UPLOAD_BARCODE_DIR')) {
    define('UPLOAD_BARCODE_DIR', BASE_PATH . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'barcodes' . DIRECTORY_SEPARATOR);
}
if (!defined('UPLOAD_SIGNATURE_DIR')) {
    define('UPLOAD_SIGNATURE_DIR', BASE_PATH . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'signatures' . DIRECTORY_SEPARATOR);
}
if (!defined('UPLOAD_BARCODE_URL')) {
    define('UPLOAD_BARCODE_URL', BASE_URL . 'uploads/barcodes/');
}
