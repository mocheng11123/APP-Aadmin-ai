<?php
/**
 * 版本管理 API
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
    require_once __DIR__ . '/models/VersionModel.php';
    
    $appModel = new AppModel();
    $versionModel = new VersionModel();
    $auth = new Auth();
    
    // 更新版本配置（仅 owner）
    if ($action === 'update' && $method === 'POST') {
        $user = $auth->verifyToken($auth->getTokenFromHeader());
        if (!$user) outputJson(['code' => 401, 'message' => '未授权']);
        
        $data = getJsonInput();
        if ($error = Validator::required($data['app_uuid'] ?? null, 'app_uuid')) {
            outputJson(['code' => 400, 'message' => $error]);
        }
        if ($error = Validator::required($data['version_code'] ?? null, 'version_code')) {
            outputJson(['code' => 400, 'message' => $error]);
        }
        
        $app = $appModel->findByUuid($data['app_uuid']);
        if (!$app) outputJson(['code' => 404, 'message' => '应用不存在']);
        
        $role = $appModel->getUserRole($user['id'], $app['id']);
        if ($role !== 'owner') outputJson(['code' => 403, 'message' => '权限不足']);
        
        $versionModel->upsert(
            $app['id'],
            (int)$data['version_code'],
            $data['version_name'] ?? '1.0.0',
            $data['download_url'] ?? '',
            $data['update_content'] ?? null,
            isset($data['force_update']) ? (int)$data['force_update'] : 0
        );
        
        outputJson(['code' => 200, 'message' => '版本配置已更新']);
    }
    
    // 检查版本
    if ($action === 'check' && $method === 'GET') {
        $appUuid = $_GET['app_uuid'] ?? '';
        $clientVersion = $_GET['client_version_code'] ?? 0;
        
        $app = $appModel->findByUuid($appUuid);
        if (!$app) outputJson(['code' => 404, 'message' => '应用不存在']);
        
        $updateInfo = $versionModel->checkUpdate($app['id'], (int)$clientVersion);
        
        if (!$updateInfo) {
            outputJson(['code' => 200, 'data' => ['has_update' => false]]);
        }
        
        outputJson(['code' => 200, 'data' => [
            'has_update' => true,
            'version_code' => (int)$updateInfo['version_code'],
            'version_name' => $updateInfo['version_name'],
            'download_url' => $updateInfo['download_url'],
            'update_content' => $updateInfo['update_content'],
            'force_update' => (bool)$updateInfo['force_update'],
        ]]);
    }
    
    // 获取当前版本配置
    if ($action === 'config' && $method === 'GET') {
        $appUuid = $_GET['app_uuid'] ?? '';
        $app = $appModel->findByUuid($appUuid);
        if (!$app) outputJson(['code' => 404, 'message' => '应用不存在']);
        
        $version = $versionModel->getByAppId($app['id']);
        outputJson(['code' => 200, 'data' => $version ?: null]);
    }
    
    outputJson(['code' => 404, 'message' => "未知操作：{$action}"]);
    
} catch (Exception $e) {
    outputJson(['code' => 500, 'message' => '错误：' . $e->getMessage()]);
}
