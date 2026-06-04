<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/VersionModel.php';

/**
 * 版本检测控制器
 */
class VersionController extends Controller
{
    private $versionModel;

    public function __construct()
    {
        parent::__construct();
        $this->versionModel = new VersionModel();
    }

    /**
     * 版本更新设置（仅主账号）
     */
    public function update($params = [])
    {
        $data = $this->getAllPostParams();
        
        // 验证输入
        if ($error = Validator::required($data['app_uuid'] ?? null, 'app_uuid')) {
            return $this->error($error);
        }
        if ($error = Validator::required($data['version_code'] ?? null, 'version_code')) {
            return $this->error($error);
        }
        if ($error = Validator::integer($data['version_code'], 'version_code')) {
            return $this->error($error);
        }
        if ($error = Validator::required($data['version_name'] ?? null, 'version_name')) {
            return $this->error($error);
        }
        if ($error = Validator::required($data['download_url'] ?? null, 'download_url')) {
            return $this->error($error);
        }
        
        // 验证应用权限（需要 owner 角色）
        $appId = $this->checkAppPermission($data['app_uuid'], 'owner');
        
        // 更新版本信息
        try {
            $this->versionModel->upsert(
                $appId,
                (int)$data['version_code'],
                $data['version_name'],
                $data['download_url'],
                $data['update_content'] ?? null,
                isset($data['force_update']) ? (int)$data['force_update'] : 0
            );
            
            return $this->success(null, '版本信息更新成功');
        } catch (Exception $e) {
            return $this->error('更新失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * 版本检测（所有人可读）
     */
    public function check($params = [])
    {
        $appUuid = $this->getParam('app_uuid');
        $clientVersionCode = $this->getParam('client_version_code');
        
        if ($error = Validator::required($appUuid, 'app_uuid')) {
            return $this->error($error);
        }
        if ($error = Validator::required($clientVersionCode, 'client_version_code')) {
            return $this->error($error);
        }
        if ($error = Validator::integer($clientVersionCode, 'client_version_code')) {
            return $this->error($error);
        }
        
        // 验证应用权限（任意角色可读）
        $appId = $this->checkAppPermission($appUuid);
        
        $currentCode = (int)$clientVersionCode;
        $updateInfo = $this->versionModel->checkUpdate($appId, $currentCode);
        
        if (!$updateInfo) {
            // 无更新
            return $this->success([
                'has_update' => false,
            ]);
        }
        
        // 有更新
        return $this->success([
            'has_update'     => true,
            'version_code'   => (int)$updateInfo['version_code'],
            'version_name'   => $updateInfo['version_name'],
            'download_url'   => $updateInfo['download_url'],
            'update_content' => $updateInfo['update_content'],
            'force_update'   => (bool)$updateInfo['force_update'],
        ]);
    }
}
