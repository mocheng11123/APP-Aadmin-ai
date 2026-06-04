<?php
/**
 * 数据库连接类
 * 单例模式，提供全局数据库连接
 */
class DB
{
    private static $instance = null;
    private $pdo;

    /**
     * 构造函数，初始化 PDO 连接
     */
    private function __construct()
    {
        $config = require __DIR__ . '/../config/database.php';
        
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['dbname'],
            $config['charset']
        );
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        try {
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (PDOException $e) {
            Response::serverError('数据库连接失败');
        }
    }

    /**
     * 获取单例实例
     *
     * @return DB
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 获取 PDO 实例
     *
     * @return PDO
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * 执行查询
     *
     * @param string $sql
     * @param array  $params
     *
     * @return array
     */
    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * 查询单行
     *
     * @param string $sql
     * @param array  $params
     *
     * @return array|null
     */
    public function queryOne($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * 执行插入，返回插入 ID
     *
     * @param string $sql
     * @param array  $params
     *
     * @return string
     */
    public function insert($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $this->pdo->lastInsertId();
    }

    /**
     * 执行更新/删除，返回影响行数
     *
     * @param string $sql
     * @param array  $params
     *
     * @return int
     */
    public function execute($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * 开启事务
     */
    public function beginTransaction()
    {
        $this->pdo->beginTransaction();
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        $this->pdo->commit();
    }

    /**
     * 回滚事务
     */
    public function rollback()
    {
        $this->pdo->rollBack();
    }
}
