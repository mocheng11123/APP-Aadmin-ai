<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/DocModel.php';

/**
 * 空白文档控制器
 */
class DocController extends Controller
{
    private $docModel;

    public function __construct()
    {
        parent::__construct();
        $this->docModel = new DocModel();
    }

    /**
     * 获取整个文档（所有人可读）
     */
    public function get($params = [])
    {
        $appUuid = $this->getParam('app_uuid');
        
        if ($error = Validator::required($appUuid, 'app_uuid')) {
            return $this->error($error);
        }
        
        // 验证应用权限（任意角色可读）
        $appId = $this->checkAppPermission($appUuid);
        
        $data = $this->docModel->getData($appId);
        return $this->success($data);
    }

    /**
     * 设置/更新字段（仅主账号，深度合并）
     */
    public function set($params = [])
    {
        $data = $this->getAllPostParams();
        
        if ($error = Validator::required($data['app_uuid'] ?? null, 'app_uuid')) {
            return $this->error($error);
        }
        if (!isset($data['data']) || !is_array($data['data'])) {
            return $this->error('data 必须是非空对象');
        }
        
        // 验证应用权限（需要 owner 角色）
        $appId = $this->checkAppPermission($data['app_uuid'], 'owner');
        
        $newData = $data['data'];
        
        // 获取现有数据
        $existingData = $this->docModel->getData($appId);
        
        // 深度合并
        $mergedData = $this->deepMerge($existingData, $newData);
        
        try {
            $this->docModel->updateData($appId, $mergedData);
            return $this->success(null, '更新成功');
        } catch (Exception $e) {
            return $this->error('更新失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * 删除字段（仅主账号，支持点号路径）
     */
    public function deleteField($params = [])
    {
        $data = $this->getAllPostParams();
        
        if ($error = Validator::required($data['app_uuid'] ?? null, 'app_uuid')) {
            return $this->error($error);
        }
        if (!isset($data['keys']) || !is_array($data['keys'])) {
            return $this->error('keys 必须是数组');
        }
        
        // 验证应用权限（需要 owner 角色）
        $appId = $this->checkAppPermission($data['app_uuid'], 'owner');
        
        $keys = $data['keys'];
        
        // 获取现有数据
        $docData = $this->docModel->getData($appId);
        
        // 逐个删除
        foreach ($keys as $key) {
            $this->deleteByKeyPath($docData, $key);
        }
        
        try {
            $this->docModel->updateData($appId, $docData);
            return $this->success(null, '删除成功');
        } catch (Exception $e) {
            return $this->error('删除失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * 清空文档（仅主账号）
     */
    public function clear($params = [])
    {
        $data = $this->getAllPostParams();
        
        if ($error = Validator::required($data['app_uuid'] ?? null, 'app_uuid')) {
            return $this->error($error);
        }
        
        // 验证应用权限（需要 owner 角色）
        $appId = $this->checkAppPermission($data['app_uuid'], 'owner');
        
        try {
            $this->docModel->updateData($appId, []);
            return $this->success(null, '清空成功');
        } catch (Exception $e) {
            return $this->error('清空失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * 深度合并两个数组
     *
     * @param array $existing
     * @param array $new
     *
     * @return array
     */
    private function deepMerge($existing, $new)
    {
        foreach ($new as $key => $value) {
            if (is_array($value) && isset($existing[$key]) && is_array($existing[$key])) {
                // 递归合并
                $existing[$key] = $this->deepMerge($existing[$key], $value);
            } else {
                // 直接覆盖
                $existing[$key] = $value;
            }
        }
        return $existing;
    }

    /**
     * 根据点号路径删除键
     *
     * @param array  $data
     * @param string $keyPath 如 "nested.sub.key"
     */
    private function deleteByKeyPath(&$data, $keyPath)
    {
        $keys = explode('.', $keyPath);
        $current = &$data;
        
        for ($i = 0; $i < count($keys) - 1; $i++) {
            $key = $keys[$i];
            if (!isset($current[$key]) || !is_array($current[$key])) {
                // 路径不存在，无需删除
                return;
            }
            $current = &$current[$key];
        }
        
        $lastKey = $keys[count($keys) - 1];
        if (isset($current[$lastKey])) {
            unset($current[$lastKey]);
        }
    }
}
