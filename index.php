<?php
/**
 * iApp 管家 API 入口
 * 所有请求通过此文件路由分发
 */

// 立即输出，不要等
header('Content-Type: application/json; charset=utf-8');

// 错误报告（调试时开启）
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 自动加载类
spl_autoload_register(function ($class) {
    // Core 目录
    $coreFile = __DIR__ . '/core/' . $class . '.php';
    if (file_exists($coreFile)) {
        require_once $coreFile;
        return;
    }
    
    // Controllers 目录
    $controllerFile = __DIR__ . '/controllers/' . $class . '.php';
    if (file_exists($controllerFile)) {
        require_once $controllerFile;
        return;
    }
    
    // Models 目录
    $modelFile = __DIR__ . '/models/' . $class . '.php';
    if (file_exists($modelFile)) {
        require_once $modelFile;
        return;
    }
    
    // Utils 目录
    $utilFile = __DIR__ . '/utils/' . $class . '.php';
    if (file_exists($utilFile)) {
        require_once $utilFile;
        return;
    }
});

try {
    // 路由配置
    $router = new Router();

    // 用户相关路由
    $router->post('/user/register', function() {
        $controller = new UserController();
        return $controller->register();
    });

    $router->post('/user/register_sub', function() {
        $controller = new UserController();
        return $controller->registerSub();
    });

    $router->post('/user/login', function() {
        $controller = new UserController();
        return $controller->login();
    });

    $router->post('/user/logout', function() {
        $controller = new UserController();
        return $controller->logout();
    });

    $router->get('/user/info', function() {
        $controller = new UserController();
        return $controller->info();
    });

    // 应用管理路由
    $router->post('/app/create', function() {
        $controller = new AppController();
        return $controller->create();
    });

    $router->get('/app/list', function() {
        $controller = new AppController();
        return $controller->list();
    });

    // 版本检测路由
    $router->post('/version/update', function() {
        $controller = new VersionController();
        return $controller->update();
    });

    $router->get('/version/check', function() {
        $controller = new VersionController();
        return $controller->check();
    });

    // 公告相关路由
    $router->post('/announcement/create', function() {
        $controller = new AnnouncementController();
        return $controller->create();
    });

    $router->post('/announcement/update', function() {
        $controller = new AnnouncementController();
        return $controller->update();
    });

    $router->post('/announcement/delete', function() {
        $controller = new AnnouncementController();
        return $controller->delete();
    });

    $router->get('/announcement/list', function() {
        $controller = new AnnouncementController();
        return $controller->list();
    });

    $router->get('/announcement/detail/{id}', function($params) {
        $controller = new AnnouncementController();
        return $controller->detail($params);
    });

    // 启动图配置路由
    $router->post('/splash/config', function() {
        $controller = new SplashController();
        return $controller->config();
    });

    $router->get('/splash/config', function() {
        $controller = new SplashController();
        return $controller->getConfig();
    });

    // 空白文档路由
    $router->get('/doc/get', function() {
        $controller = new DocController();
        return $controller->get();
    });

    $router->post('/doc/set', function() {
        $controller = new DocController();
        return $controller->set();
    });

    $router->post('/doc/delete', function() {
        $controller = new DocController();
        return $controller->deleteField();
    });

    $router->post('/doc/clear', function() {
        $controller = new DocController();
        return $controller->clear();
    });

    // 分发请求
    $router->dispatch();
    
} catch (Exception $e) {
    echo json_encode([
        'code' => 500,
        'message' => '服务器错误：' . $e->getMessage(),
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
}
