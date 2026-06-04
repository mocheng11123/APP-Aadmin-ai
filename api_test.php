<?php
/**
 * 简单测试 API 文件
 * 用于测试服务器环境是否正常
 */
header('Content-Type: application/json; charset=utf-8');

// 测试直接访问
if (!isset($_GET['test'])) {
    echo json_encode(['status' => 'ok', 'message' => 'PHP 正常执行', 'method' => $_SERVER['REQUEST_METHOD']]);
    exit;
}

// 测试数据库连接
try {
    $config = [
        'host' => 'localhost',
        'dbname' => 'sqldaytime123',
        'username' => 'daytime123',
        'password' => 'admin192412',
        'charset' => 'utf8mb4',
    ];
    
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    
    echo json_encode([
        'status' => 'ok',
        'message' => '数据库连接成功',
        'pdo_version' => phpversion('pdo_mysql'),
        'tables' => $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
