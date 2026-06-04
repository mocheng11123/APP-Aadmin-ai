<?php
/**
 * 公告管理 API
 */
header('Content-Type: application/json; charset=utf-8');
while (ob_get_level()) { ob_end_clean(); }

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['param0'] ?? 0;

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
    require_once __DIR__ . '/models/AnnouncementModel.php';
    
    $appModel = new AppModel();
    $announcementModel = new AnnouncementModel();
    $auth = new Auth();
    
    // 创建公告（仅 owner）
    if ($action === 'create' && $method === 'POST') {
        $user = $auth->verifyToken($auth->getTokenFromHeader());
        if (!$user) outputJson(['code' => 401, 'message' => '未授权']);
        
        $data = getJsonInput();
        if ($error = Validator::required($data['app_uuid'] ?? null, 'app_uuid')) {
            outputJson(['code' => 400, 'message' => $error]);
        }
        if ($error = Validator::required($data['title'] ?? null, '标题')) {
            outputJson(['code' => 400, 'message' => $error]);
        }
        
        $app = $appModel->findByUuid($data['app_uuid']);
        if (!$app) outputJson(['code' => 404, 'message' => '应用不存在']);
        
        $role = $appModel->getUserRole($user['id'], $app['id']);
        if ($role !== 'owner') outputJson(['code' => 403, 'message' => '权限不足']);
        
        $id = $announcementModel->create($app['id'], $data['title'], $data['content'] ?? '');
        outputJson(['code' => 200, 'message' => '公告创建成功', 'data' => ['id' => $id]]);
    }
    
    // 更新公告（仅 owner）
    if ($action === 'update' && $method === 'POST') {
        $user = $auth->verifyToken($auth->getTokenFromHeader());
        if (!$user) outputJson(['code' => 401, 'message' => '未授权']);
        
        $data = getJsonInput();
        if (empty($data['id'])) outputJson(['code' => 400, 'message' => '缺少公告 ID']);
        
        $ann = $announcementModel->getDetail($data['id']);
        if (!$ann) outputJson(['code' => 404, 'message' => '公告不存在']);
        
        $role = $appModel->getUserRole($user['id'], $ann['app_id']);
        if ($role !== 'owner') outputJson(['code' => 403, 'message' => '权限不足']);
        
        $announcementModel->update($data['id'], $ann['app_id'], $data['title'], $data['content']);
        outputJson(['code' => 200, 'message' => '公告已更新']);
    }
    
    // 删除公告（仅 owner）
    if ($action === 'delete' && $method === 'POST') {
        $user = $auth->verifyToken($auth->getTokenFromHeader());
        if (!$user) outputJson(['code' => 401, 'message' => '未授权']);
        
        $data = getJsonInput();
        if (empty($data['id'])) outputJson(['code' => 400, 'message' => '缺少公告 ID']);
        
        $ann = $announcementModel->getDetail($data['id']);
        if (!$ann) outputJson(['code' => 404, 'message' => '公告不存在']);
        
        $role = $appModel->getUserRole($user['id'], $ann['app_id']);
        if ($role !== 'owner') outputJson(['code' => 403, 'message' => '权限不足']);
        
        $announcementModel->delete($data['id'], $ann['app_id']);
        outputJson(['code' => 200, 'message' => '公告已删除']);
    }
    
    // 获取公告列表
    if ($action === 'list' && $method === 'GET') {
        $appUuid = $_GET['app_uuid'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);
        
        $app = $appModel->findByUuid($appUuid);
        if (!$app) outputJson(['code' => 404, 'message' => '应用不存在']);
        
        $list = $announcementModel->getList($app['id'], $page, $limit);
        $total = $announcementModel->getTotal($app['id']);
        
        outputJson(['code' => 200, 'data' => ['list' => $list, 'total' => $total]]);
    }
    
    // 获取公告详情
    if ($action === 'detail' && $method === 'GET') {
        if (!$id) outputJson(['code' => 400, 'message' => '缺少公告 ID']);
        
        $ann = $announcementModel->getDetail($id);
        if (!$ann) outputJson(['code' => 404, 'message' => '公告不存在']);
        
        outputJson(['code' => 200, 'data' => $ann]);
    }
    
    outputJson(['code' => 404, 'message' => "未知操作：{$action}"]);
    
} catch (Exception $e) {
    outputJson(['code' => 500, 'message' => '错误：' . $e->getMessage()]);
}
