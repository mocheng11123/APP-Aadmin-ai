<?php
/**
 * 用户相关 API
 * 使用 action 参数路由
 */
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// 禁用输出缓冲
while (ob_get_level()) {
    ob_end_clean();
}

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
            outputJson(['code' => 404, 'message' => '未知操作：' . $action]);
    }
} catch (Exception $e) {
    outputJson([
        'code' => 500,
        'message' => '服务器错误：' . $e->getMessage(),
        'data' => null
    ]);
}

function getJsonInput() {
    $input = file_getcontents('php://input');
    return json_decode($input, true) ?: [];
}

function outputJson($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function handleLogin() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        outputJson(['code' => 400, 'message' => '请使用 POST 方法']);
    }
    
    $data = getJsonInput();
    $userModel = new UserModel();
    
    // 验证
    if ($error = Validator::required($data['username'] ?? null, '用户名')) {
        outputJson(['code' => 400, 'message' => $error]);
    }
    if ($error = Validator::required($data['password'] ?? null, '密码')) {
        outputJson(['code' => 400, 'message' => $error]);
    }
    
    $user = $userModel->findByUsername(trim($data['username']));
    if (!$user || !$userModel->verifyPassword($data['password'], $user['password'])) {
        outputJson(['code' => 400, 'message' => '用户名或密码错误']);
    }
    
    $auth = new Auth();
    $token = $auth->generateToken($user['id']);
    $apps = $userModel->getUserApps($user['id']);
    $config = require __DIR__ . '/config/app.php';
    
    outputJson([
        'code' => 200,
        'message' => '登录成功',
        'data' => [
            'token' => $token,
            'expire' => $config['token_expire_days'] * 86400,
            'user_id' => $user['id'],
            'username' => $user['username'],
            'apps' => $apps,
        ]
    ]);
}

function handleRegister() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        outputJson(['code' => 400, 'message' => '请使用 POST 方法']);
    }
    
    $data = getJsonInput();
    $userModel = new UserModel();
    
    if ($error = Validator::required($data['username'] ?? null, '用户名')) {
        outputJson(['code' => 400, 'message' => $error]);
    }
    if ($error = Validator::minLength($data['password'] ?? null, 6, '密码')) {
        outputJson(['code' => 400, 'message' => $error]);
    }
    if ($userModel->usernameExists(trim($data['username']))) {
        outputJson(['code' => 400, 'message' => '用户名已存在']);
    }
    
    $userModel->create(trim($data['username']), $data['password']);
    outputJson(['code' => 200, 'message' => '注册成功']);
}

function handleRegisterSub() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        outputJson(['code' => 400, 'message' => '请使用 POST 方法']);
    }
    
    $auth = new Auth();
    $token = $auth->getTokenFromHeader();
    $owner = $auth->verifyToken($token);
    if (!$owner) {
        outputJson(['code' => 401, 'message' => '未授权']);
    }
    
    $data = getJsonInput();
    $userModel = new UserModel();
    $appModel = new AppModel();
    
    if ($error = Validator::required($data['username'] ?? null, '用户名')) {
        outputJson(['code' => 400, 'message' => $error]);
    }
    if ($error = Validator::required($data['app_uuid'] ?? null, 'app_uuid')) {
        outputJson(['code' => 400, 'message' => $error]);
    }
    
    if ($userModel->usernameExists(trim($data['username']))) {
        outputJson(['code' => 400, 'message' => '用户名已存在']);
    }
    
    $app = $appModel->findByUuid($data['app_uuid']);
    if (!$app) {
        outputJson(['code' => 404, 'message' => '应用不存在']);
    }
    
    $role = $appModel->getUserRole($owner['id'], $app['id']);
    if ($role !== 'owner') {
        outputJson(['code' => 403, 'message' => '无权创建子用户']);
    }
    
    $subUserId = $userModel->create(trim($data['username']), $data['password']);
    $appModel->addUserToApp($subUserId, $app['id'], 'readonly');
    
    outputJson(['code' => 200, 'message' => '子用户创建成功']);
}

function handleLogout() {
    $auth = new Auth();
    $token = $auth->getTokenFromHeader();
    $user = $auth->verifyToken($token);
    if ($user) {
        $auth->logout($user['id']);
    }
    outputJson(['code' => 200, 'message' => '退出成功']);
}

function handleInfo() {
    $auth = new Auth();
    $token = $auth->getTokenFromHeader();
    $user = $auth->verifyToken($token);
    if (!$user) {
        outputJson(['code' => 401, 'message' => '未授权']);
    }
    
    $userModel = new UserModel();
    $userInfo = $userModel->find($user['id']);
    $userInfo['apps'] = $userModel->getUserApps($user['id']);
    unset($userInfo['password'], $userInfo['token'], $userInfo['token_expire']);
    
    outputJson(['code' => 200, 'message' => '成功', 'data' => $userInfo]);
}
