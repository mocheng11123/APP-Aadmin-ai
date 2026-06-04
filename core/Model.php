<?php
/**
 * 模型基类
 * 封装常用数据库操作
 */
class Model
{
    protected $db;
    protected $table = '';
    protected $primaryKey = 'id';

    public function __construct()
    {
        $this->db = DB::getInstance();
    }

    /**
     * 查询所有记录
     *
     * @param string $where
     * @param array  $params
     * @param string $orderBy
     * @param string $limit
     *
     * @return array
     */
    public function findAll($where = '', $params = [], $orderBy = '', $limit = '')
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        return $this->db->query($sql, $params);
    }

    /**
     * 查询单条记录
     *
     * @param mixed $id
     *
     * @return array|null
     */
    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->queryOne($sql, [$id]);
    }

    /**
     * 查询单条记录（自定义条件）
     *
     * @param string $where
     * @param array  $params
     *
     * @return array|null
     */
    public function findOne($where, $params = [])
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$where} LIMIT 1";
        return $this->db->queryOne($sql, $params);
    }

    /**
     * 插入记录
     *
     * @param array $data
     *
     * @return string 插入 ID
     */
    public function insert($data)
    {
        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $placeholders = ':' . implode(', :', $keys);
        
        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})";
        return $this->db->insert($sql, $data);
    }

    /**
     * 更新记录
     *
     * @param mixed $id
     * @param array $data
     *
     * @return int 影响行数
     */
    public function update($id, $data)
    {
        $sets = [];
        foreach ($data as $key => $value) {
            $sets[] = "{$key} = :{$key}";
        }
        $setStr = implode(', ', $sets);
        
        $sql = "UPDATE {$this->table} SET {$setStr} WHERE {$this->primaryKey} = :id";
        $data['id'] = $id;
        return $this->db->execute($sql, $data);
    }

    /**
     * 更新记录（自定义条件）
     *
     * @param string $where
     * @param array  $data
     * @param array  $whereParams
     *
     * @return int
     */
    public function updateBy($where, $data, $whereParams = [])
    {
        $sets = [];
        foreach ($data as $key => $value) {
            $sets[] = "{$key} = :{$key}";
        }
        $setStr = implode(', ', $sets);
        
        $sql = "UPDATE {$this->table} SET {$setStr} WHERE {$where}";
        $params = array_merge($data, $whereParams);
        return $this->db->execute($sql, $params);
    }

    /**
     * 删除记录
     *
     * @param mixed $id
     *
     * @return int
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->execute($sql, [$id]);
    }

    /**
     * 删除记录（自定义条件）
     *
     * @param string $where
     * @param array  $params
     *
     * @return int
     */
    public function deleteBy($where, $params = [])
    {
        $sql = "DELETE FROM {$this->table} WHERE {$where}";
        return $this->db->execute($sql, $params);
    }

    /**
     * 统计记录数
     *
     * @param string $where
     * @param array  $params
     *
     * @return int
     */
    public function count($where = '1=1', $params = [])
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$where}";
        $result = $this->db->queryOne($sql, $params);
        return (int)($result['total'] ?? 0);
    }
}
