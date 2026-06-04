<?php
require_once __DIR__ . '/../core/Model.php';

/**
 * 启动图配置模型
 */
class SplashModel extends Model
{
    protected $table = 'splash_configs';

    /**
     * 获取应用的启动图配置
     *
     * @param int $appId
     *
     * @return array|null
     */
    public function getByAppId($appId)
    {
        return $this->findOne('app_id = :app_id', ['app_id' => $appId]);
    }

    /**
     * 设置启动图配置（upsert）
     *
     * @param int    $appId
     * @param string $imageUrl
     * @param int    $duration
     *
     * @return int
     */
    public function upsert($appId, $imageUrl, $duration = 3000)
    {
        $sql = "INSERT INTO {$this->table} (app_id, image_url, duration)
                VALUES (:app_id, :image_url, :duration)
                ON DUPLICATE KEY UPDATE
                image_url = VALUES(image_url),
                duration = VALUES(duration)";
        
        return $this->db->execute($sql, [
            'app_id'    => $appId,
            'image_url' => $imageUrl,
            'duration'  => $duration,
        ]);
    }
}
