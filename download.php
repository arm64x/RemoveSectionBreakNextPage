<?php
/**
 * DOWNLOAD.PHP
 * Download file đã sửa và tự động xóa files
 */

define('TEMP_DIR', 'temp/');
define('UPLOAD_DIR', 'uploads/');

try {
    if (!isset($_GET['file_id'])) {
        throw new Exception('Missing file_id');
    }

    $fileId = $_GET['file_id'];
    
    // Load analysis data
    $analysisFile = TEMP_DIR . $fileId . '_analysis.json';
    
    if (!file_exists($analysisFile)) {
        throw new Exception('File not found or already deleted');
    }

    $analysisData = json_decode(file_get_contents($analysisFile), true);
    
    // Check if fixed file exists
    if (!isset($analysisData['fixed_path']) || !file_exists($analysisData['fixed_path'])) {
        throw new Exception('Fixed file not found');
    }

    $fixedPath = $analysisData['fixed_path'];
    $fixedName = $analysisData['fixed_name'];

    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $fixedName . '"');
    header('Content-Length: ' . filesize($fixedPath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Output file
    readfile($fixedPath);

    // Cleanup after download
    cleanup($fileId, $analysisData);

    exit;

} catch (Exception $e) {
    header('HTTP/1.1 404 Not Found');
    echo 'Error: ' . $e->getMessage();
    exit;
}

function cleanup($fileId, $analysisData) {
    // Delete original uploaded file
    if (isset($analysisData['upload_path']) && file_exists($analysisData['upload_path'])) {
        unlink($analysisData['upload_path']);
    }

    // Delete fixed file
    if (isset($analysisData['fixed_path']) && file_exists($analysisData['fixed_path'])) {
        unlink($analysisData['fixed_path']);
    }

    // Delete unpacked directory
    if (isset($analysisData['unpack_dir']) && is_dir($analysisData['unpack_dir'])) {
        deleteDirectory($analysisData['unpack_dir']);
    }

    // Delete analysis file
    $analysisFile = TEMP_DIR . $fileId . '_analysis.json';
    if (file_exists($analysisFile)) {
        unlink($analysisFile);
    }
}

function deleteDirectory($dir) {
    if (!is_dir($dir)) return;
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }
    rmdir($dir);
}
?>