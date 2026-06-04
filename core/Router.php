<?php
require_once __DIR__ . '/../utils/Response.php';

/**
 * 路由解析类
 * 支持路径参数匹配，如 /api/announcement/detail/{id}
 * 兼容 IIS/Apache/Nginx
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
     */
    public function get($path, $handler)
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * 注册 POST 路由
     */
    public function post($path, $handler)
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * 注册 PUT/DELETE 路由
     */
    public function put($path, $handler)
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete($path, $handler)
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * 添加路由
     */
    private function addRoute($method, $path, $handler)
    {
        $fullPath = $this->basePath ? $this->basePath . $path : $path;
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $fullPath);
        $pattern = '#^' . $pattern . '$#';
        
        $this->routes[] = [
            'method'  => $method,
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    /**
     * 路由分发
     */
    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        
        // 从 REQUEST_URI 提取路径（兼容所有服务器）
        $uri = $this->getRequestUri();
        
        // 移除查询字符串
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // URL 解码
        $uri = urldecode($uri);
        
        // 移除脚本路径前缀 (如 /iapp_api)
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if ($scriptDir && strpos($uri, $scriptDir) === 0) {
            $uri = substr($uri, strlen($scriptDir));
        }
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method && $method !== 'OPTIONS') {
                continue;
            }
            
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $result = call_user_func($route['handler'], $params);
                
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
        
        Response::notFound('接口不存在：' . $method . ' ' . $uri);
    }
    
    /**
     * 获取请求 URI（兼容 IIS/Apache/Nginx）
     */
    private function getRequestUri()
    {
        // IIS: HTTP_X_ORIGINAL_URL 或 HTTP_X_REWRITE_URL
        if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
            return $_SERVER['HTTP_X_ORIGINAL_URL'];
        }
        if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            return $_SERVER['HTTP_X_REWRITE_URL'];
        }
        
        // Apache/Nginx: REQUEST_URI
        if (isset($_SERVER['REQUEST_URI'])) {
            return $_SERVER['REQUEST_URI'];
        }
        
        // Fallback: 拼接 PATH_INFO
        if (isset($_SERVER['PHP_SELF'])) {
            $uri = $_SERVER['PHP_SELF'];
            if (isset($_SERVER['PATH_INFO'])) {
                $uri .= $_SERVER['PATH_INFO'];
            }
            return $uri;
        }
        
        return '/';
    }
}
