<?php
/**
 * 空白文档 API
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

function deepMerge($existing, $new) {
    foreach ($new as $key => $value) {
        if (is_array($value) && isset($existing[$key]) && is_array($existing[$key])) {
            $existing[$key] = deepMerge($existing[$key], $value);
        } else {
            $existing[$key] = $value;
        }
    }
    return $existing;
}

function deleteByKeyPath(&$data, $keyPath) {
    $keys = explode('.', $keyPath);
    $current = &$data;
    for ($i = 0; $i < count($keys) - 1; $i++) {
        $key = $keys[$i];
        if (!isset($current[$key]) || !is_array($current[$key])) {
            return;
        }
        $current = &$current[$key];
    }
    $lastKey = $keys[count($keys) - 1];
    if (isset($current[$lastKey])) {
        unset($current[$lastKey]);
    }
}

try {
    require_once __DIR__ . '/core/DB.php';
    require_once __DIR__ . '/core/Auth.php';
    require_once __DIR__ . '/utils/Validator.php';
    require_once __DIR__ . '/models/AppModel.php';
    require_once __DIR__ . '/models/DocModel.php';
    
    $appModel = new AppModel();
    $docModel = new DocModel();
    $auth = new Auth();
    
    // 获取文档
    if ($action === 'get' && $method === 'GET') {
        $appUuid = $_GET['app_uuid'] ?? '';
        $app = $appModel->findByUuid($appUuid);
        if (!$app) outputJson(['code' => 404, 'message' => '应用不存在']);
        
        $data = $docModel->getData($app['id']);
        outputJson(['code' => 200, 'data' => $data]);
    }
    
    // 设置字段（仅 owner）
    if ($action === 'set' && $method === 'POST') {
        $user = $auth->verifyToken($auth->getTokenFromHeader());
        if (!$user) outputJson(['code' => 401, 'message' => '未授权']);
        
        $data = getJsonInput();
        if ($error = Validator::required($data['app_uuid'] ?? null, 'app_uuid')) {
            outputJson(['code' => 400, 'message' => $error]);
        }
        if (!isset($data['data']) || !is_array($data['data'])) {
            outputJson(['code' => 400, 'message' => 'data 必须是非空对象']);
        }
        
        $app = $appModel->findByUuid($data['app_uuid']);
        if (!$app) outputJson(['code' => 404, 'message' => '应用不存在']);
        
        $role = $appModel->getUserRole($user['id'], $app['id']);
        if ($role !== 'owner') outputJson(['code' => 403, 'message' => '权限不足']);
        
        $existingData = $docModel->getData($app['id']);
        $mergedData = deepMerge($existingData, $data['data']);
        $docModel->updateData($app['id'], $mergedData);
        
        outputJson(['code' => 200, 'message' => '更新成功']);
    }
    
    // 删除字段（仅 owner）
    if ($action === 'delete' && $method === 'POST') {
        $user = $auth->verifyToken($auth->getTokenFromHeader());
        if (!$user) outputJson(['code' => 401, 'message' => '未授权']);
        
        $data = getJsonInput();
        if ($error = Validator::required($data['app_uuid'] ?? null, 'app_uuid')) {
            outputJson(['code' => 400, 'message' => $error]);
        }
        if (!isset($data['keys']) || !is_array($data['keys'])) {
            outputJson(['code' => 400, 'message' => 'keys 必须是数组']);
        }
        
        $app = $appModel->findByUuid($data['app_uuid']);
        if (!$app) outputJson(['code' => 404, 'message' => '应用不存在']);
        
        $role = $appModel->getUserRole($user['id'], $app['id']);
        if ($role !== 'owner') outputJson(['code' => 403, 'message' => '权限不足']);
        
        $docData = $docModel->getData($app['id']);
        foreach ($data['keys'] as $key) {
            deleteByKeyPath($docData, $key);
        }
        $docModel->updateData($app['id'], $docData);
        
        outputJson(['code' => 200, 'message' => '删除成功']);
    }
    
    // 清空文档（仅 owner）
    if ($action === 'clear' && $method === 'POST') {
        $user = $auth->verifyToken($auth->getTokenFromHeader());
        if (!$user) outputJson(['code' => 401, 'message' => '未授权']);
        
        $data = getJsonInput();
        if ($error = Validator::required($data['app_uuid'] ?? null, 'app_uuid')) {
            outputJson(['code' => 400, 'message' => $error]);
        }
        
        $app = $appModel->findByUuid($data['app_uuid']);
        if (!$app) outputJson(['code' => 404, 'message' => '应用不存在']);
        
        $role = $appModel->getUserRole($user['id'], $app['id']);
        if ($role !== 'owner') outputJson(['code' => 403, 'message' => '权限不足']);
        
        $docModel->updateData($app['id'], []);
        outputJson(['code' => 200, 'message' => '清空成功']);
    }
    
    outputJson(['code' => 404, 'message' => "未知操作：{$action}"]);
    
} catch (Exception $e) {
    outputJson(['code' => 500, 'message' => '错误：' . $e->getMessage()]);
}
