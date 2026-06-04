<?php
/**
 * 用户相关 API
 */
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// 加载必要的类
require_once __DIR__ . '/core/DB.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/utils/Response.php';
require_once __DIR__ . '/utils/Validator.php';
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/models/AppModel.php';

try {
    switch ($action) {
        case 'login':
            handleLogin();
            break;
        case 'register':
            handleRegister();
            break;
        case 'register_sub':
            handleRegisterSub();
            break;
        case 'logout':
            handleLogout();
            break;
        case 'info':
            handleInfo();
            break;
        default:
            echo json_encode(['code' => 404, 'message' => '未知操作']);
    }
} catch (Exception $e) {
    echo json_encode([
        'code' => 500,
        'message' => '服务器错误：' . $e->getMessage(),
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
}

function getJsonInput() {
    $input = file_getcontents('php://input');
    return json_decode($input, true) ?: [];
}

function handleLogin() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['code' => 400, 'message' => '请使用 POST 方法']);
        return;
    }
    
    $data = getJsonInput();
    $userModel = new UserModel();
    
    // 验证
    if ($error = Validator::required($data['username'] ?? null, '用户名')) {
        echo json_encode(['code' => 400, 'message' => $error]); return;
    }
    if ($error = Validator::required($data['password'] ?? null, '密码')) {
        echo json_encode(['code' => 400, 'message' => $error]); return;
    }
    
    $user = $userModel->findByUsername(trim($data['username']));
    if (!$user || !$userModel->verifyPassword($data['password'], $user['password'])) {
        echo json_encode(['code' => 400, 'message' => '用户名或密码错误']);
        return;
    }
    
    $auth = new Auth();
    $token = $auth->generateToken($user['id']);
    $apps = $userModel->getUserApps($user['id']);
    $config = require __DIR__ . '/config/app.php';
    
    echo json_encode([
        'code' => 200,
        'message' => '登录成功',
        'data' => [
            'token' => $token,
            'expire' => $config['token_expire_days'] * 86400,
            'user_id' => $user['id'],
            'username' => $user['username'],
            'apps' => $apps,
        ]
    ], JSON_UNESCAPED_UNICODE);
}

function handleRegister() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['code' => 400, 'message' => '请使用 POST 方法']);
        return;
    }
    
    $data = getJsonInput();
    $userModel = new UserModel();
    
    if ($error = Validator::required($data['username'] ?? null, '用户名')) {
        echo json_encode(['code' => 400, 'message' => $error]); return;
    }
    if ($error = Validator::minLength($data['password'] ?? null, 6, '密码')) {
        echo json_encode(['code' => 400, 'message' => $error]); return;
    }
    if ($userModel->usernameExists(trim($data['username']))) {
        echo json_encode(['code' => 400, 'message' => '用户名已存在']);
        return;
    }
    
    $userModel->create(trim($data['username']), $data['password']);
    echo json_encode(['code' => 200, 'message' => '注册成功']);
}

function handleRegisterSub() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['code' => 400, 'message' => '请使用 POST 方法']);
        return;
    }
    
    $auth = new Auth();
    $owner = $auth->verifyToken($auth->getTokenFromHeader());
    if (!$owner) {
        echo json_encode(['code' => 401, 'message' => '未授权']);
        return;
    }
    
    $data = getJsonInput();
    $userModel = new UserModel();
    $appModel = new AppModel();
    
    if ($error = Validator::required($data['username'] ?? null, '用户名')) {
        echo json_encode(['code' => 400, 'message' => $error]); return;
    }
    if ($error = Validator::required($data['app_uuid'] ?? null, 'app_uuid')) {
        echo json_encode(['code' => 400, 'message' => $error]); return;
    }
    
    if ($userModel->usernameExists(trim($data['username']))) {
        echo json_encode(['code' => 400, 'message' => '用户名已存在']);
        return;
    }
    
    $app = $appModel->findByUuid($data['app_uuid']);
    if (!$app) {
        echo json_encode(['code' => 404, 'message' => '应用不存在']);
        return;
    }
    
    $role = $appModel->getUserRole($owner['id'], $app['id']);
    if ($role !== 'owner') {
        echo json_encode(['code' => 403, 'message' => '无权创建子用户']);
        return;
    }
    
    $subUserId = $userModel->create(trim($data['username']), $data['password']);
    $appModel->addUserToApp($subUserId, $app['id'], 'readonly');
    
    echo json_encode(['code' => 200, 'message' => '子用户创建成功']);
}

function handleLogout() {
    $auth = new Auth();
    $token = $auth->getTokenFromHeader();
    $user = $auth->verifyToken($token);
    if ($user) {
        $auth->logout($user['id']);
    }
    echo json_encode(['code' => 200, 'message' => '退出成功']);
}

function handleInfo() {
    $auth = new Auth();
    $user = $auth->verifyToken($auth->getTokenFromHeader());
    if (!$user) {
        echo json_encode(['code' => 401, 'message' => '未授权']);
        return;
    }
    
    $userModel = new UserModel();
    $userInfo = $userModel->find($user['id']);
    $userInfo['apps'] = $userModel->getUserApps($user['id']);
    unset($userInfo['password'], $userInfo['token'], $userInfo['token_expire']);
    
    echo json_encode(['code' => 200, 'message' => '成功', 'data' => $userInfo], JSON_UNESCAPED_UNICODE);
}
