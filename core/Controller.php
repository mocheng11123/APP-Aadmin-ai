<?php
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../models/AppModel.php';

/**
 * 控制器基类
 */
class Controller
{
    protected $auth;
    protected $userId;
    protected $username;
    protected $appModel;

    public function __construct()
    {
        $this->auth = new Auth();
        $this->appModel = new AppModel();
    }

    /**
     * 要求认证，设置用户信息
     *
     * @return int 用户 ID
     */
    protected function requireAuth()
    {
        $user = $this->auth->requireAuth();
        $this->userId = $user['id'];
        $this->username = $user['username'];
        return $this->userId;
    }

    /**
     * 检查应用权限
     *
     * @param string      $appUuid      应用 UUID
     * @param string|null $requiredRole 所需角色 (null=任意角色，'owner'=仅主账号)
     *
     * @return int 应用 ID
     */
    protected function checkAppPermission($appUuid, $requiredRole = null)
    {
        if (empty($appUuid)) {
            Response::error('缺少 app_uuid 参数');
        }
        
        // 确保已认证
        if (!$this->userId) {
            $this->requireAuth();
        }
        
        // 获取应用信息
        $app = $this->appModel->findByUuid($appUuid);
        if (!$app) {
            Response::error('应用不存在', 404);
        }
        
        $appId = $app['id'];
        
        // 检查用户是否有权访问该应用
        $role = $this->appModel->getUserRole($this->userId, $appId);
        if ($role === null) {
            Response::error('无权访问此应用', 403);
        }
        
        // 检查角色权限
        if ($requiredRole === 'owner' && $role !== 'owner') {
            Response::error('权限不足，仅主账号可执行此操作', 403);
        }
        
        return $appId;
    }

    /**
     * 获取 GET 参数
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    protected function getParam($key, $default = null)
    {
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }

    /**
     * 获取 POST 参数（支持表单和 JSON）
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    protected function postParam($key, $default = null)
    {
        // 先检查表单数据
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        
        // 再检查 JSON 数据
        $jsonInput = $this->getJsonInput();
        if (isset($jsonInput[$key])) {
            return $jsonInput[$key];
        }
        
        return $default;
    }

    /**
     * 获取所有 POST 参数（支持表单和 JSON）
     *
     * @return array
     */
    protected function getAllPostParams()
    {
        // 如果有表单数据
        if (!empty($_POST)) {
            return $_POST;
        }
        
        // 否则尝试 JSON
        $jsonInput = $this->getJsonInput();
        if ($jsonInput) {
            return $jsonInput;
        }
        
        return [];
    }

    /**
     * 获取 JSON 输入
     *
     * @return array
     */
    protected function getJsonInput()
    {
        $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $rawInput = file_getcontents('php://input');
            if (!empty($rawInput)) {
                $data = json_decode($rawInput, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $data ?: [];
                }
            }
        }
        
        return [];
    }

    /**
     * 获取完整的 JSON 输入体
     *
     * @return array
     */
    protected function getJsonBody()
    {
        return $this->getJsonInput();
    }

    /**
     * 输出成功响应
     *
     * @param mixed  $data
     * @param string $message
     *
     * @return array
     */
    protected function success($data = null, $message = '成功')
    {
        return Response::success($data, $message);
    }

    /**
     * 输出错误响应
     *
     * @param string $message
     * @param int    $code
     * @param mixed  $data
     *
     * @return array
     */
    protected function error($message = '错误', $code = 400, $data = null)
    {
        return Response::error($message, $code, $data);
    }
}
