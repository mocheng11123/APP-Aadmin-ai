<?php
/**
 * Token 认证类
 */
class Auth
{
    private $db;
    private $tokenExpireDays;

    public function __construct()
    {
        $this->db = DB::getInstance();
        $config = require __DIR__ . '/../config/app.php';
        $this->tokenExpireDays = $config['token_expire_days'];
    }

    /**
     * 生成 Token
     *
     * @param int $userId
     *
     * @return string
     */
    public function generateToken($userId)
    {
        $token = bin2hex(random_bytes(32));
        $expireTime = date('Y-m-d H:i:s', strtotime("+{$this->tokenExpireDays} days"));
        
        $sql = "UPDATE users SET token = :token, token_expire = :expire WHERE id = :id";
        $this->db->execute($sql, [
            'token'  => $token,
            'expire' => $expireTime,
            'id'     => $userId,
        ]);
        
        return $token;
    }

    /**
     * 验证 Token
     *
     * @param string $token
     *
     * @return array|null 用户信息，null 表示无效
     */
    public function verifyToken($token)
    {
        if (empty($token)) {
            return null;
        }
        
        $sql = "SELECT id, username, token_expire FROM users WHERE token = :token";
        $user = $this->db->queryOne($sql, ['token' => $token]);
        
        if (!$user) {
            return null;
        }
        
        // 检查是否过期
        if (strtotime($user['token_expire']) < time()) {
            // 删除过期 token
            $this->db->execute("UPDATE users SET token = NULL, token_expire = NULL WHERE id = :id", [
                'id' => $user['id'],
            ]);
            return null;
        }
        
        return $user;
    }

    /**
     * 从请求头获取 Token
     *
     * @return string|null
     */
    public function getTokenFromHeader()
    {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            return null;
        }
        
        $authHeader = $headers['Authorization'];
        if (strpos($authHeader, 'Bearer ') !== 0) {
            return null;
        }
        
        return substr($authHeader, 7);
    }

    /**
     * 要求认证，返回用户信息
     *
     * @return array 用户信息 (id, username)
     */
    public function requireAuth()
    {
        $token = $this->getTokenFromHeader();
        if (!$token) {
            Response::unauthorized('请在请求头中携带 Authorization: Bearer <token>');
        }
        
        $user = $this->verifyToken($token);
        if (!$user) {
            Response::unauthorized('Token 无效或已过期，请重新登录');
        }
        
        return $user;
    }

    /**
     * 退出登录（删除 Token）
     *
     * @param int $userId
     */
    public function logout($userId)
    {
        $this->db->execute("UPDATE users SET token = NULL, token_expire = NULL WHERE id = :id", [
            'id' => $userId,
        ]);
    }
}

/**
 * 兼容 CGI 和 FastCGI 的 getallheaders 函数
 */
if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}
