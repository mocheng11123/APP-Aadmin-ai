-- iApp 管家 API 数据库初始化脚本
-- 数据库名：sqldaytime123

USE `sqldaytime123`;

-- --------------------------------------------------------
-- 用户表（主账号和子账号都是用户）
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL COMMENT '用户名',
  `password` VARCHAR(255) NOT NULL COMMENT '密码（bcrypt 加密）',
  `token` VARCHAR(64) DEFAULT NULL COMMENT '登录 Token',
  `token_expire` TIMESTAMP NULL DEFAULT NULL COMMENT 'Token 过期时间',
  `create_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_token` (`token`),
  KEY `idx_token_expire` (`token_expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- --------------------------------------------------------
-- 应用表
-- --------------------------------------------------------
DROP TABLE IF EXISTS `apps`;
CREATE TABLE `apps` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `app_uuid` VARCHAR(64) NOT NULL COMMENT '应用唯一标识（对外暴露）',
  `app_name` VARCHAR(100) NOT NULL COMMENT '应用名称',
  `owner_user_id` INT(11) UNSIGNED NOT NULL COMMENT '创建该应用的主账号 ID',
  `create_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_uuid` (`app_uuid`),
  KEY `idx_owner_user_id` (`owner_user_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='应用表';

-- --------------------------------------------------------
-- 用户 - 应用关联表（记录用户属于哪个应用以及角色）
-- --------------------------------------------------------
DROP TABLE IF EXISTS `user_app_relations`;
CREATE TABLE `user_app_relations` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT '用户 ID',
  `app_id` INT(11) UNSIGNED NOT NULL COMMENT '应用 ID',
  `role` ENUM('owner', 'readonly') NOT NULL DEFAULT 'readonly' COMMENT '角色：owner 可写，readonly 只读',
  `join_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '加入时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_app` (`user_id`, `app_id`),
  KEY `idx_app_id` (`app_id`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户 - 应用关联表';

-- --------------------------------------------------------
-- 版本配置表（每个应用只有一条当前版本记录）
-- --------------------------------------------------------
DROP TABLE IF EXISTS `app_versions`;
CREATE TABLE `app_versions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `app_id` INT(11) UNSIGNED NOT NULL COMMENT '应用 ID',
  `version_code` INT(11) NOT NULL COMMENT '版本号（整数，用于比较）',
  `version_name` VARCHAR(50) NOT NULL COMMENT '版本名称（如 1.0.1）',
  `download_url` VARCHAR(255) NOT NULL COMMENT 'APK 下载地址',
  `update_content` TEXT COMMENT '更新内容',
  `force_update` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否强制更新（0 否 1 是）',
  `update_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_id` (`app_id`),
  KEY `idx_version_code` (`version_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='应用版本表';

-- --------------------------------------------------------
-- 公告表
-- --------------------------------------------------------
DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `app_id` INT(11) UNSIGNED NOT NULL COMMENT '应用 ID',
  `title` VARCHAR(200) NOT NULL COMMENT '公告标题',
  `content` TEXT NOT NULL COMMENT '公告内容',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否启用（0 禁用 1 启用）',
  `create_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_app_id` (`app_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='公告表';

-- --------------------------------------------------------
-- 启动图配置表（每个应用一条配置）
-- --------------------------------------------------------
DROP TABLE IF EXISTS `splash_configs`;
CREATE TABLE `splash_configs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `app_id` INT(11) UNSIGNED NOT NULL COMMENT '应用 ID',
  `image_url` VARCHAR(255) NOT NULL COMMENT '启动图 URL',
  `duration` INT(11) NOT NULL DEFAULT 3000 COMMENT '显示时长（毫秒）',
  `update_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_id` (`app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='启动图配置表';

-- --------------------------------------------------------
-- 空白文档表（每个应用一个文档）
-- --------------------------------------------------------
DROP TABLE IF EXISTS `uuid_documents`;
CREATE TABLE `uuid_documents` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `app_id` INT(11) UNSIGNED NOT NULL COMMENT '应用 ID',
  `doc_data` LONGTEXT NOT NULL COMMENT '文档数据（JSON 格式）',
  `update_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_id` (`app_id`),
  KEY `idx_update_time` (`update_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='UUID 文档表';

-- --------------------------------------------------------
-- 初始示例数据
-- --------------------------------------------------------

-- 示例应用数据（请先手动创建用户后，再插入关联数据）
-- 以下 SQL 仅供参考，实际需要等用户创建后动态插入
