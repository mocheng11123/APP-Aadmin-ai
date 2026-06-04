<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/AppModel.php';

/**
 * 用户控制器
 */
class UserController extends Controller
{
    private $userModel;
    private $appModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->appModel = new AppModel();
    }

    /**
     * 主账号注册
     */
    public function register($params = [])
    {
        $data = $this->getAllPostParams();
        
        // 验证输入
        if ($error = Validator::required($data['username'] ?? null, '用户名')) {
            return $this->error($error);
        }
        if ($error = Validator::minLength($data['password'] ?? null, 6, '密码')) {
            return $this->error($error);
        }
        
        $username = trim($data['username']);
        $password = $data['password'];
        
        // 检查用户名是否存在
        if ($this->userModel->usernameExists($username)) {
            return $this->error('用户名已存在');
        }
        
        // 创建用户
        try {
            $this->userModel->create($username, $password);
            return $this->success(null, '注册成功');
        } catch (Exception $e) {
            return $this->error('注册失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * 子用户注册（需要主账号 token 授权）
     */
    public function registerSub($params = [])
    {
        // 验证主账号 token
        $owner = $this->auth->requireAuth();
        
        $data = $this->getAllPostParams();
        
        // 验证输入
        if ($error = Validator::required($data['username'] ?? null, '用户名')) {
            return $this->error($error);
        }
        if ($error = Validator::required($data['password'] ?? null, '密码')) {
            return $this->error($error);
        }
        if ($error = Validator::required($data['app_uuid'] ?? null, 'app_uuid')) {
            return $this->error($error);
        }
        
        $username = trim($data['username']);
        $password = $data['password'];
        $appUuid = $data['app_uuid'];
        
        // 检查用户名是否存在
        if ($this->userModel->usernameExists($username)) {
            return $this->error('用户名已存在');
        }
        
        // 检查应用是否存在且属于主账号
        $app = $this->appModel->findByUuid($appUuid);
        if (!$app) {
            return $this->error('应用不存在', 404);
        }
        
        // 验证主账号是否有权添加子用户（必须是 owner）
        $role = $this->appModel->getUserRole($owner['id'], $app['id']);
        if ($role !== 'owner') {
            return $this->error('无权在此应用下创建子用户', 403);
        }
        
        // 创建子用户
        try {
            $this->db->beginTransaction();
            
            // 创建用户
            $subUserId = $this->userModel->create($username, $password);
            
            // 添加到应用（readonly 角色）
            $this->appModel->addUserToApp($subUserId, $app['id'], 'readonly');
            
            $this->db->commit();
            
            return $this->success(null, '子用户创建成功');
        } catch (Exception $e) {
            $this->db->rollback();
            return $this->error('创建失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * 用户登录
     */
    public function login($params = [])
    {
        $data = $this->getAllPostParams();
        
        // 验证输入
        if ($error = Validator::required($data['username'] ?? null, '用户名')) {
            return $this->error($error);
        }
        if ($error = Validator::required($data['password'] ?? null, '密码')) {
            return $this->error($error);
        }
        
        $username = trim($data['username']);
        $password = $data['password'];
        
        // 查找用户
        $user = $this->userModel->findByUsername($username);
        if (!$user) {
            return $this->error('用户名或密码错误');
        }
        
        // 验证密码
        if (!$this->userModel->verifyPassword($password, $user['password'])) {
            return $this->error('用户名或密码错误');
        }
        
        // 生成 Token
        $token = $this->auth->generateToken($user['id']);
        
        // 获取用户关联的应用列表
        $apps = $this->userModel->getUserApps($user['id']);
        
        $config = require __DIR__ . '/../config/app.php';
        $expireSeconds = $config['token_expire_days'] * 86400;
        
        return $this->success([
            'token'    => $token,
            'expire'   => $expireSeconds,
            'user_id'  => $user['id'],
            'username' => $user['username'],
            'apps'     => $apps,
        ], '登录成功');
    }

    /**
     * 退出登录
     */
    public function logout($params = [])
    {
        $user = $this->auth->requireAuth();
        $this->auth->logout($user['id']);
        
        return $this->success(null, '退出成功');
    }

    /**
     * 获取当前用户信息
     */
    public function info($params = [])
    {
        $user = $this->auth->requireAuth();
        
        $userInfo = $this->userModel->find($user['id']);
        if (!$userInfo) {
            return $this->error('用户不存在', 404);
        }
        
        // 获取应用列表
        $apps = $this->userModel->getUserApps($user['id']);
        
        unset($userInfo['password'], $userInfo['token'], $userInfo['token_expire']);
        $userInfo['apps'] = $apps;
        
        return $this->success($userInfo);
    }
}
