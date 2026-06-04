<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/AnnouncementModel.php';

/**
 * 公告控制器
 */
class AnnouncementController extends Controller
{
    private $announcementModel;

    public function __construct()
    {
        parent::__construct();
        $this->announcementModel = new AnnouncementModel();
    }

    /**
     * 创建公告（仅主账号）
     */
    public function create($params = [])
    {
        $data = $this->getAllPostParams();
        
        // 验证输入
        if ($error = Validator::required($data['app_uuid'] ?? null, 'app_uuid')) {
            return $this->error($error);
        }
        if ($error = Validator::required($data['title'] ?? null, '公告标题')) {
            return $this->error($error);
        }
        if ($error = Validator::required($data['content'] ?? null, '公告内容')) {
            return $this->error($error);
        }
        
        // 验证应用权限（需要 owner 角色）
        $appId = $this->checkAppPermission($data['app_uuid'], 'owner');
        
        try {
            $id = $this->announcementModel->create(
                $appId,
                $data['title'],
                $data['content']
            );
            
            return $this->success(['id' => $id], '公告创建成功');
        } catch (Exception $e) {
            return $this->error('创建失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * 更新公告（仅主账号）
     */
    public function update($params = [])
    {
        $data = $this->getAllPostParams();
        
        // 验证输入
        if ($error = Validator::required($data['id'] ?? null, '公告 ID')) {
            return $this->error($error);
        }
        if ($error = Validator::required($data['title'] ?? null, '公告标题')) {
            return $this->error($error);
        }
        if ($error = Validator::required($data['content'] ?? null, '公告内容')) {
            return $this->error($error);
        }
        
        $id = (int)$data['id'];
        
        // 获取公告信息以验证 app 归属
        $announcement = $this->announcementModel->getDetail($id);
        if (!$announcement) {
            return $this->error('公告不存在', 404);
        }
        
        // 验证对该公告所属应用有 owner 权限
        $appId = $this->checkAppPermissionByAppId($announcement['app_id'], 'owner');
        
        try {
            $result = $this->announcementModel->update(
                $id,
                $appId,
                $data['title'],
                $data['content']
            );
            
            if ($result === 0) {
                return $this->error('公告不属于此应用', 403);
            }
            
            return $this->success(null, '公告更新成功');
        } catch (Exception $e) {
            return $this->error('更新失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * 删除公告（仅主账号）
     */
    public function delete($params = [])
    {
        $data = $this->getAllPostParams();
        
        // 验证输入
        if ($error = Validator::required($data['id'] ?? null, '公告 ID')) {
            return $this->error($error);
        }
        
        $id = (int)$data['id'];
        
        // 获取公告信息以验证 app 归属
        $announcement = $this->announcementModel->getDetail($id);
        if (!$announcement) {
            return $this->error('公告不存在', 404);
        }
        
        // 验证对该公告所属应用有 owner 权限
        $appId = $this->checkAppPermissionByAppId($announcement['app_id'], 'owner');
        
        try {
            $result = $this->announcementModel->delete($id, $appId);
            
            if ($result === 0) {
                return $this->error('公告不属于此应用', 403);
            }
            
            return $this->success(null, '公告删除成功');
        } catch (Exception $e) {
            return $this->error('删除失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取公告列表（所有人可读）
     */
    public function list($params = [])
    {
        $appUuid = $this->getParam('app_uuid');
        $page = (int)$this->getParam('page', 1);
        $limit = (int)$this->getParam('limit', 10);
        
        if ($error = Validator::required($appUuid, 'app_uuid')) {
            return $this->error($error);
        }
        
        // 验证应用权限（任意角色可读）
        $appId = $this->checkAppPermission($appUuid);
        
        if ($page < 1) {
            $page = 1;
        }
        if ($limit < 1 || $limit > 100) {
            $limit = 10;
        }
        
        $list = $this->announcementModel->getList($appId, $page, $limit);
        $total = $this->announcementModel->getTotal($appId);
        
        return $this->success([
            'list'  => $list,
            'total' => $total,
        ]);
    }

    /**
     * 获取公告详情（所有人可读）
     */
    public function detail($params = [])
    {
        $id = isset($params['id']) ? (int)$params['id'] : 0;
        
        if ($id <= 0) {
            return $this->error('公告 ID 无效');
        }
        
        $announcement = $this->announcementModel->getDetail($id);
        if (!$announcement) {
            return $this->error('公告不存在', 404);
        }
        
        // 验证对该公告所属应用有访问权限
        $this->checkAppPermissionByAppId($announcement['app_id']);
        
        return $this->success($announcement);
    }

    /**
     * 根据 app_id 检查权限（内部方法）
     */
    private function checkAppPermissionByAppId($appId, $requiredRole = null)
    {
        if (!$this->userId) {
            $this->requireAuth();
        }
        
        $role = $this->appModel->getUserRole($this->userId, $appId);
        if ($role === null) {
            Response::error('无权访问此应用', 403);
        }
        
        if ($requiredRole === 'owner' && $role !== 'owner') {
            Response::error('权限不足，仅主账号可执行此操作', 403);
        }
        
        return $appId;
    }
}
