<?php
require_once __DIR__ . '/../core/Model.php';

/**
 * 空白文档模型
 */
class DocModel extends Model
{
    protected $table = 'uuid_documents';

    /**
     * 获取应用的文档数据
     *
     * @param int $appId
     *
     * @return array
     */
    public function getData($appId)
    {
        $doc = $this->findOne('app_id = :app_id', ['app_id' => $appId]);
        if (!$doc) {
            return [];
        }
        
        $data = json_decode($doc['doc_data'], true);
        return $data ?: [];
    }

    /**
     * 更新文档数据
     *
     * @param int   $appId
     * @param array $data
     *
     * @return int
     */
    public function updateData($appId, $data)
    {
        $jsonStr = json_encode($data, JSON_UNESCAPED_UNICODE);
        
        // 检查是否存在
        $exists = $this->count('app_id = :app_id', ['app_id' => $appId]) > 0;
        
        if ($exists) {
            return $this->updateBy('app_id = :app_id', ['doc_data' => $jsonStr], [
                'app_id' => $appId,
            ]);
        } else {
            return $this->insert([
                'app_id'   => $appId,
                'doc_data' => $jsonStr,
            ]);
        }
    }

    /**
     * 检查文档是否存在
     *
     * @param int $appId
     *
     * @return bool
     */
    public function exists($appId)
    {
        return $this->count('app_id = :app_id', ['app_id' => $appId]) > 0;
    }
}
