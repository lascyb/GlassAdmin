/*
 Navicat Premium Data Transfer

 Source Server         : lascyb
 Source Server Type    : MySQL
 Source Server Version : 80400 (8.4.0)
 Source Host           : 127.0.0.1:3306
 Source Schema         : glass_admin

 Target Server Type    : MySQL
 Target Server Version : 80400 (8.4.0)
 File Encoding         : 65001

 Date: 14/08/2026 17:11:39
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for auth_admin
-- ----------------------------
DROP TABLE IF EXISTS `auth_admin`;
CREATE TABLE `auth_admin`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `username` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '登录名',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT 'password_hash bcrypt',
  `nickname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '昵称',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '头像相对路径（public/storage 下，如 avatar/xxx.jpg）',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '{1:正常,0:禁用}',
  `last_login_time` bigint NULL DEFAULT NULL COMMENT '上次登录时间（Unix 秒）',
  `create_time` bigint NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` bigint NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_username`(`username` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '后台管理员账号' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for auth_admin_operation_log
-- ----------------------------
DROP TABLE IF EXISTS `auth_admin_operation_log`;
CREATE TABLE `auth_admin_operation_log`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `admin_id` bigint UNSIGNED NOT NULL COMMENT '管理员 id',
  `action` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '动作标识，如 profile.update',
  `summary` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '简述',
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '客户端 IP',
  `create_time` datetime NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_admin_time`(`admin_id` ASC, `create_time` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '管理员操作日志' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for auth_admin_role
-- ----------------------------
DROP TABLE IF EXISTS `auth_admin_role`;
CREATE TABLE `auth_admin_role`  (
  `admin_id` bigint UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL,
  `enabled` tinyint NOT NULL DEFAULT 1 COMMENT '{1:启用,0:停用}',
  PRIMARY KEY (`admin_id`, `role_id`) USING BTREE,
  INDEX `idx_role`(`role_id` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for auth_oauth2_feishu
-- ----------------------------
DROP TABLE IF EXISTS `auth_oauth2_feishu`;
CREATE TABLE `auth_oauth2_feishu`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `admin_id` bigint UNSIGNED NOT NULL COMMENT '管理员 id',
  `user_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '飞书 user_id',
  `open_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '飞书 open_id',
  `union_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '飞书 union_id',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '飞书昵称',
  `avatar` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '飞书头像 URL',
  `access_token` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT 'access_token',
  `expires_in` bigint NULL DEFAULT NULL COMMENT 'access_token 过期时间（Unix 秒）',
  `refresh_token` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT 'refresh_token',
  `refresh_token_expires_in` bigint NULL DEFAULT NULL COMMENT 'refresh_token 过期时间（Unix 秒）',
  `scope` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '授权 scope',
  `create_time` bigint NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` bigint NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_union_id`(`union_id` ASC) USING BTREE,
  INDEX `idx_admin_id`(`admin_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '管理员飞书 OAuth 绑定' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for auth_role
-- ----------------------------
DROP TABLE IF EXISTS `auth_role`;
CREATE TABLE `auth_role`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `pid` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '父级 id，0 为根（仅展示，无权限继承）',
  `code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '角色识别编码',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '角色名',
  `desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '描述',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '{1:启用,0:禁用}',
  `create_time` datetime NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_pid`(`pid` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '权限角色' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for auth_role_rule
-- ----------------------------
DROP TABLE IF EXISTS `auth_role_rule`;
CREATE TABLE `auth_role_rule`  (
  `role_id` bigint UNSIGNED NOT NULL COMMENT '角色 id',
  `rule_id` bigint UNSIGNED NOT NULL COMMENT 'auth_rule.id，且 scope=frontend',
  PRIMARY KEY (`role_id`, `rule_id`) USING BTREE,
  INDEX `idx_rule`(`rule_id` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '角色-前端规则' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for auth_rule
-- ----------------------------
DROP TABLE IF EXISTS `auth_rule`;
CREATE TABLE `auth_rule`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键；1–10000 系统内置，自增从 10001 起',
  `scope` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '{backend:后端,frontend:前端}',
  `type` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT 'backend:{menu,api} frontend:{menu,view,route,btn,link,iframe}',
  `pid` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '父级 id，0 为根',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '名称',
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '后端 api 路径或前端 path 片段',
  `method` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT 'HTTP 方法，api 用，如 GET POST *',
  `component` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '前端组件路径或外链/内嵌 URL',
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '图标：{ep:ElementPlus图标组件名,url:图片绝对或相对地址}',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '{1:启用,0:禁用}',
  `visibility` tinyint NOT NULL DEFAULT 0 COMMENT '{0:授权后侧栏可见,1:展示全部时可发现,2:不入侧栏}',
  `keep_alive` tinyint NOT NULL DEFAULT 1 COMMENT '{1:页面缓存,0:离开即销毁} 仅 frontend view/route 有效',
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '功能描述（无权限页展示）',
  `create_time` datetime NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_scope_pid`(`scope` ASC, `pid` ASC) USING BTREE,
  INDEX `idx_scope_status`(`scope` ASC, `status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 10001 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '权限规则（后端/前端双域树）' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for auth_rule_api
-- ----------------------------
DROP TABLE IF EXISTS `auth_rule_api`;
CREATE TABLE `auth_rule_api`  (
  `frontend_rule_id` bigint UNSIGNED NOT NULL COMMENT 'auth_rule id，scope=frontend 且 type 为 view 或 btn',
  `backend_rule_id` bigint UNSIGNED NOT NULL COMMENT 'auth_rule id，scope=backend 且 type=api',
  PRIMARY KEY (`frontend_rule_id`, `backend_rule_id`) USING BTREE,
  INDEX `idx_backend`(`backend_rule_id` ASC) USING BTREE,
  CONSTRAINT `backend_rule_id` FOREIGN KEY (`backend_rule_id`) REFERENCES `auth_rule` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `frontend_rule_id` FOREIGN KEY (`frontend_rule_id`) REFERENCES `auth_rule` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '前端规则-后端接口' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for dev_crud_history
-- ----------------------------
DROP TABLE IF EXISTS `dev_crud_history`;
CREATE TABLE `dev_crud_history`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '方案名',
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '方案配置',
  `fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '字段配置',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 27 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for phinxlog
-- ----------------------------
DROP TABLE IF EXISTS `system_db_version`;
CREATE TABLE `system_db_version` (
  `version` bigint NOT NULL,
  `migration_name` varchar(100) DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `breakpoint` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='数据库升级账本（Phinx）';
-- ----------------------------
-- Table structure for system_attachment
-- ----------------------------
DROP TABLE IF EXISTS `system_attachment`;
CREATE TABLE `system_attachment`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '原始文件名',
  `path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '相对存储盘根路径',
  `disk` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'public' COMMENT '存储磁盘',
  `mime` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT 'MIME 类型',
  `ext` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '扩展名',
  `size` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '字节大小',
  `scene` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '上传接口路径',
  `sha1` char(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '文件哈希',
  `admin_id` bigint UNSIGNED NULL DEFAULT NULL COMMENT '上传人 auth_admin.id',
  `file_deleted` tinyint NOT NULL DEFAULT 0 COMMENT '{0:文件在盘,1:物理文件已删除}',
  `create_time` bigint NULL DEFAULT NULL COMMENT '创建时间（Unix 秒）',
  `update_time` bigint NULL DEFAULT NULL COMMENT '更新时间（Unix 秒）',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_scene`(`scene` ASC) USING BTREE,
  INDEX `idx_sha1`(`sha1` ASC) USING BTREE,
  INDEX `idx_create_time`(`create_time` ASC) USING BTREE,
  INDEX `idx_admin`(`admin_id` ASC) USING BTREE,
  INDEX `idx_file_deleted`(`file_deleted` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '系统附件' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for system_config
-- ----------------------------
DROP TABLE IF EXISTS `system_config`;
CREATE TABLE `system_config`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '配置键名（唯一）',
  `value` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '配置值',
  `group` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'default' COMMENT '配置分组',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '配置名称',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '配置说明',
  `type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'input' COMMENT '{input:单行,textarea:多行,select:单选,multi_select:多选,switch:开关,number:数值,color:颜色}',
  `options` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '选项 JSON 数组',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '{1:启用,0:禁用,2:系统内置}',
  `create_time` bigint NULL DEFAULT NULL COMMENT '创建时间（Unix 秒）',
  `update_time` bigint NULL DEFAULT NULL COMMENT '更新时间（Unix 秒）',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_key`(`key` ASC) USING BTREE,
  INDEX `idx_group_sort`(`group` ASC, `sort` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 11 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '系统配置' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for system_rsa_key
-- ----------------------------
DROP TABLE IF EXISTS `system_rsa_key`;
CREATE TABLE `system_rsa_key`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `serial` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '密钥序列号',
  `public_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT 'RSA公钥PEM',
  `private_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT 'RSA私钥PEM',
  `expire_time` bigint NOT NULL COMMENT '过期时间',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '{1:有效,0:失效}',
  `create_time` bigint NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` bigint NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_serial`(`serial` ASC) USING BTREE,
  INDEX `idx_expire_time`(`expire_time` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '登录RSA密钥' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for system_workbench_shortcut
-- ----------------------------
DROP TABLE IF EXISTS `system_workbench_shortcut`;
CREATE TABLE `system_workbench_shortcut`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `admin_id` bigint UNSIGNED NOT NULL COMMENT '管理员 auth_admin.id',
  `rule_id` bigint UNSIGNED NOT NULL COMMENT '前端规则 auth_rule.id',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序（越小越靠前）',
  `create_time` bigint NULL DEFAULT NULL COMMENT '创建时间（Unix 秒）',
  `update_time` bigint NULL DEFAULT NULL COMMENT '更新时间（Unix 秒）',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_admin_rule`(`admin_id` ASC, `rule_id` ASC) USING BTREE,
  INDEX `idx_admin_sort`(`admin_id` ASC, `sort` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '工作台快捷入口（按管理员）' ROW_FORMAT = DYNAMIC;

SET FOREIGN_KEY_CHECKS = 1;
