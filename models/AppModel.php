<?php
require_once __DIR__ . '/../core/Model.php';

/**
 * 应用模型
 */
class AppModel extends Model
{
    protected $table = 'apps';

    /**
     * 根据 app_uuid 查找应用
     *
     * @param string $appUuid
     *
     * @return array|null
     */
    public function findByUuid($appUuid)
    {
        return $this->findOne('app_uuid = :uuid', ['uuid' => $appUuid]);
    }

    /**
     * 创建应用
     *
     * @param string $appUuid
     * @param string $appName
     * @param int    $ownerUserId
     *
     * @return string 应用 ID
     */
    public function create($appUuid, $appName, $ownerUserId)
    {
        return $this->insert([
            'app_uuid'      => $appUuid,
            'app_name'      => $appName,
            'owner_user_id' => $ownerUserId,
        ]);
    }

    /**
     * 获取用户的应用列表（包括 owner 和 member）
     *
     * @param int $userId
     *
     * @return array
     */
    public function getUserApps($userId)
    {
        $sql = "SELECT a.app_uuid, a.app_name, r.role, a.create_time
                FROM apps a
                INNER JOIN user_app_relations r ON a.id = r.app_id
                WHERE r.user_id = :user_id
                ORDER BY a.create_time DESC";
        
        return $this->db->query($sql, ['user_id' => $userId]);
    }

    /**
     * 获取用户在应用中的角色
     *
     * @param int $userId
     * @param int $appId
     *
     * @return string|null 角色 (owner/readonly)，不存在返回 null
     */
    public function getUserRole($userId, $appId)
    {
        $sql = "SELECT role FROM user_app_relations WHERE user_id = :user_id AND app_id = :app_id";
        $result = $this->db->queryOne($sql, ['user_id' => $userId, 'app_id' => $appId]);
        return $result ? $result['role'] : null;
    }

    /**
     * 检查用户是否有权访问应用
     *
     * @param int $userId
     * @param int $appId
     *
     * @return bool
     */
    public function canAccess($userId, $appId)
    {
        return $this->count('user_id = :user_id AND app_id = :app_id', [
            'user_id' => $userId,
            'app_id'  => $appId,
        ]) > 0;
    }

    /**
     * 添加用户到应用
     *
     * @param int    $userId
     * @param int    $appId
     * @param string $role
     *
     * @return string
     */
    public function addUserToApp($userId, $appId, $role = 'readonly')
    {
        return $this->db->insert(
            "INSERT INTO user_app_relations (user_id, app_id, role) VALUES (:user_id, :app_id, :role)",
            ['user_id' => $userId, 'app_id' => $appId, 'role' => $role]
        );
    }

    /**
     * 检查应用是否存在
     *
     * @param string $appUuid
     *
     * @return bool
     */
    public function existsByUuid($appUuid)
    {
        return $this->count('app_uuid = :uuid', ['uuid' => $appUuid]) > 0;
    }
}
