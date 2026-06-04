<?php
require_once __DIR__ . '/../core/Model.php';

/**
 * 应用版本模型
 */
class VersionModel extends Model
{
    protected $table = 'app_versions';

    /**
     * 获取应用的版本信息
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
     * 检查是否有更新
     *
     * @param int $appId
     * @param int $currentVersionCode
     *
     * @return array|null 有更新返回版本信息，无更新返回 null
     */
    public function checkUpdate($appId, $currentVersionCode)
    {
        $sql = "SELECT * FROM {$this->table} WHERE app_id = :app_id AND version_code > :version LIMIT 1";
        $result = $this->db->queryOne($sql, ['app_id' => $appId, 'version' => $currentVersionCode]);
        return $result ?: null;
    }

    /**
     * 更新或插入版本信息（upsert）
     *
     * @param int    $appId
     * @param int    $versionCode
     * @param string $versionName
     * @param string $downloadUrl
     * @param string $updateContent
     * @param int    $forceUpdate
     *
     * @return int 影响行数
     */
    public function upsert($appId, $versionCode, $versionName, $downloadUrl, $updateContent = null, $forceUpdate = 0)
    {
        $sql = "INSERT INTO {$this->table} 
                (app_id, version_code, version_name, download_url, update_content, force_update)
                VALUES (:app_id, :version_code, :version_name, :download_url, :update_content, :force_update)
                ON DUPLICATE KEY UPDATE
                version_code = VALUES(version_code),
                version_name = VALUES(version_name),
                download_url = VALUES(download_url),
                update_content = VALUES(update_content),
                force_update = VALUES(force_update)";
        
        return $this->db->execute($sql, [
            'app_id'         => $appId,
            'version_code'   => $versionCode,
            'version_name'   => $versionName,
            'download_url'   => $downloadUrl,
            'update_content' => $updateContent,
            'force_update'   => $forceUpdate,
        ]);
    }
}
