<?php
/**
 * 调试 API 文件
 * 直接输出错误信息
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// 关闭输出缓冲
while (ob_get_level()) {
    ob_end_clean();
}

echo json_encode([
    'status' => 'ok',
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'script_filename' => __FILE__,
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
    'request_method' => $_SERVER['REQUEST_METHOD'],
]);

// 测试文件访问
$testFile = __DIR__ . '/config/database.php';
echo json_encode([
    'file_exists' => file_exists($testFile),
    'test_file' => $testFile,
    'dir_contents' => scandir(__DIR__),
]);
