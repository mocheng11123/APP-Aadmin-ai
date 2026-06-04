<?php
/**
 * 启动图配置 API
 */
header('Content-Type: application/json; charset=utf-8');
while (ob_get_level()) { ob_end_clean(); }

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

function getJsonInput() {
    $input = file_getcontents('php://input');
    return $input ? (json_decode($input, true) ?: []) : [];
}

function outputJson($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once __DIR__ . '/core/DB.php';
    require_once __DIR__ . '/core/Auth.php';
    require_once __DIR__ . '/utils/Validator.php';
    require_once __DIR__ . '/models/AppModel.php';
    require_once __DIR__ . '/models/SplashModel.php';
    
    $appModel = new AppModel();
    $splashModel = new SplashModel();
    $auth = new Auth();
    
    // 设置启动图（仅 owner）
    if ($action === 'config' && $method === 'POST') {
        $user = $auth->verifyToken($auth->getTokenFromHeader());
        if (!$user) outputJson(['code' => 401, 'message' => '未授权']);
        
        $data = getJsonInput();
        if ($error = Validator::required($data['app_uuid'] ?? null, 'app_uuid')) {
            outputJson(['code' => 400, 'message' => $error]);
        }
        if ($error = Validator::required($data['image_url'] ?? null, 'image_url')) {
            outputJson(['code' => 400, 'message' => $error]);
        }
        
        $app = $appModel->findByUuid($data['app_uuid']);
        if (!$app) outputJson(['code' => 404, 'message' => '应用不存在']);
        
        $role = $appModel->getUserRole($user['id'], $app['id']);
        if ($role !== 'owner') outputJson(['code' => 403, 'message' => '权限不足']);
        
        $splashModel->upsert($app['id'], $data['image_url'], (int)($data['duration'] ?? 3000));
        outputJson(['code' => 200, 'message' => '启动图配置已保存']);
    }
    
    // 获取启动图
    if ($action === 'config' && $method === 'GET') {
        $appUuid = $_GET['app_uuid'] ?? '';
        $app = $appModel->findByUuid($appUuid);
        if (!$app) outputJson(['code' => 404, 'message' => '应用不存在']);
        
        $config = $splashModel->getByAppId($app['id']);
        if (!$config) {
            outputJson(['code' => 200, 'data' => ['image_url' => '', 'duration' => 3000]]);
        }
        
        outputJson(['code' => 200, 'data' => [
            'image_url' => $config['image_url'],
            'duration' => (int)$config['duration'],
        ]]);
    }
    
    outputJson(['code' => 404, 'message' => "未知操作：{$action}"]);
    
} catch (Exception $e) {
    outputJson(['code' => 500, 'message' => '错误：' . $e->getMessage()]);
}
