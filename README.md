# iApp 管家 API

一款为移动应用设计的 PHP + MySQL 后端 API 服务，支持多应用管理和角色权限控制。

## 功能特性

- **用户系统**：主账号注册/登录，子用户管理
- **应用管理**：创建应用，获取应用列表
- **版本检测**：版本配置与更新检查
- **公告管理**：公告 CRUD 操作
- **启动图配置**：应用专属启动图
- **空白文档**：JSON 文档存储，支持深度合并

## 技术栈

- **PHP**: 7.4+（原生，无 Composer 依赖）
- **MySQL**: 5.7+
- **数据库驱动**: PDO（防止 SQL 注入）
- **认证**: Token 认证（Bearer Token，30 天有效期）
- **API 格式**: RESTful JSON

## 核心概念

### 角色体系
- **主账号 (owner)**：普通注册的用户，可以创建应用，拥有所有权限
- **子用户 (readonly)**：由主账号创建并绑定到应用，只能读取数据

### 应用 (App)
- 每个应用有唯一的 `app_uuid`（对外暴露）
- 主账号可以创建多个应用
- 应用数据隔离（版本、公告、启动图、文档）

## 快速开始

### 1. 数据库配置

编辑 `config/database.php`：

```php
return [
    'host'     => 'localhost',
    'dbname'   => 'your_database_name',
    'username' => 'your_username',
    'password' => 'your_password',
    'charset'  => 'utf8mb4',
];
```

### 2. 导入数据库

```bash
mysql -u username -p your_database_name < install.sql
```

### 3. 上传到服务器

将所有文件上传到 Web 服务器的可访问目录。

### 4. 测试

```bash
bash test_api.sh
```

## API 文档

### 基础 URL

```
https://yourdomain.com/iapp_api/api
```

### 认证方式

需要认证的接口，在请求头中携带 Token：

```
Authorization: Bearer <your_token>
```

### 主要接口

#### 用户认证
- `POST /api/user/register` - 主账号注册
- `POST /api/user/register_sub` - 子用户注册（需要 owner token）
- `POST /api/user/login` - 用户登录
- `POST /api/user/logout` - 退出登录
- `GET /api/user/info` - 获取用户信息

#### 应用管理
- `POST /api/app/create` - 创建应用（仅主账号）
- `GET /api/app/list` - 获取我的应用列表

#### 版本管理
- `POST /api/version/update` - 更新版本配置（仅主账号）
- `GET /api/version/check?app_uuid=xxx&client_version_code=101` - 检查更新

#### 公告管理
- `POST /api/announcement/create` - 创建公告（仅主账号）
- `POST /api/announcement/update` - 更新公告（仅主账号）
- `POST /api/announcement/delete` - 删除公告（仅主账号）
- `GET /api/announcement/list?app_uuid=xxx&page=1&limit=10` - 公告列表
- `GET /api/announcement/detail/{id}` - 公告详情

#### 启动图
- `POST /api/splash/config` - 设置启动图（仅主账号）
- `GET /api/splash/config?app_uuid=xxx` - 获取启动图

#### 空白文档
- `GET /api/doc/get?app_uuid=xxx` - 获取文档
- `POST /api/doc/set` - 设置/更新字段（仅主账号，深度合并）
- `POST /api/doc/delete` - 删除字段（仅主账号，支持点号路径）
- `POST /api/doc/clear` - 清空文档（仅主账号）

## 请求参数说明

### GET 请求
`app_uuid` 通过 query 参数传递：
```
GET /api/version/check?app_uuid=550e8400-e29b-41d4-a716-446655440000&client_version_code=101
```

### POST 请求
`app_uuid` 通过 JSON 请求体传递：
```json
{
  "app_uuid": "550e8400-e29b-41d4-a716-446655440000",
  "data": { "key": "value" }
}
```

## 空白文档使用示例

### 设置字段（深度合并）

```bash
curl -X POST /api/doc/set \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "app_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "data": {
      "user_name": "张三",
      "settings": { "theme": "dark" }
    }
  }'
```

### 再次设置（自动合并）

```bash
curl -X POST /api/doc/set \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "app_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "data": {
      "settings": { "font_size": 16 },
      "email": "test@example.com"
    }
  }'
```

最终文档内容：
```json
{
  "user_name": "张三",
  "settings": {
    "theme": "dark",
    "font_size": 16
  },
  "email": "test@example.com"
}
```

### 删除字段（支持点号路径）

```bash
curl -X POST /api/doc/delete \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "app_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "keys": ["user_name", "settings.theme"]
  }'
```

## 权限控制

### 权限检查流程
1. 从 Token 获取用户 ID
2. 查询 `user_app_relations` 表获取角色
3. `owner` 可读写，`readonly` 只读

### 403 错误场景
- 用户不属于该应用
- 子用户尝试写操作

## 部署指南

详细部署说明请查看 [DEPLOY.md](./DEPLOY.md)

## 目录结构

```
.
├── .htaccess              # URL 重写配置
├── index.php              # 入口文件
├── config/                # 配置文件
│   ├── database.php       # 数据库配置
│   └── app.php            # 应用配置
├── core/                  # 核心类
│   ├── DB.php             # 数据库连接
│   ├── Router.php         # 路由器
│   ├── Controller.php     # 控制器基类
│   ├── Model.php          # 模型基类
│   └── Auth.php           # 认证类
├── controllers/           # 控制器
│   ├── UserController.php
│   ├── AppController.php
│   ├── VersionController.php
│   ├── AnnouncementController.php
│   ├── SplashController.php
│   └── DocController.php
├── models/                # 模型
│   ├── UserModel.php
│   ├── AppModel.php
│   ├── VersionModel.php
│   ├── AnnouncementModel.php
│   ├── SplashModel.php
│   └── DocModel.php
├── utils/                 # 工具类
│   ├── Response.php       # 响应格式化
│   └── Validator.php      # 输入验证
├── uploads/               # 上传目录
├── install.sql            # 数据库脚本
└── test_api.sh            # 测试脚本
```

## 安全性

- 所有数据库操作使用 PDO 预处理语句，防止 SQL 注入
- 密码使用 `password_hash()` 加密存储
- Token 采用随机字符串（32 字节），安全强度等同于 256 位
- 上传目录禁止执行 PHP
- 支持 HTTPS（需在服务器配置）

## 注意事项

1. **生产环境请关闭错误显示** - 在 `index.php` 中设置 `ini_set('display_errors', 0)`
2. **定期备份数据库** - 重要数据请定期备份
3. **Token 有效期** - 默认 30 天，可在 `config/app.php` 中修改
4. **上传文件** - APK 和启动图需手动上传到 `uploads/` 目录

## 常见错误码

- `200` - 成功
- `400` - 客户端错误
- `401` - 未认证
- `403` - 权限不足
- `404` - 资源不存在
- `500` - 服务器错误

## 许可证

MIT License
