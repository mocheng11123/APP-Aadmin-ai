<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/SplashModel.php';

/**
 * 启动图配置控制器
 */
class SplashController extends Controller
{
    private $splashModel;

    public function __construct()
    {
        parent::__construct();
        $this->splashModel = new SplashModel();
    }

    /**
     * 设置启动图配置（仅主账号）
     */
    public function config($params = [])
    {
        $data = $this->getAllPostParams();
        
        // 验证输入
        if ($error = Validator::required($data['app_uuid'] ?? null, 'app_uuid')) {
            return $this->error($error);
        }
        if ($error = Validator::required($data['image_url'] ?? null, 'image_url')) {
            return $this->error($error);
        }
        
        // 验证应用权限（需要 owner 角色）
        $appId = $this->checkAppPermission($data['app_uuid'], 'owner');
        
        $duration = isset($data['duration']) ? (int)$data['duration'] : 3000;
        
        try {
            $this->splashModel->upsert($appId, $data['image_url'], $duration);
            return $this->success(null, '启动图配置成功');
        } catch (Exception $e) {
            return $this->error('配置失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取启动图配置（所有人可读）
     */
    public function getConfig($params = [])
    {
        $appUuid = $this->getParam('app_uuid');
        
        if ($error = Validator::required($appUuid, 'app_uuid')) {
            return $this->error($error);
        }
        
        // 验证应用权限（任意角色可读）
        $appId = $this->checkAppPermission($appUuid);
        
        $config = $this->splashModel->getByAppId($appId);
        
        if (!$config) {
            // 无配置时返回默认值
            return $this->success([
                'image_url' => '',
                'duration'  => 3000,
            ]);
        }
        
        return $this->success([
            'image_url' => $config['image_url'],
            'duration'  => (int)$config['duration'],
        ]);
    }
}
