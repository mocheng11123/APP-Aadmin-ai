<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/AppModel.php';

/**
 * 应用管理控制器
 */
class AppController extends Controller
{
    private $appModel;
    private $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->appModel = new AppModel();
        $this->userModel = new UserModel();
    }

    /**
     * 创建应用（仅主账号）
     */
    public function create($params = [])
    {
        $user = $this->auth->requireAuth();
        
        $data = $this->getJsonBody();
        
        // 验证输入
        if ($error = Validator::required($data['app_name'] ?? null, '应用名称')) {
            return $this->error($error);
        }
        
        $appName = trim($data['app_name']);
        
        // 生成 app_uuid
        $appUuid = $this->generateUuid();
        
        try {
            $this->db->beginTransaction();
            
            // 创建应用
            $appId = $this->appModel->create($appUuid, $appName, $user['id']);
            
            // 将创建者添加为 owner
            $this->appModel->addUserToApp($user['id'], $appId, 'owner');
            
            $this->db->commit();
            
            return $this->success([
                'app_uuid' => $appUuid,
                'app_name' => $appName,
            ], '应用创建成功');
        } catch (Exception $e) {
            $this->db->rollback();
            return $this->error('创建失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取我的应用列表
     */
    public function list($params = [])
    {
        $user = $this->auth->requireAuth();
        
        $apps = $this->userModel->getUserApps($user['id']);
        
        return $this->success($apps);
    }

    /**
     * 生成 UUID
     *
     * @return string
     */
    private function generateUuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
