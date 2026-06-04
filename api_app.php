<?php
/**
 * 应用管理 API
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
    require_once __DIR__ . '/models/UserModel.php';
    
    $appModel = new AppModel();
    $userModel = new UserModel();
    $auth = new Auth();
    
    // 创建应用（仅主账号）
    if ($action === 'create' && $method === 'POST') {
        $user = $auth->verifyToken($auth->getTokenFromHeader());
        if (!$user) outputJson(['code' => 401, 'message' => '未授权']);
        
        $data = getJsonInput();
        if ($error = Validator::required($data['app_name'] ?? null, '应用名称')) {
            outputJson(['code' => 400, 'message' => $error]);
        }
        
        $appUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
        $db = DB::getInstance();
        $db->beginTransaction();
        
        $appId = $appModel->create($appUuid, trim($data['app_name']), $user['id']);
        $appModel->addUserToApp($user['id'], $appId, 'owner');
        
        $db->commit();
        
        outputJson(['code' => 200, 'message' => '应用创建成功', 'data' => [
            'app_uuid' => $appUuid,
            'app_name' => trim($data['app_name']),
        ]]);
    }
    
    // 获取应用列表
    if ($action === 'list' && $method === 'GET') {
        $user = $auth->verifyToken($auth->getTokenFromHeader());
        if (!$user) outputJson(['code' => 401, 'message' => '未授权']);
        
        $apps = $userModel->getUserApps($user['id']);
        outputJson(['code' => 200, 'data' => $apps]);
    }
    
    // 获取应用成员列表
    if ($action === 'members' && $method === 'GET') {
        $user = $auth->verifyToken($auth->getTokenFromHeader());
        if (!$user) outputJson(['code' => 401, 'message' => '未授权']);
        
        $appUuid = $_GET['app_uuid'] ?? '';
        $app = $appModel->findByUuid($appUuid);
        if (!$app) outputJson(['code' => 404, 'message' => '应用不存在']);
        
        $role = $appModel->getUserRole($user['id'], $app['id']);
        if (!$role) outputJson(['code' => 403, 'message' => '无权访问']);
        
        $db = DB::getInstance();
        $members = $db->query("
            SELECT u.id, u.username, r.role, r.join_time 
            FROM users u 
            INNER JOIN user_app_relations r ON u.id = r.user_id 
            WHERE r.app_id = ?
        ", [$app['id']]);
        
        outputJson(['code' => 200, 'data' => $members]);
    }
    
    // 删除成员
    if ($action === 'member' && $method === 'DELETE') {
        $user = $auth->verifyToken($auth->getTokenFromHeader());
        if (!$user) outputJson(['code' => 401, 'message' => '未授权']);
        
        $userId = $_GET['user_id'] ?? 0;
        $appUuid = $_GET['app_uuid'] ?? '';
        
        $app = $appModel->findByUuid($appUuid);
        if (!$app) outputJson(['code' => 404, 'message' => '应用不存在']);
        
        $role = $appModel->getUserRole($user['id'], $app['id']);
        if ($role !== 'owner') outputJson(['code' => 403, 'message' => '权限不足']);
        
        $db = DB::getInstance();
        $db->execute("DELETE FROM user_app_relations WHERE user_id = ? AND app_id = ?", [
            $userId, $app['id']
        ]);
        
        outputJson(['code' => 200, 'message' => '成员已删除']);
    }
    
    outputJson(['code' => 404, 'message' => "未知操作：{$action}"]);
    
} catch (Exception $e) {
    outputJson(['code' => 500, 'message' => '错误：' . $e->getMessage()]);
}
