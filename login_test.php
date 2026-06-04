<?php
/**
 * 直接测试登录接口（不经过路由）
 */
header('Content-Type: application/json; charset=utf-8');

// 只接受 POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['code' => 404, 'message' => '请使用 POST 方法']);
    exit;
}

// 获取请求体
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['code' => 400, 'message' => '无效的 JSON 数据']);
    exit;
}

// 加载必要的类
require_once __DIR__ . '/core/DB.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/utils/Response.php';
require_once __DIR__ . '/utils/Validator.php';
require_once __DIR__ . '/models/UserModel.php';

try {
    // 验证输入
    if ($error = Validator::required($data['username'] ?? null, '用户名')) {
        echo json_encode(['code' => 400, 'message' => $error]);
        exit;
    }
    if ($error = Validator::required($data['password'] ?? null, '密码')) {
        echo json_encode(['code' => 400, 'message' => $error]);
        exit;
    }
    
    $username = trim($data['username']);
    $password = $data['password'];
    
    // 查找用户
    $userModel = new UserModel();
    $user = $userModel->findByUsername($username);
    
    if (!$user) {
        echo json_encode(['code' => 400, 'message' => '用户名或密码错误']);
        exit;
    }
    
    // 验证密码
    if (!$userModel->verifyPassword($password, $user['password'])) {
        echo json_encode(['code' => 400, 'message' => '用户名或密码错误']);
        exit;
    }
    
    // 生成 Token
    $auth = new Auth();
    $token = $auth->generateToken($user['id']);
    
    // 获取用户关联的应用列表
    $apps = $userModel->getUserApps($user['id']);
    
    $config = require __DIR__ . '/config/app.php';
    $expireSeconds = $config['token_expire_days'] * 86400;
    
    echo json_encode([
        'code' => 200,
        'message' => '登录成功',
        'data' => [
            'token' => $token,
            'expire' => $expireSeconds,
            'user_id' => $user['id'],
            'username' => $user['username'],
            'apps' => $apps,
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'code' => 500,
        'message' => '服务器错误：' . $e->getMessage(),
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
}
