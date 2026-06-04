<?php
/**
 * 统一响应格式化工具类
 */
class Response
{
    /**
     * 成功响应
     *
     * @param mixed  $data
     * @param string $message
     * @param int    $code
     */
    public static function success($data = null, $message = '成功', $code = 200)
    {
        return self::json($code, $message, $data);
    }

    /**
     * 错误响应
     *
     * @param string $message
     * @param int    $code
     * @param mixed  $data
     */
    public static function error($message = '错误', $code = 400, $data = null)
    {
        return self::json($code, $message, $data);
    }

    /**
     * 未认证响应
     *
     * @param string $message
     */
    public static function unauthorized($message = '未认证或 Token 已过期')
    {
        return self::json(401, $message, null);
    }

    /**
     * 资源不存在
     *
     * @param string $message
     */
    public static function notFound($message = '资源不存在')
    {
        return self::json(404, $message, null);
    }

    /**
     * 服务器错误
     *
     * @param string $message
     */
    public static function serverError($message = '服务器内部错误')
    {
        return self::json(500, $message, null);
    }

    /**
     * 输出 JSON 并终止
     *
     * @param int    $code
     * @param string $message
     * @param mixed  $data
     */
    public static function send($code = 200, $message = '成功', $data = null)
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        // 处理 OPTIONS 预检请求
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        http_response_code($code);
        echo json_encode([
            'code'    => $code,
            'message' => $message,
            'data'    => $data,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 返回 JSON 数组（不直接输出）
     *
     * @param int    $code
     * @param string $message
     * @param mixed  $data
     *
     * @return array
     */
    private static function json($code, $message, $data)
    {
        return [
            'code'    => $code,
            'message' => $message,
            'data'    => $data,
        ];
    }
}
