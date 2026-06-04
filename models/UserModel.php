<?php
require_once __DIR__ . '/../core/Model.php';

/**
 * 用户模型
 */
class UserModel extends Model
{
    protected $table = 'users';

    /**
     * 根据用户名查找用户
     *
     * @param string $username
     *
     * @return array|null
     */
    public function findByUsername($username)
    {
        return $this->findOne('username = :username', ['username' => $username]);
    }

    /**
     * 根据 Token 查找用户
     *
     * @param string $token
     *
     * @return array|null
     */
    public function findByToken($token)
    {
        return $this->findOne('token = :token', ['token' => $token]);
    }

    /**
     * 创建用户
     *
     * @param string $username
     * @param string $password
     *
     * @return string 用户 ID
     */
    public function create($username, $password)
    {
        return $this->insert([
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    }

    /**
     * 验证密码
     *
     * @param string $password
     * @param string $hash
     *
     * @return bool
     */
    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * 检查用户名是否存在
     *
     * @param string $username
     *
     * @return bool
     */
    public function usernameExists($username)
    {
        return $this->count('username = :username', ['username' => $username]) > 0;
    }

    /**
     * 获取用户关联的应用列表
     *
     * @param int $userId
     *
     * @return array
     */
    public function getUserApps($userId)
    {
        $sql = "SELECT a.app_uuid, a.app_name, r.role
                FROM apps a
                INNER JOIN user_app_relations r ON a.id = r.app_id
                WHERE r.user_id = :user_id
                ORDER BY a.create_time DESC";
        
        return $this->db->query($sql, ['user_id' => $userId]);
    }
}
