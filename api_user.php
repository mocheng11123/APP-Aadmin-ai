<?php
/**
 * 用户相关 API - 简化版本
 */
header('Content-Type: application/json; charset=utf-8');

// 关闭输出缓冲
while (ob_get_level()) {
    ob_end_clean();
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// 获取 JSON 输入
function getJsonInput() {
    $input = file_getcontents('php://input');
    if (!$input) return [];
    $data = json_decode($input, true);
    return $data ?: [];
}

// 输出 JSON
function outputJson($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 简单的注册逻辑
if ($action === 'register' && $method === 'POST') {
    $data = getJsonInput();
    
    if (empty($data['username'])) {
        outputJson(['code' => 400, 'message' => '用户名不能为空']);
    }
    if (empty($data['password'])) {
        outputJson(['code' => 400, 'message' => '密码不能为空']);
    }
    if (strlen($data['password']) < 6) {
        outputJson(['code' => 400, 'message' => '密码长度至少 6 位']);
    }
    
    $username = trim($data['username']);
    $password = $data['password'];
    
    try {
        // 检查配置文件
        $configFile = __DIR__ . '/config/database.php';
        if (!file_exists($configFile)) {
            outputJson(['code' => 500, 'message' => '配置文件不存在']);
        }
        $config = require $configFile;
        
        // 数据库连接
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 检查用户名是否存在
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            outputJson(['code' => 400, 'message' => '用户名已存在']);
        }
        
        // 创建用户
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $hashedPassword]);
        
        outputJson(['code' => 200, 'message' => '注册成功']);
        
    } catch (Exception $e) {
        outputJson(['code' => 500, 'message' => '错误：' . $e->getMessage()]);
    }
}

// 简单的登录逻辑
if ($action === 'login' && $method === 'POST') {
    $data = getJsonInput();
    
    if (empty($data['username'])) {
        outputJson(['code' => 400, 'message' => '用户名不能为空']);
    }
    if (empty($data['password'])) {
        outputJson(['code' => 400, 'message' => '密码不能为空']);
    }
    
    $username = trim($data['username']);
    $password = $data['password'];
    
    try {
        $config = require __DIR__ . '/config/database.php';
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($password, $user['password'])) {
            outputJson(['code' => 400, 'message' => '用户名或密码错误']);
        }
        
        // 生成 token
        $token = bin2hex(random_bytes(32));
        $expire = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $stmt = $pdo->prepare("UPDATE users SET token = ?, token_expire = ? WHERE id = ?");
        $stmt->execute([$token, $expire, $user['id']]);
        
        // 获取应用列表
        $stmt = $pdo->prepare("
            SELECT a.app_uuid, a.app_name, r.role 
            FROM apps a 
            INNER JOIN user_app_relations r ON a.id = r.app_id 
            WHERE r.user_id = ?
        ");
        $stmt->execute([$user['id']]);
        $apps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        outputJson([
            'code' => 200,
            'message' => '登录成功',
            'data' => [
                'token' => $token,
                'expire' => 2592000,
                'user_id' => $user['id'],
                'username' => $user['username'],
                'apps' => $apps,
            ]
        ]);
        
    } catch (Exception $e) {
        outputJson(['code' => 500, 'message' => '错误：' . $e->getMessage()]);
    }
}

// 默认响应
outputJson(['code' => 404, 'message' => "未知操作：{$action} {$method}"]);
