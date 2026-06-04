# iApp 管家 API 部署说明

## 项目概述

iApp 管家是一款移动应用的后端 API 服务，提供用户认证、应用管理、版本检测、公告推送、启动图配置和空白文档管理等功能。

**技术栈**：
- PHP 7.4（原生，无第三方依赖）
- MySQL 5.7+
- PDO + 预处理语句（防止 SQL 注入）
- Token 认证（30 天有效期）

## 核心概念

### 角色体系
- **主账号 (owner)**：通过普通注册创建，可以创建应用，对应用拥有所有权限
- **子用户 (readonly)**：由主账号创建并绑定到应用，只能读取数据，不能修改配置

### 应用 (App)
- 每个应用有唯一的 `app_uuid`（对外暴露）和内部 `app_id`
- 主账号可以创建多个应用
- 每个应用的数据（版本、公告、启动图、空白文档）相互隔离

## 环境要求

- PHP >= 7.4
- MySQL >= 5.7
- Apache + mod_rewrite（用于 URL 重写）
- FTP 访问权限（用于上传文件）

## 部署步骤

### 1. 数据库配置

#### 1.1 创建数据库

```sql
CREATE DATABASE sqldaytime123 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### 1.2 导入表结构

将 `install.sql` 文件中的 SQL 语句在 MySQL 中执行：

```bash
mysql -u daytime123 -padmin192412 sqldaytime123 < install.sql
```

#### 1.3 修改数据库配置

编辑 `config/database.php`（已预配置）：

```php
return [
    'host'     => 'localhost',
    'dbname'   => 'sqldaytime123',
    'username' => 'daytime123',
    'password' => 'admin192412',
    'charset'  => 'utf8mb4',
];
```

### 2. 上传代码

通过 FTP 将所有文件上传到服务器指定目录（例如 `/iapp_api/`）。

### 3. 配置 Apache

确保 `.htaccess` 文件被启用（Apache 配置中 `AllowOverride All`）。

### 4. 上传资源文件

将 APK 文件和启动图上传到 `uploads/` 目录，然后在数据库中更新 URL。

### 5. 验证部署

使用 `test_api.sh` 脚本进行测试：

```bash
# 修改 BASE_URL 为实际地址
export BASE_URL="https://yourdomain.com/iapp_api/api"
bash test_api.sh
```

## API 接口文档

### 基础 URL

```
https://yourdomain.com/iapp_api/api
```

### 认证方式

需要认证的接口，在请求头中添加：

```
Authorization: Bearer <token>
```

### 接口列表

#### 用户模块

| 接口 | 方法 | 认证 | 说明 |
|------|------|------|------|
| /user/register | POST | 否 | 主账号注册 |
| /user/register_sub | POST | 是 (owner) | 子用户注册（需要 owner token） |
| /user/login | POST | 否 | 用户登录 |
| /user/logout | POST | 是 | 退出登录 |
| /user/info | GET | 是 | 获取用户信息 |

#### 应用管理

| 接口 | 方法 | 认证 | 说明 |
|------|------|------|------|
| /app/create | POST | 是 | 创建应用（仅主账号） |
| /app/list | GET | 是 | 获取我的应用列表 |

#### 版本管理

| 接口 | 方法 | 认证 | 说明 |
|------|------|------|------|
| /version/update | POST | 是 (owner) | 更新版本配置（仅主账号） |
| /version/check | GET | 是 | 版本检测（所有人可读） |

#### 公告管理

| 接口 | 方法 | 认证 | 说明 |
|------|------|------|------|
| /announcement/create | POST | 是 (owner) | 创建公告（仅主账号） |
| /announcement/update | POST | 是 (owner) | 更新公告（仅主账号） |
| /announcement/delete | POST | 是 (owner) | 删除公告（仅主账号） |
| /announcement/list | GET | 是 | 公告列表（所有人可读） |
| /announcement/detail/{id} | GET | 是 | 公告详情（所有人可读） |

#### 启动图配置

| 接口 | 方法 | 认证 | 说明 |
|------|------|------|------|
| /splash/config | POST | 是 (owner) | 设置启动图（仅主账号） |
| /splash/config | GET | 是 | 获取启动图（所有人可读） |

#### 空白文档

| 接口 | 方法 | 认证 | 说明 |
|------|------|------|------|
| /doc/get | GET | 是 | 获取文档（所有人可读） |
| /doc/set | POST | 是 (owner) | 设置字段（仅主账号，支持深度合并） |
| /doc/delete | POST | 是 (owner) | 删除字段（仅主账号，支持点号路径） |
| /doc/clear | POST | 是 (owner) | 清空文档（仅主账号） |

### 请求参数说明

#### GET 请求
- `app_uuid` 通过 query 参数传递
- 示例：`GET /api/version/check?app_uuid=xxx&client_version_code=101`

#### POST/PUT/DELETE 请求
- `app_uuid` 通过 JSON 请求体传递
- Content-Type: application/json
- 示例：
```json
{
  "app_uuid": "550e8400-e29b-41d4-a716-446655440000",
  "data": { "key": "value" }
}
```

### 返回格式

所有接口统一返回 JSON：

```json
{
  "code": 200,
  "message": "成功",
  "data": {}
}
```

### 状态码

- `200` - 成功
- `400` - 客户端错误（参数错误等）
- `401` - 未认证（Token 无效或过期）
- `403` - 权限不足（无权访问应用或非 owner）
- `404` - 资源不存在
- `500` - 服务器内部错误

## 使用流程示例

### 1. 主账号创建应用

```bash
# 注册主账号
curl -X POST /api/user/register \
  -H "Content-Type: application/json" \
  -d '{"username":"owner","password":"123456"}'

# 登录
curl -X POST /api/user/login \
  -H "Content-Type: application/json" \
  -d '{"username":"owner","password":"123456"}'
# 返回 token

# 创建应用
curl -X POST /api/app/create \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"app_name":"我的应用"}'
# 返回 app_uuid
```

### 2. 添加子用户

```bash
curl -X POST /api/user/register_sub \
  -H "Authorization: Bearer <owner_token>" \
  -H "Content-Type: application/json" \
  -d '{
    "username":"subuser",
    "password":"123456",
    "app_uuid":"<app_uuid>"
  }'
```

### 3. 更新版本配置

```bash
curl -X POST /api/version/update \
  -H "Authorization: Bearer <owner_token>" \
  -H "Content-Type: application/json" \
  -d '{
    "app_uuid":"<app_uuid>",
    "version_code":102,
    "version_name":"1.0.2",
    "download_url":"https://example.com/app.apk",
    "update_content":"修复 bug",
    "force_update":0
  }'
```

### 4. 客户端检查版本

```bash
curl -X GET "/api/version/check?app_uuid=<app_uuid>&client_version_code=101" \
  -H "Authorization: Bearer <token>"
```

### 5. 空白文档操作

```bash
# 设置字段（深度合并）
curl -X POST /api/doc/set \
  -H "Authorization: Bearer <owner_token>" \
  -H "Content-Type: application/json" \
  -d '{
    "app_uuid":"<app_uuid>",
    "data":{"key1":"value1","nested":{"sub":"data"}}
  }'

# 获取文档
curl -X GET "/api/doc/get?app_uuid=<app_uuid>" \
  -H "Authorization: Bearer <token>"

# 删除字段（支持点号路径）
curl -X POST /api/doc/delete \
  -H "Authorization: Bearer <owner_token>" \
  -H "Content-Type: application/json" \
  -d '{
    "app_uuid":"<app_uuid>",
    "keys":["key1","nested.sub"]
  }'
```

## 权限控制说明

### 权限检查流程

1. 从请求头提取 Token，验证用户身份
2. 从请求中提取 `app_uuid`
3. 查询 `user_app_relations` 表，获取用户在该应用中的角色
4. 根据角色判断是否有权限执行操作：
   - `owner`：可读可写
   - `readonly`：只读

### 403 错误场景

- 用户不属于该应用（无关联记录）
- 子用户尝试执行写操作（更新版本、创建公告、修改文档等）

## 安全建议

1. **生产环境关闭错误显示**
   ```php
   // index.php
   ini_set('display_errors', 0);
   ```

2. **强制 HTTPS**
   在 `.htaccess` 中添加：
   ```apache
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

3. **定期备份数据库**

4. **Token 有效期**
   可在 `config/app.php` 中修改：
   ```php
   'token_expire_days' => 30,
   ```

## 常见问题

### 1. 404 错误
- 检查 `.htaccess` 是否生效
- 确认 Apache 启用了 `mod_rewrite`
- 确认 `RewriteBase` 路径是否正确

### 2. 数据库连接失败
- 检查 `config/database.php` 配置
- 确认数据库已创建
- 确认用户权限正确

### 3. Token 无效
- 确认 Token 未过期（默认 30 天）
- 确认请求头格式正确：`Authorization: Bearer <token>`

### 4. 403 权限不足
- 确认用户已添加到应用（通过 `user_app_relations` 表）
- 确认操作与角色匹配（写操作需要 owner）

## 更新日志

- v2.0.0 - 重构为多应用架构
  - 新增应用管理模块
  - 新增主账号/子用户角色体系
  - 所有功能绑定到 app_uuid
  - 完善权限控制
