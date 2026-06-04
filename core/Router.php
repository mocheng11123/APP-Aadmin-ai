<?php
require_once __DIR__ . '/../utils/Response.php';

/**
 * 路由解析类
 * 支持路径参数匹配，如 /api/announcement/detail/{id}
 */
class Router
{
    private $routes = [];
    private $basePath = '';

    public function __construct($basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * 注册 GET 路由
     *
     * @param string   $path
     * @param callable $handler
     */
    public function get($path, $handler)
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * 注册 POST 路由
     *
     * @param string   $path
     * @param callable $handler
     */
    public function post($path, $handler)
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * 注册 PUT 路由
     *
     * @param string   $path
     * @param callable $handler
     */
    public function put($path, $handler)
    {
        $this->addRoute('PUT', $path, $handler);
    }

    /**
     * 注册 DELETE 路由
     *
     * @param string   $path
     * @param callable $handler
     */
    public function delete($path, $handler)
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * 添加路由
     *
     * @param string   $method
     * @param string   $path
     * @param callable $handler
     */
    private function addRoute($method, $path, $handler)
    {
        $path = $this->basePath . $path;
        // 将路径参数 {param} 转换为正则表达式
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        
        $this->routes[] = [
            'method'  => $method,
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    /**
     * 路由分发
     *
     * @param string|null $uri 可选的 URI，如果为 null 则从 $_SERVER 获取
     */
    public function dispatch($uri = null)
    {
        $method = $_SERVER['REQUEST_METHOD'];
        
        if ($uri === null) {
            $uri = isset($_SERVER['HTTP_X_ORIGINAL_URL']) 
                ? $_SERVER['HTTP_X_ORIGINAL_URL']
                : (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/');
        }
        
        // URL 解码
        $uri = urldecode($uri);
        
        // 提取 PATH_INFO（重写后的路径）
        if (strpos($uri, '/api/') === 0) {
            $uri = substr($uri, 4); // 保持/api 前缀
        } elseif (isset($_SERVER['PATH_INFO'])) {
            $uri = $_SERVER['PATH_INFO'];
        }
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method && $method !== 'OPTIONS') {
                continue;
            }
            
            if (preg_match($route['pattern'], $uri, $matches)) {
                // 提取路径参数
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                // 调用处理函数
                $result = call_user_func($route['handler'], $params);
                
                // 如果返回的是数组，输出 JSON
                if (is_array($result)) {
                    Response::send(
                        $result['code'] ?? 200,
                        $result['message'] ?? '成功',
                        $result['data'] ?? null
                    );
                }
                return;
            }
        }
        
        // 未找到匹配路由
        Response::notFound('接口不存在');
    }
}
