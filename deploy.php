<?php
/**
 * 数据库部署检查工具
 * 访问此页面自动检查并创建数据库表
 */
header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/config/database.php';

try {
    // 连接数据库
    $dsn = "mysql:host={$config['host']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 检查数据库是否存在
    $databaseExists = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$config['dbname']}'")->fetch();
    
    if (!$databaseExists) {
        // 创建数据库
        $pdo->exec("CREATE DATABASE `{$config['dbname']}` DEFAULT CHARACTER SET utf8mb4");
        echo json_encode(['step' => 1, 'message' => '数据库已创建'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 选择数据库
    $pdo->exec("USE `{$config['dbname']}`");
    
    // 检查表是否存在
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) >= 7) {
        echo json_encode([
            'status' => 'ok',
            'message' => '数据库部署完成！',
            'tables' => $tables,
            'next' => '请访问 /frontend/index.html 使用管理后台'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 读取并执行 SQL 文件
    $sqlFile = __DIR__ . '/install.sql';
    if (!file_exists($sqlFile)) {
        echo json_encode(['status' => 'error', 'message' => 'install.sql 文件不存在']);
        exit;
    }
    
    $sql = file_get_contents($sqlFile);
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) continue;
        try {
            $pdo->exec($statement);
        } catch (Exception $e) {
            // 忽略已存在的表错误
            if (strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }
    }
    
    echo json_encode([
        'status' => 'ok',
        'message' => '数据库表创建完成！',
        'tables' => $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN),
        'next' => '请访问 /frontend/index.html 使用管理后台'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'hint' => '请检查数据库配置 config/database.php'
    ]);
}
