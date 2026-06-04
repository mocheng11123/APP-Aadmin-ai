<?php
/**
 * 路由测试文件
 * 直接输出详细错误
 */
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

while (ob_get_level()) {
    ob_end_clean();
}

try {
    // 测试 1: 自动加载
    spl_autoload_register(function ($class) {
        $dirs = ['core', 'controllers', 'models', 'utils'];
        foreach ($dirs as $dir) {
            $file = __DIR__ . '/' . $dir . '/' . $class . '.php';
            if (!file_exists($file)) continue;
            require_once $file;
            return;
        }
    });
    
    // 测试 2: 创建 Router
    $router = new Router('/api');
    
    // 测试 3: 添加路由
    $router->post('/test', function() {
        return ['code' => 200, 'message' => '路由测试成功'];
    });
    
    // 测试 4: 输出结果
    echo json_encode([
        'status' => 'ok',
        'router_loaded' => true,
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'http_x_original' => $_SERVER['HTTP_X_ORIGINAL_URL'] ?? 'not set',
        'path_info' => $_SERVER['PATH_INFO'] ?? 'not set',
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ], JSON_UNESCAPED_UNICODE);
}
