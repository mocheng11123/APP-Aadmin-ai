<?php
require_once __DIR__ . '/../core/Model.php';

/**
 * 公告模型
 */
class AnnouncementModel extends Model
{
    protected $table = 'announcements';

    /**
     * 获取公告列表（分页，按 app 筛选）
     *
     * @param int $appId
     * @param int $page
     * @param int $limit
     *
     * @return array
     */
    public function getList($appId, $page = 1, $limit = 10)
    {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT id, title, create_time 
                FROM {$this->table} 
                WHERE app_id = :app_id AND is_active = 1
                ORDER BY create_time DESC
                LIMIT :offset, :limit";
        
        return $this->db->query($sql, [
            'app_id' => $appId,
            'offset' => $offset,
            'limit'  => $limit,
        ]);
    }

    /**
     * 获取公告总数
     *
     * @param int $appId
     *
     * @return int
     */
    public function getTotal($appId)
    {
        return $this->count('app_id = :app_id AND is_active = 1', ['app_id' => $appId]);
    }

    /**
     * 获取公告详情
     *
     * @param int $id
     *
     * @return array|null
     */
    public function getDetail($id)
    {
        return $this->find($id);
    }

    /**
     * 创建公告
     *
     * @param int    $appId
     * @param string $title
     * @param string $content
     *
     * @return string
     */
    public function create($appId, $title, $content)
    {
        return $this->insert([
            'app_id'  => $appId,
            'title'   => $title,
            'content' => $content,
        ]);
    }

    /**
     * 更新公告（验证 app 归属）
     *
     * @param int    $id
     * @param int    $appId
     * @param string $title
     * @param string $content
     *
     * @return int
     */
    public function update($id, $appId, $title, $content)
    {
        return $this->updateBy('id = :id AND app_id = :app_id', [
            'title'   => $title,
            'content' => $content,
        ], ['id' => $id, 'app_id' => $appId]);
    }

    /**
     * 删除公告（验证 app 归属）
     *
     * @param int $id
     * @param int $appId
     *
     * @return int
     */
    public function delete($id, $appId)
    {
        return $this->deleteBy('id = :id AND app_id = :app_id', [
            'id'     => $id,
            'app_id' => $appId,
        ]);
    }

    /**
     * 检查公告是否属于指定应用
     *
     * @param int $id
     * @param int $appId
     *
     * @return bool
     */
    public function belongsToApp($id, $appId)
    {
        return $this->count('id = :id AND app_id = :app_id', [
            'id'     => $id,
            'app_id' => $appId,
        ]) > 0;
    }
}
