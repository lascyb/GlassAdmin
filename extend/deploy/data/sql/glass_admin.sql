/*
 Navicat Premium Dump SQL

 Source Server         : glass_admin
 Source Server Type    : MySQL
 Source Server Version : 80400 (8.4.0)
 Source Host           : 192.168.64.132:3306
 Source Schema         : glass_admin

 Target Server Type    : MySQL
 Target Server Version : 80400 (8.4.0)
 File Encoding         : 65001

 Date: 31/05/2026 10:06:47
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for admin
-- ----------------------------
DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `username` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '登录名',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'password_hash bcrypt',
  `nickname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '昵称',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '头像相对路径（public/storage 下，如 avatar/xxx.jpg）',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '{1:正常,0:禁用}',
  `role_id` bigint UNSIGNED NULL DEFAULT NULL COMMENT '主角色 auth_role.id',
  `create_time` bigint NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` bigint NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_username`(`username` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '后台管理员账号' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admin
-- ----------------------------
INSERT INTO `admin` VALUES (1, 'admin', '$2y$10$xwPex1q7KkR6aDhR2UtMAuYtYxRH8bN.bSXeyiQFs7IaKpYOfJq0a', '管理员', 'avatar/fd75e560334c440fffe9c6b359e006e5.png', 1, 1, NULL, 1780159132);
INSERT INTO `admin` VALUES (3, 'black', '$2y$12$AY55kBfDoQM8cyw9BXoreeIPqq659OJ2oiJ61D0pWO7U9EOW8bNHS', 'lascyb', 'storage/avatar/27f3488295fb624974736daf3148d3ae.jpg', 1, 11, 1778745753, 1778839154);

-- ----------------------------
-- Table structure for admin_notification
-- ----------------------------
DROP TABLE IF EXISTS `admin_notification`;
CREATE TABLE `admin_notification`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `admin_id` bigint UNSIGNED NOT NULL COMMENT '接收人管理员 id',
  `category` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system' COMMENT '{system:系统,friend:好友}',
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '标题',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '正文',
  `read_at` datetime NULL DEFAULT NULL COMMENT '已读时间，空为未读',
  `create_time` datetime NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_admin_read_time`(`admin_id` ASC, `read_at` ASC, `create_time` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '管理员站内通知' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admin_notification
-- ----------------------------
INSERT INTO `admin_notification` VALUES (1, 1, 'system', '欢迎使用 GlassAdmin', '您可在「个人中心」维护资料并查看消息。', NULL, '2026-05-15 17:11:49');
INSERT INTO `admin_notification` VALUES (2, 1, 'system', '安全提示', '请定期更换登录密码。', NULL, '2026-05-15 17:11:49');
INSERT INTO `admin_notification` VALUES (3, 3, 'system', '欢迎使用 GlassAdmin', '您可在「个人中心」维护资料并查看消息。', NULL, '2026-05-15 17:11:49');

-- ----------------------------
-- Table structure for admin_operation_log
-- ----------------------------
DROP TABLE IF EXISTS `admin_operation_log`;
CREATE TABLE `admin_operation_log`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `admin_id` bigint UNSIGNED NOT NULL COMMENT '管理员 id',
  `action` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '动作标识，如 profile.update',
  `summary` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '简述',
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '客户端 IP',
  `create_time` datetime NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_admin_time`(`admin_id` ASC, `create_time` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 66 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '管理员操作日志' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admin_operation_log
-- ----------------------------
INSERT INTO `admin_operation_log` VALUES (1, 3, 'profile.update', '更新个人资料', '192.168.22.1', '2026-05-15 17:14:32');
INSERT INTO `admin_operation_log` VALUES (2, 3, 'profile.update', '更新个人资料', '192.168.22.1', '2026-05-15 17:14:40');
INSERT INTO `admin_operation_log` VALUES (3, 3, 'api.PUT.account/notifications/read', 'PUT account/notifications/read', '192.168.22.1', '2026-05-15 17:30:44');
INSERT INTO `admin_operation_log` VALUES (4, 3, 'auth.login.password', '登录成功（密码）', '192.168.22.1', '2026-05-15 17:36:38');
INSERT INTO `admin_operation_log` VALUES (5, 3, 'api.POST.auth/rule/bindApis', 'POST auth/rule/bindApis', '192.168.22.1', '2026-05-15 17:58:46');
INSERT INTO `admin_operation_log` VALUES (6, 3, 'api.POST.auth/rule/bindApis', 'POST auth/rule/bindApis', '192.168.22.1', '2026-05-15 17:58:51');
INSERT INTO `admin_operation_log` VALUES (7, 3, 'api.POST.auth/rule/bindApis', 'POST auth/rule/bindApis', '192.168.22.1', '2026-05-15 17:59:02');
INSERT INTO `admin_operation_log` VALUES (8, 3, 'api.POST.account/profile/avatar', 'POST account/profile/avatar', '192.168.22.1', '2026-05-15 17:59:14');
INSERT INTO `admin_operation_log` VALUES (9, 3, 'auth.login.password', '登录成功（密码）', '192.168.22.1', '2026-05-15 18:25:23');
INSERT INTO `admin_operation_log` VALUES (10, 1, 'auth.login.password', '登录成功（密码）', '192.168.64.1', '2026-05-30 10:00:21');
INSERT INTO `admin_operation_log` VALUES (11, 1, 'auth.login.password', '登录成功（密码）', '192.168.64.1', '2026-05-30 20:53:24');
INSERT INTO `admin_operation_log` VALUES (12, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=213', '192.168.64.1', '2026-05-30 20:55:32');
INSERT INTO `admin_operation_log` VALUES (13, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=221', '192.168.64.1', '2026-05-30 20:55:51');
INSERT INTO `admin_operation_log` VALUES (14, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=222', '192.168.64.1', '2026-05-30 20:56:01');
INSERT INTO `admin_operation_log` VALUES (15, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=223', '192.168.64.1', '2026-05-30 20:56:10');
INSERT INTO `admin_operation_log` VALUES (16, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=212', '192.168.64.1', '2026-05-30 20:56:45');
INSERT INTO `admin_operation_log` VALUES (17, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=213', '192.168.64.1', '2026-05-30 20:56:54');
INSERT INTO `admin_operation_log` VALUES (18, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=213', '192.168.64.1', '2026-05-30 20:58:36');
INSERT INTO `admin_operation_log` VALUES (19, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=213', '192.168.64.1', '2026-05-30 21:09:12');
INSERT INTO `admin_operation_log` VALUES (20, 1, 'api.PUT.system/config/update', 'PUT system/config/update ?id=5', '192.168.64.1', '2026-05-30 21:55:24');
INSERT INTO `admin_operation_log` VALUES (21, 1, 'api.PUT.system/config/update', 'PUT system/config/update ?id=9', '192.168.64.1', '2026-05-30 21:55:34');
INSERT INTO `admin_operation_log` VALUES (22, 1, 'api.DELETE.system/config/delete', 'DELETE system/config/delete ?id=8', '192.168.64.1', '2026-05-30 21:58:03');
INSERT INTO `admin_operation_log` VALUES (23, 1, 'api.DELETE.system/config/delete', 'DELETE system/config/delete ?id=9', '192.168.64.1', '2026-05-30 21:58:06');
INSERT INTO `admin_operation_log` VALUES (24, 1, 'api.DELETE.system/config/delete', 'DELETE system/config/delete ?id=10', '192.168.64.1', '2026-05-30 21:58:08');
INSERT INTO `admin_operation_log` VALUES (25, 1, 'api.DELETE.system/config/delete', 'DELETE system/config/delete ?id=11', '192.168.64.1', '2026-05-30 21:58:11');
INSERT INTO `admin_operation_log` VALUES (26, 1, 'api.DELETE.system/config/delete', 'DELETE system/config/delete ?id=12', '192.168.64.1', '2026-05-30 21:58:13');
INSERT INTO `admin_operation_log` VALUES (27, 1, 'api.POST.system/config/upload', 'POST system/config/upload', '192.168.64.1', '2026-05-30 23:46:34');
INSERT INTO `admin_operation_log` VALUES (28, 1, 'api.PUT.system/config/update', 'PUT system/config/update ?id=2', '192.168.64.1', '2026-05-30 23:46:35');
INSERT INTO `admin_operation_log` VALUES (29, 1, 'api.PUT.system/config/update', 'PUT system/config/update ?id=2', '192.168.64.1', '2026-05-30 23:48:18');
INSERT INTO `admin_operation_log` VALUES (30, 1, 'api.POST.system/config/save', 'POST system/config/save', '192.168.64.1', '2026-05-31 00:20:05');
INSERT INTO `admin_operation_log` VALUES (31, 1, 'api.PUT.system/config/update', 'PUT system/config/update ?id=13', '192.168.64.1', '2026-05-31 00:20:40');
INSERT INTO `admin_operation_log` VALUES (32, 1, 'api.POST.auth/rule/bindApis', 'POST auth/rule/bindApis', '192.168.64.1', '2026-05-31 00:30:28');
INSERT INTO `admin_operation_log` VALUES (33, 1, 'api.POST.auth/rule/save', 'POST auth/rule/save', '192.168.64.1', '2026-05-31 00:30:59');
INSERT INTO `admin_operation_log` VALUES (34, 1, 'api.POST.auth/rule/save', 'POST auth/rule/save', '192.168.64.1', '2026-05-31 00:31:14');
INSERT INTO `admin_operation_log` VALUES (35, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=214', '192.168.64.1', '2026-05-31 00:32:37');
INSERT INTO `admin_operation_log` VALUES (36, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=215', '192.168.64.1', '2026-05-31 00:32:50');
INSERT INTO `admin_operation_log` VALUES (37, 1, 'api.POST.auth/rule/bindApis', 'POST auth/rule/bindApis', '192.168.64.1', '2026-05-31 00:34:33');
INSERT INTO `admin_operation_log` VALUES (38, 1, 'api.POST.auth/rule/bindApis', 'POST auth/rule/bindApis', '192.168.64.1', '2026-05-31 00:34:49');
INSERT INTO `admin_operation_log` VALUES (39, 1, 'api.POST.auth/rule/bindApis', 'POST auth/rule/bindApis', '192.168.64.1', '2026-05-31 00:34:56');
INSERT INTO `admin_operation_log` VALUES (40, 1, 'api.PUT.auth/role/update', 'PUT auth/role/update ?id=11', '192.168.64.1', '2026-05-31 00:35:20');
INSERT INTO `admin_operation_log` VALUES (41, 1, 'api.PUT.auth/role/assignRules', 'PUT auth/role/assignRules ?id=11', '192.168.64.1', '2026-05-31 00:35:20');
INSERT INTO `admin_operation_log` VALUES (42, 1, 'api.POST.account/profile/avatar', 'POST account/profile/avatar', '192.168.64.1', '2026-05-31 00:38:52');
INSERT INTO `admin_operation_log` VALUES (43, 1, 'api.PUT.account/profile', 'PUT account/profile', '192.168.64.1', '2026-05-31 00:38:55');
INSERT INTO `admin_operation_log` VALUES (44, 1, 'auth.login.password', '登录成功（密码）', '192.168.64.1', '2026-05-31 09:43:21');
INSERT INTO `admin_operation_log` VALUES (45, 1, 'api.PUT.system/config/update', 'PUT system/config/update ?id=3', '192.168.64.1', '2026-05-31 09:43:53');
INSERT INTO `admin_operation_log` VALUES (46, 1, 'api.PUT.system/config/update', 'PUT system/config/update ?id=13', '192.168.64.1', '2026-05-31 09:44:10');
INSERT INTO `admin_operation_log` VALUES (47, 1, 'api.POST.auth/rule/save', 'POST auth/rule/save', '192.168.64.1', '2026-05-31 09:46:04');
INSERT INTO `admin_operation_log` VALUES (48, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=226', '192.168.64.1', '2026-05-31 09:51:04');
INSERT INTO `admin_operation_log` VALUES (49, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=16', '192.168.64.1', '2026-05-31 09:51:17');
INSERT INTO `admin_operation_log` VALUES (50, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=17', '192.168.64.1', '2026-05-31 09:51:26');
INSERT INTO `admin_operation_log` VALUES (51, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=19', '192.168.64.1', '2026-05-31 09:51:43');
INSERT INTO `admin_operation_log` VALUES (52, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=18', '192.168.64.1', '2026-05-31 09:51:55');
INSERT INTO `admin_operation_log` VALUES (53, 1, 'api.POST.auth/rule/save', 'POST auth/rule/save', '192.168.64.1', '2026-05-31 09:52:30');
INSERT INTO `admin_operation_log` VALUES (54, 1, 'api.POST.auth/rule/save', 'POST auth/rule/save', '192.168.64.1', '2026-05-31 09:52:38');
INSERT INTO `admin_operation_log` VALUES (55, 1, 'api.POST.auth/rule/save', 'POST auth/rule/save', '192.168.64.1', '2026-05-31 09:52:50');
INSERT INTO `admin_operation_log` VALUES (56, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=227', '192.168.64.1', '2026-05-31 09:52:59');
INSERT INTO `admin_operation_log` VALUES (57, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=26', '192.168.64.1', '2026-05-31 09:53:13');
INSERT INTO `admin_operation_log` VALUES (58, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=27', '192.168.64.1', '2026-05-31 09:53:21');
INSERT INTO `admin_operation_log` VALUES (59, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=28', '192.168.64.1', '2026-05-31 09:53:30');
INSERT INTO `admin_operation_log` VALUES (60, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=29', '192.168.64.1', '2026-05-31 09:53:39');
INSERT INTO `admin_operation_log` VALUES (61, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=31', '192.168.64.1', '2026-05-31 09:53:48');
INSERT INTO `admin_operation_log` VALUES (62, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=30', '192.168.64.1', '2026-05-31 09:53:55');
INSERT INTO `admin_operation_log` VALUES (63, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=212', '192.168.64.1', '2026-05-31 09:56:41');
INSERT INTO `admin_operation_log` VALUES (64, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=102', '192.168.64.1', '2026-05-31 09:56:47');
INSERT INTO `admin_operation_log` VALUES (65, 1, 'api.PUT.auth/rule/update', 'PUT auth/rule/update ?id=208', '192.168.64.1', '2026-05-31 09:56:52');

-- ----------------------------
-- Table structure for auth_role
-- ----------------------------
DROP TABLE IF EXISTS `auth_role`;
CREATE TABLE `auth_role`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `pid` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '父级 id，0 为根（仅展示，无权限继承）',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '角色名',
  `desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '描述',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '{1:启用,0:禁用}',
  `create_time` datetime NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_pid`(`pid` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 12 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '权限角色' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of auth_role
-- ----------------------------
INSERT INTO `auth_role` VALUES (1, 0, '超级管理员', '拥有全部菜单（id=1 特例：未勾选也视为全量）', 0, 1, NULL, NULL);
INSERT INTO `auth_role` VALUES (10, 0, '领导权限', '领导权限-只能查看、不能编辑', 0, 1, '2026-05-13 15:20:53', '2026-05-14 16:47:19');
INSERT INTO `auth_role` VALUES (11, 1, '系统权限', '拥有所有权限，但需要进行鉴权逻辑', 0, 1, '2026-05-14 16:47:45', '2026-05-15 15:17:35');

-- ----------------------------
-- Table structure for auth_role_rule
-- ----------------------------
DROP TABLE IF EXISTS `auth_role_rule`;
CREATE TABLE `auth_role_rule`  (
  `role_id` bigint UNSIGNED NOT NULL COMMENT '角色 id',
  `rule_id` bigint UNSIGNED NOT NULL COMMENT 'auth_rule.id，且 scope=frontend',
  PRIMARY KEY (`role_id`, `rule_id`) USING BTREE,
  INDEX `idx_rule`(`rule_id` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '角色-前端规则' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of auth_role_rule
-- ----------------------------
INSERT INTO `auth_role_rule` VALUES (10, 100);
INSERT INTO `auth_role_rule` VALUES (11, 100);
INSERT INTO `auth_role_rule` VALUES (11, 102);
INSERT INTO `auth_role_rule` VALUES (10, 103);
INSERT INTO `auth_role_rule` VALUES (11, 103);
INSERT INTO `auth_role_rule` VALUES (11, 104);
INSERT INTO `auth_role_rule` VALUES (11, 105);
INSERT INTO `auth_role_rule` VALUES (11, 106);
INSERT INTO `auth_role_rule` VALUES (10, 107);
INSERT INTO `auth_role_rule` VALUES (11, 107);
INSERT INTO `auth_role_rule` VALUES (10, 108);
INSERT INTO `auth_role_rule` VALUES (11, 108);
INSERT INTO `auth_role_rule` VALUES (11, 202);
INSERT INTO `auth_role_rule` VALUES (11, 203);
INSERT INTO `auth_role_rule` VALUES (11, 204);
INSERT INTO `auth_role_rule` VALUES (11, 205);
INSERT INTO `auth_role_rule` VALUES (11, 206);
INSERT INTO `auth_role_rule` VALUES (11, 207);
INSERT INTO `auth_role_rule` VALUES (11, 208);
INSERT INTO `auth_role_rule` VALUES (11, 209);
INSERT INTO `auth_role_rule` VALUES (11, 210);
INSERT INTO `auth_role_rule` VALUES (11, 211);
INSERT INTO `auth_role_rule` VALUES (11, 212);
INSERT INTO `auth_role_rule` VALUES (11, 213);
INSERT INTO `auth_role_rule` VALUES (11, 221);
INSERT INTO `auth_role_rule` VALUES (11, 222);
INSERT INTO `auth_role_rule` VALUES (11, 223);

-- ----------------------------
-- Table structure for auth_rule
-- ----------------------------
DROP TABLE IF EXISTS `auth_rule`;
CREATE TABLE `auth_rule`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `scope` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '{backend:后端,frontend:前端}',
  `type` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'backend:{menu,api} frontend:{menu,view,btn}',
  `pid` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '父级 id，0 为根',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '名称',
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '后端 api 路径或前端 path 片段',
  `method` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'HTTP 方法，api 用，如 GET POST *',
  `component` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '前端组件路径，view 用',
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '图标：{ep:ElementPlus图标组件名,url:图片绝对或相对地址}',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '{1:启用,0:禁用}',
  `create_time` datetime NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_scope_pid`(`scope` ASC, `pid` ASC) USING BTREE,
  INDEX `idx_scope_status`(`scope` ASC, `status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 230 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '权限规则（后端/前端双域树）' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of auth_rule
-- ----------------------------
INSERT INTO `auth_rule` VALUES (1, 'backend', 'menu', 0, '后端接口权限', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (2, 'backend', 'api', 16, '规则树', 'auth/rule/tree', 'GET', NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (3, 'backend', 'api', 16, '规则详情', 'auth/rule/read', 'GET', NULL, NULL, 1, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (4, 'backend', 'api', 16, '新增规则', 'auth/rule/save', 'POST', NULL, NULL, 2, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (5, 'backend', 'api', 16, '更新规则', 'auth/rule/update', 'PUT', NULL, NULL, 3, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (6, 'backend', 'api', 16, '删除规则', 'auth/rule/delete', 'DELETE', NULL, NULL, 4, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (7, 'backend', 'api', 16, '绑定后端接口', 'auth/rule/bindApis', 'POST', NULL, NULL, 5, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (9, 'backend', 'api', 17, '角色树', 'auth/role/tree', 'GET', NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (10, 'backend', 'api', 17, '角色详情', 'auth/role/read', 'GET', NULL, NULL, 1, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (11, 'backend', 'api', 17, '新增角色', 'auth/role/save', 'POST', NULL, NULL, 2, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (12, 'backend', 'api', 17, '更新角色', 'auth/role/update', 'PUT', NULL, NULL, 3, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (13, 'backend', 'api', 17, '删除角色', 'auth/role/delete', 'DELETE', NULL, NULL, 4, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (14, 'backend', 'api', 17, '分配前端规则', 'auth/role/assignRules', 'PUT', NULL, NULL, 5, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (15, 'backend', 'api', 18, '动态菜单', 'menu/menus', 'GET', NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (16, 'backend', 'menu', 226, '规则管理', NULL, NULL, NULL, NULL, 400, 1, NULL, '2026-05-31 09:51:18');
INSERT INTO `auth_rule` VALUES (17, 'backend', 'menu', 226, '角色管理', NULL, NULL, NULL, NULL, 300, 1, NULL, '2026-05-31 09:51:27');
INSERT INTO `auth_rule` VALUES (18, 'backend', 'menu', 1, '菜单与导航', NULL, NULL, NULL, NULL, 0, 1, NULL, '2026-05-31 09:51:56');
INSERT INTO `auth_rule` VALUES (19, 'backend', 'menu', 226, '管理员', NULL, NULL, NULL, NULL, 200, 1, NULL, '2026-05-31 09:51:43');
INSERT INTO `auth_rule` VALUES (20, 'backend', 'api', 19, '管理员列表', 'auth/admin/list', 'GET', NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (21, 'backend', 'api', 19, '管理员详情', 'auth/admin/read', 'GET', NULL, NULL, 1, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (22, 'backend', 'api', 19, '新增管理员', 'auth/admin/save', 'POST', NULL, NULL, 2, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (23, 'backend', 'api', 19, '更新管理员', 'auth/admin/update', 'PUT', NULL, NULL, 3, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (24, 'backend', 'api', 19, '删除管理员', 'auth/admin/delete', 'DELETE', NULL, NULL, 4, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (25, 'backend', 'menu', 1, '账号中心接口', NULL, NULL, NULL, NULL, 500, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (26, 'backend', 'api', 228, '当前用户资料', 'account/profile', 'GET', NULL, NULL, 0, 1, NULL, '2026-05-31 09:53:13');
INSERT INTO `auth_rule` VALUES (27, 'backend', 'api', 228, '更新当前用户资料', 'account/profile', 'PUT', NULL, NULL, 1, 1, NULL, '2026-05-31 09:53:22');
INSERT INTO `auth_rule` VALUES (28, 'backend', 'api', 227, '操作日志列表', 'account/operation-logs', 'GET', NULL, NULL, 2, 1, NULL, '2026-05-31 09:53:30');
INSERT INTO `auth_rule` VALUES (29, 'backend', 'api', 229, '通知列表', 'account/notifications', 'GET', NULL, NULL, 3, 1, NULL, '2026-05-31 09:53:39');
INSERT INTO `auth_rule` VALUES (30, 'backend', 'api', 229, '通知已读', 'account/notifications/read', 'PUT', NULL, NULL, 4, 1, NULL, '2026-05-31 09:53:56');
INSERT INTO `auth_rule` VALUES (31, 'backend', 'api', 228, '上传头像', 'account/profile/avatar', 'POST', NULL, NULL, 5, 1, NULL, '2026-05-31 09:53:49');
INSERT INTO `auth_rule` VALUES (100, 'frontend', 'view', 0, '仪表盘', 'dashboard', NULL, 'dashboard/index.vue', 'el:DataAnalysis', 0, 1, NULL, '2026-05-12 16:06:24');
INSERT INTO `auth_rule` VALUES (102, 'frontend', 'menu', 0, '权限管理', 'auth', NULL, NULL, 'el:Avatar', 200, 1, NULL, '2026-05-31 09:56:47');
INSERT INTO `auth_rule` VALUES (103, 'frontend', 'view', 102, '角色管理', 'role', NULL, 'auth/role/index.vue', 'el:User', 20, 1, NULL, '2026-05-14 16:41:58');
INSERT INTO `auth_rule` VALUES (104, 'frontend', 'btn', 103, '添加', 'add', NULL, NULL, 'el:Plus', 0, 1, NULL, '2026-05-13 14:36:32');
INSERT INTO `auth_rule` VALUES (105, 'frontend', 'btn', 103, '编辑', 'edit', NULL, NULL, 'el:EditPen', 1, 1, NULL, '2026-05-13 14:36:18');
INSERT INTO `auth_rule` VALUES (106, 'frontend', 'btn', 103, '删除', 'del', NULL, NULL, 'el:Delete', 2, 1, NULL, '2026-05-13 14:36:24');
INSERT INTO `auth_rule` VALUES (107, 'frontend', 'view', 102, '规则管理', 'rule', NULL, 'auth/rule/index.vue', 'el:Tickets', 30, 1, NULL, '2026-05-14 16:42:05');
INSERT INTO `auth_rule` VALUES (108, 'frontend', 'view', 102, '管理员', 'admin', NULL, 'auth/admin/index.vue', 'el:UserFilled', 10, 1, NULL, '2026-05-14 16:41:52');
INSERT INTO `auth_rule` VALUES (202, 'frontend', 'btn', 108, '添加', 'add', NULL, NULL, 'el:Plus', 0, 1, '2026-05-14 16:45:07', '2026-05-14 16:45:07');
INSERT INTO `auth_rule` VALUES (203, 'frontend', 'btn', 108, '编辑', 'edit', NULL, NULL, 'el:EditPen', 0, 1, '2026-05-14 16:46:02', '2026-05-14 16:46:02');
INSERT INTO `auth_rule` VALUES (204, 'frontend', 'btn', 108, '删除', 'del', NULL, NULL, 'el:Delete', 0, 1, '2026-05-14 16:46:15', '2026-05-14 16:46:15');
INSERT INTO `auth_rule` VALUES (205, 'frontend', 'btn', 107, '添加', 'add', NULL, NULL, 'el:Plus', 0, 1, '2026-05-14 17:09:06', '2026-05-14 17:09:06');
INSERT INTO `auth_rule` VALUES (206, 'frontend', 'btn', 107, '编辑', 'edit', NULL, NULL, 'el:EditPen', 0, 1, '2026-05-14 17:09:22', '2026-05-14 17:09:22');
INSERT INTO `auth_rule` VALUES (207, 'frontend', 'btn', 107, '删除', 'del', NULL, NULL, 'el:Delete', 0, 1, '2026-05-14 17:09:32', '2026-05-14 17:09:32');
INSERT INTO `auth_rule` VALUES (208, 'frontend', 'menu', 0, '个人中心', 'account', NULL, NULL, 'el:UserFilled', 300, 1, NULL, '2026-05-31 09:56:52');
INSERT INTO `auth_rule` VALUES (209, 'frontend', 'view', 208, '个人信息', 'profile', NULL, 'account/profile', 'el:User', 0, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (210, 'frontend', 'view', 208, '操作日志', 'logs', NULL, 'account/operation-logs', 'el:Document', 1, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (211, 'frontend', 'view', 208, '消息通知', 'notifications', NULL, 'account/notifications', 'el:Bell', 2, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (212, 'frontend', 'menu', 0, '系统管理', 'system', NULL, NULL, 'Setting', 100, 1, '0000-00-00 00:00:00', '2026-05-31 09:56:42');
INSERT INTO `auth_rule` VALUES (213, 'frontend', 'view', 212, '系统配置', 'config', NULL, 'system/config/index.vue', 'el:Monitor', 901, 1, '0000-00-00 00:00:00', '2026-05-30 21:09:12');
INSERT INTO `auth_rule` VALUES (214, 'backend', 'api', 225, '系统配置列表', 'system/config/list', 'GET', NULL, '', 0, 1, '0000-00-00 00:00:00', '2026-05-31 00:32:37');
INSERT INTO `auth_rule` VALUES (215, 'backend', 'api', 225, '查看系统配置', 'system/config/read', 'GET', NULL, '', 0, 1, '0000-00-00 00:00:00', '2026-05-31 00:32:50');
INSERT INTO `auth_rule` VALUES (216, 'backend', 'api', 225, '系统配置分组列表', 'system/config/groups', 'GET', NULL, '', 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `auth_rule` VALUES (217, 'backend', 'api', 225, '新增系统配置', 'system/config/save', 'POST', NULL, '', 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `auth_rule` VALUES (218, 'backend', 'api', 225, '更新系统配置', 'system/config/update', 'PUT', NULL, '', 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `auth_rule` VALUES (219, 'backend', 'api', 225, '删除系统配置', 'system/config/delete', 'DELETE', NULL, '', 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `auth_rule` VALUES (220, 'backend', 'api', 225, '批量保存系统配置', 'system/config/batchSave', 'PUT', NULL, '', 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `auth_rule` VALUES (221, 'frontend', 'btn', 213, '新增', 'add', NULL, NULL, NULL, 902, 1, '0000-00-00 00:00:00', '2026-05-30 20:55:52');
INSERT INTO `auth_rule` VALUES (222, 'frontend', 'btn', 213, '编辑', 'edit', NULL, NULL, NULL, 903, 1, '0000-00-00 00:00:00', '2026-05-30 20:56:02');
INSERT INTO `auth_rule` VALUES (223, 'frontend', 'btn', 213, '删除', 'del', NULL, NULL, NULL, 904, 1, '0000-00-00 00:00:00', '2026-05-30 20:56:10');
INSERT INTO `auth_rule` VALUES (224, 'backend', 'menu', 1, '系统管理', NULL, NULL, NULL, NULL, 0, 1, '2026-05-31 00:30:59', '2026-05-31 00:30:59');
INSERT INTO `auth_rule` VALUES (225, 'backend', 'menu', 224, '系统配置', NULL, NULL, NULL, NULL, 10, 1, '2026-05-31 00:31:15', '2026-05-31 00:31:15');
INSERT INTO `auth_rule` VALUES (226, 'backend', 'menu', 1, '权限管理', NULL, NULL, NULL, NULL, 20, 1, '2026-05-31 09:46:04', '2026-05-31 09:51:05');
INSERT INTO `auth_rule` VALUES (227, 'backend', 'menu', 25, '操作日志', NULL, NULL, NULL, NULL, 20, 1, '2026-05-31 09:52:30', '2026-05-31 09:53:00');
INSERT INTO `auth_rule` VALUES (228, 'backend', 'menu', 25, '个人信息', NULL, NULL, NULL, NULL, 0, 1, '2026-05-31 09:52:38', '2026-05-31 09:52:38');
INSERT INTO `auth_rule` VALUES (229, 'backend', 'menu', 25, '消息通知', NULL, NULL, NULL, NULL, 30, 1, '2026-05-31 09:52:51', '2026-05-31 09:52:51');

-- ----------------------------
-- Table structure for auth_rule_frontend_api
-- ----------------------------
DROP TABLE IF EXISTS `auth_rule_frontend_api`;
CREATE TABLE `auth_rule_frontend_api`  (
  `frontend_rule_id` bigint UNSIGNED NOT NULL COMMENT 'auth_rule id，scope=frontend 且 type 为 view 或 btn',
  `backend_rule_id` bigint UNSIGNED NOT NULL COMMENT 'auth_rule id，scope=backend 且 type=api',
  PRIMARY KEY (`frontend_rule_id`, `backend_rule_id`) USING BTREE,
  INDEX `idx_backend`(`backend_rule_id` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '前端规则-后端接口' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of auth_rule_frontend_api
-- ----------------------------
INSERT INTO `auth_rule_frontend_api` VALUES (107, 2);
INSERT INTO `auth_rule_frontend_api` VALUES (107, 3);
INSERT INTO `auth_rule_frontend_api` VALUES (205, 4);
INSERT INTO `auth_rule_frontend_api` VALUES (206, 5);
INSERT INTO `auth_rule_frontend_api` VALUES (207, 6);
INSERT INTO `auth_rule_frontend_api` VALUES (205, 7);
INSERT INTO `auth_rule_frontend_api` VALUES (206, 7);
INSERT INTO `auth_rule_frontend_api` VALUES (207, 7);
INSERT INTO `auth_rule_frontend_api` VALUES (103, 9);
INSERT INTO `auth_rule_frontend_api` VALUES (103, 10);
INSERT INTO `auth_rule_frontend_api` VALUES (104, 11);
INSERT INTO `auth_rule_frontend_api` VALUES (105, 12);
INSERT INTO `auth_rule_frontend_api` VALUES (106, 13);
INSERT INTO `auth_rule_frontend_api` VALUES (104, 14);
INSERT INTO `auth_rule_frontend_api` VALUES (105, 14);
INSERT INTO `auth_rule_frontend_api` VALUES (106, 14);
INSERT INTO `auth_rule_frontend_api` VALUES (108, 20);
INSERT INTO `auth_rule_frontend_api` VALUES (108, 21);
INSERT INTO `auth_rule_frontend_api` VALUES (202, 22);
INSERT INTO `auth_rule_frontend_api` VALUES (203, 23);
INSERT INTO `auth_rule_frontend_api` VALUES (204, 24);
INSERT INTO `auth_rule_frontend_api` VALUES (209, 26);
INSERT INTO `auth_rule_frontend_api` VALUES (209, 27);
INSERT INTO `auth_rule_frontend_api` VALUES (210, 28);
INSERT INTO `auth_rule_frontend_api` VALUES (211, 29);
INSERT INTO `auth_rule_frontend_api` VALUES (211, 30);
INSERT INTO `auth_rule_frontend_api` VALUES (209, 31);
INSERT INTO `auth_rule_frontend_api` VALUES (213, 214);
INSERT INTO `auth_rule_frontend_api` VALUES (213, 215);
INSERT INTO `auth_rule_frontend_api` VALUES (213, 216);
INSERT INTO `auth_rule_frontend_api` VALUES (221, 217);
INSERT INTO `auth_rule_frontend_api` VALUES (222, 218);
INSERT INTO `auth_rule_frontend_api` VALUES (223, 219);

-- ----------------------------
-- Table structure for rsa_key
-- ----------------------------
DROP TABLE IF EXISTS `rsa_key`;
CREATE TABLE `rsa_key`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `serial` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '密钥序列号',
  `public_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'RSA公钥PEM',
  `private_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'RSA私钥PEM',
  `expire_time` bigint NOT NULL COMMENT '过期时间',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '{1:有效,0:失效}',
  `create_time` bigint NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` bigint NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_serial`(`serial` ASC) USING BTREE,
  INDEX `idx_expire_time`(`expire_time` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '登录RSA密钥' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of rsa_key
-- ----------------------------
INSERT INTO `rsa_key` VALUES (1, '6a1a44b11007c', '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvJgcSXM72WLH3aRBU2Bk\noIvP3/400fNbPPdFpvYv8XLkwyq3ftFJsFxE7hryLyDddTDVGcjHS0j6vERiY6O7\nt31cVE1eNK156aWrylWijX4fQRAxbvRPl+0K75NQ8VtxjwwtkNxepBiORGRZm4Xc\n/FXzTAx8wWyp14rMm61h8mzRTFdrpazy22UkegiMC/OB19xOqYS0hDnSFCicTT+C\n1ysi2BQwEjn97Gn/N1pYlWGltQQrxkuVcAPCnzuKyqXt22MEz2ZDZv7YAaJiysjM\n1pR+KWT1DTEzbCIZfTMEmgVfLvKLbL5iBFanrS+u3y4k0jzT1l3PRyvtWYHz283c\n4QIDAQAB\n-----END PUBLIC KEY-----\n', '-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC8mBxJczvZYsfd\npEFTYGSgi8/f/jTR81s890Wm9i/xcuTDKrd+0UmwXETuGvIvIN11MNUZyMdLSPq8\nRGJjo7u3fVxUTV40rXnppavKVaKNfh9BEDFu9E+X7Qrvk1DxW3GPDC2Q3F6kGI5E\nZFmbhdz8VfNMDHzBbKnXisybrWHybNFMV2ulrPLbZSR6CIwL84HX3E6phLSEOdIU\nKJxNP4LXKyLYFDASOf3saf83WliVYaW1BCvGS5VwA8KfO4rKpe3bYwTPZkNm/tgB\nomLKyMzWlH4pZPUNMTNsIhl9MwSaBV8u8otsvmIEVqetL67fLiTSPNPWXc9HK+1Z\ngfPbzdzhAgMBAAECggEBALm188zkO2AiZHu+HlHTDuQFKSsCMNM5l3OebyktLiLN\np7UD4HNx9eVDxuDNCgUckNawwnfEfXOGJtmzYUuK1POn735YPVcUKIPKjzMMRqic\nNHymTHdc/u+1DVrDLyasjFN3g3stdciTIaz5QD/kfqrAnHcbqNHzhf/o/vihsFy9\n1eaZrq742XfZI5Xvoy4vIuPg9GMpzrGqT96l/qEF6kcjZimbWko2wnTXB6oN1sOQ\ney/C6bpzowdPWHterYFCSH+AyiZFUlQvx+foJujmKkOgPtsoidUVguihSIcewH1/\np+0pbwciKbDvNI9vG234isN8xX9lA3yPuk5/dii410ECgYEA9DmHr2iuSwyKwG1U\n1pMV59BCfcXqa0oRUj0SzVJX/+kky/L0Yl6xPyDfD1B1uoE7k2sOBduLB82W3gYX\nqnyFVtI864D3cv7wS1QDwKNI2W8j7tavQImA+xlkYa0Qd6Yss0ewclN3KwO7DE6Y\njEkkVS+orYrFcW/ORUBzHOmwZekCgYEAxa/uhFCsEGw3GBmnICcu5NIHf386nUdX\nCUWAsaQqz8h62zFsojj1aAGVw7wb0h02Q9uKSKk+CM4YRe4BjjtFEOfkxz17Dq4M\nHQ99QtjIE0I0dNOzsKTu3kuTgp25JzjQMlccGE+hjWliZzL02ivOdNBs4cR5g1FU\nVsvuftFWTDkCgYA4gVMGX4br8EPuD52wY6mOz92CchcxqZpmV6ERBjs8B3ovAdBP\nLi9+NqHFTk1e0GWzTvKYoloBfSypw+kX1eWX196mcllOLvPzycVwfZbuT8uVh/BW\nUzoXXqGeA8eqlzUCadTGLs+EkUFmsIyU8JtKZlN1i4TxIPgth6Lajqr0eQKBgDXH\nn+bFH5o0JmTbDblUj5BWhw2raiHBd68hBlrNY4w1aR7aekhDoJeT3s9qDFOD+PBK\n+Hq9+A9jKf1UO34bJ7XqsDV3x1N+C/OX9CbMXNnIaAbkVP0YUZeODeo5yFYgQZuA\nUTdX6oZTBh83IJ99zWPAE+7ziW8tH/mg7xf3ETgZAoGAQ8e7vd0n65Gx4FHY2ROe\nDS8yyN6WF9yCGeWLuYw1X9bPELL+x0/u+Zp/XwNBVDCI8pRc2SGLtIpXIGJrJAZO\nu1s3CHzxElKwMEkeDN+atSRcxRq/wY7weXMPBxb0F8R25PtCqvEuFjX+Q2/gesqs\n1TUerYgsuun4mBR/5GJpesM=\n-----END PRIVATE KEY-----\n', 1780110017, 1, 1780106417, 1780106417);
INSERT INTO `rsa_key` VALUES (2, '6a1addc43267c', '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAw0ta3vOmDYtla5aHM5nj\nKOhVUIZqkWTe6K3iGuUOksHZzQt7xp38GIEJ43fJ5nyP84LXF5cKUzgcSS68zVrn\nlmmoWoiwj8Fh50KO/z2UiR3suv3qdGmUf2OKyo+/epD9A3vdzLEH9ul5PckmrBox\nXCuBJJ5p0Qvg+Unb+n0rVCrk/GTHRvvzYidIDLlflzWFiFKv++L9sQIsIh0ybd+o\nd9HaURypcio8fPb2VAuKE/BBilQT3BIUJJdohrlHk+gDurS7oN7xQEhkUPwggf+l\nQd7OaH3537YBXVGkorxfR6tmj9kDKWuw6yZlm+809VFH1GHHqIYIu7DTMvvjMNMs\nAwIDAQAB\n-----END PUBLIC KEY-----\n', '-----BEGIN PRIVATE KEY-----\nMIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDDS1re86YNi2Vr\nloczmeMo6FVQhmqRZN7oreIa5Q6SwdnNC3vGnfwYgQnjd8nmfI/zgtcXlwpTOBxJ\nLrzNWueWaahaiLCPwWHnQo7/PZSJHey6/ep0aZR/Y4rKj796kP0De93MsQf26Xk9\nySasGjFcK4EknmnRC+D5Sdv6fStUKuT8ZMdG+/NiJ0gMuV+XNYWIUq/74v2xAiwi\nHTJt36h30dpRHKlyKjx89vZUC4oT8EGKVBPcEhQkl2iGuUeT6AO6tLug3vFASGRQ\n/CCB/6VB3s5offnftgFdUaSivF9Hq2aP2QMpa7DrJmWb7zT1UUfUYceohgi7sNMy\n++Mw0ywDAgMBAAECggEATm2hta2lfwT/GuDdtQRU7KW5NVh1zaPzvP+vsNtSKe+9\nlxHIiHADwJd5sCb4Jx7Y9JMT7D5VACyld2JUoiein44Y9Rq1xVWFCsl5AdnqH9Kr\nskCsekm4QBxqrYRjAkbdxRtmbICf0THb5cs1Q6JVNk2mVF1l43TgyVw3AnZoAylv\nHApe0Cjg10eQTf8fpDafA3uKS+IPKAi2vCfCIo+RlbiJtFfpO6+z8YCZ/jyBlRCT\n/k53nqbLvDYssJQhDC1x2sXboYtQ5peHioGpFqC0GuNVatR3AFZyUj7RlSAua7I/\n+o1gPmWZ43p/h5483t2uUFV4Ok002YgE/0r37U3ZMQKBgQDlYKoKI9EzCqSsxeuU\nBT2Bnrosi3A1R2ArT3/FVksNGqY6NYqa2KLZXWTbowE0JoRhxP/uHD576kdz2glO\ndoko4wqYy6WJPNbOZi3c/ZWd3xh0WoXWQIG87IMIevaPVQLZXFOFbbfod1UCBhYh\nAkAs5F+2H1nlxsKbKi+5dNn46wKBgQDZ9f/UbY0KcG0kvXx+BlZ9V0yRkXUs3sio\n6b2SCAy2UGrYd0xR5MupzlsziRFrJVHfrzSYKifxGgKtXCaxR3ubZG8PqsvgyE9p\n/ZYTbNGmtI24I/P6S2m9LEBJ9t5bgmeKa9sPVgoEpqCFe26kczPWgl6am7oZETAu\nVWz5ayVTSQKBgE4Usq1S9fKfC+F4WjH/+qj2BEAHk0G/sz/rBv6A2nlyeeVUtoLx\nfbXqcdvGSDGdbvDtI4XFfYum+323WfWoqhYN+1x57jaiTCjkZXZVqCmeeO+apPaC\n7OcHx68KVIYEFT0yjYfdCn6KqJhQxBWmc3wUnXQchfiOyU4SdT53RX/TAoGAMiVc\n1JzXxD0Vf0xxJfWDVJcPMLaMhOREukf8q2pasl9SFbdswbKoAMtPRE4WydXuPk5r\nBU89nUTktMnsxQCzOY8j1jDvcyniy3niW1m/6mDD8kejTESpcTKLH/lCMz+PXtNt\ntNuQtn03lT5OuFsg5AtehKerazT/JsnUppW2IrECgYA+XOpLv5wTjThVfUB8YQGa\nIiwTRne/IsvViDzUhH7GSToWplFqX9KomqjhWk0RYeYXLaschXStF1kvTN2th9pb\n+nG7UEEb3b6lLoV2aUm0CZiW4TXvUZXaRa0GTzBxq9nYZVv7+WQ7LId0SEz72OeU\nBdUReQtvu6gXL/f03TSQTA==\n-----END PRIVATE KEY-----\n', 1780149204, 1, 1780145604, 1780145604);
INSERT INTO `rsa_key` VALUES (3, '6a1b92390c41e', '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEApLg0Ejn+mavoxjqjWW0J\n8Gwn/w4ZRjIr4T1jywoxS7huOtZ9Vta7z9L0UMMrMXQBhulI/jvao05MA51BAdl/\nIYQOUXYtHG5pqs1Gz64/FREm183J5NJjFAhq9mnbvbMjNI1CwFAFrUzCSci28jEX\n8Vmh9cD9WQmKXKq4Fe3Iy1UNzl9T/FcZ0kIEqtafVn66UVXWdy1yWfB+QSTZa+aw\nz9/zE9AmHqFG8T72QY+qVzWDRQ7SbOLezGQ0xdk6hyGBeeqhXl8tc3uuxZPRgCFb\n8Di2CGmdht5LFYero8uoHsrBStHeR6mjHQRA7kzOwFbx1WFHXACdsJrsdVxg5KhF\nowIDAQAB\n-----END PUBLIC KEY-----\n', '-----BEGIN PRIVATE KEY-----\nMIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCkuDQSOf6Zq+jG\nOqNZbQnwbCf/DhlGMivhPWPLCjFLuG461n1W1rvP0vRQwysxdAGG6Uj+O9qjTkwD\nnUEB2X8hhA5Rdi0cbmmqzUbPrj8VESbXzcnk0mMUCGr2adu9syM0jULAUAWtTMJJ\nyLbyMRfxWaH1wP1ZCYpcqrgV7cjLVQ3OX1P8VxnSQgSq1p9WfrpRVdZ3LXJZ8H5B\nJNlr5rDP3/MT0CYeoUbxPvZBj6pXNYNFDtJs4t7MZDTF2TqHIYF56qFeXy1ze67F\nk9GAIVvwOLYIaZ2G3ksVh6ujy6geysFK0d5HqaMdBEDuTM7AVvHVYUdcAJ2wmux1\nXGDkqEWjAgMBAAECggEAVM/Df978iINLR0XJ9bF+/JuzvE+2E76heUfyqgiFeUds\n+j6WKrBF46vqv+q9wqjIYH6dZsnKuWsHGJVPc3RZURh9htQiRhFTtu4bF7PQJzi9\nl0AORBNPkHEFIfs/eV1TMyvIwtxxD9oF75e5GtrMSSiti6vixoVzCGOehSv5xzcg\nMYJ3rOkfeamIYYz/5hQ1ymirTJCwFrTxxRFcBJ/h0sk84jiXLpsYEAGzeYI50NKD\ndsjs83h5s30q7E8GyhAMFNtG2ezJ1Td8LNKk/7N5l8Ojefs6m0Y1aSVTeB07phHt\nVV8bK5AyEKqpW7gTFmwRidAGjMOVNEkJjm+7B39CAQKBgQDYhuOkLb2LFvtMvug7\neftV2Y5k1cSt+X4MU/iFzJ2o1Tnahp1RPSeqdfIoe95ceFTmNmkjkWRuEOHS+GMU\nUOtN9NWnH45qfXUz6ZdkZ67pVHjy2McsHcUmcOvpRprDzqfvWpwe5MrByKcj2NRD\nd/y+CgcRWXbxvTrL+C/o4IsH0QKBgQDCv4MklWvxwpzDu7Ni3fBOQ9aS2aQHNBp/\nkSPSTWG+pIDVVsbZcFPPYQHDAV7b3g44uRk2eKVODA54yzN8U2ndTLQlLRDS9/Uc\ngscAzX0PG/PASnhovi6+kEF5jX8oBUKY1Qv8udEuhgjrBmiHxhz20I9f6XSoP9xw\n6QOcno8HMwKBgHJVl/LjrEi70+rMi/iMCulkPcqFDpwHOEXuOTb9uuqh3TDFEGEL\nd9yM91flQJ93cu01IgOEDJIKHBS40Ci0Kew6Mm9TPHKC2NaWrDh6f2P6FPu2+EUV\nWztXOVsuyx5GC2taX0H48g+tkoRdRIhz3WPEC8NfiG1RzLBT14iSuRdhAoGANOKW\n9WdSUeu8cHbXZwDIEijf6y8JGR+wFnZOmPyEj6XSipyvETsst0MrnE4R+XkauZKi\nYyVuFny1rV3ZFGj+dacvwGy1heTp/h0Rd4lV7bV0gwJ2IlOLFBrOfPjM0AxlomUw\nhSfcw1qIr3IlDsHg+bF3Td9y24HEFjFg1rZENvsCgYAjwPmezR/7DGf1XauCo+pC\n64tlzWIrCQhCt+rFwMV6y3ZqV7QnQhcI0WIOwhjhQiB4J3TbjrJAzpHNh/B7ojPh\n/URmhi9EE9bNfTyyc+TrFesNlroq+U6iXXGMowBhEiKwuWAhCCqNUdotzieDxVP1\nyl6XpduEtu4Hqexd3/scow==\n-----END PRIVATE KEY-----\n', 1780195401, 1, 1780191801, 1780191801);

-- ----------------------------
-- Table structure for system_config
-- ----------------------------
DROP TABLE IF EXISTS `system_config`;
CREATE TABLE `system_config`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '配置键名',
  `value` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '配置值（纯文本存储）',
  `group` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'default' COMMENT '配置分组',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '配置名称',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '配置说明',
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'input' COMMENT '输入类型',
  `options` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '选项（JSON数组）',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：2=系统内置，1=启用，0=禁用',
  `create_time` int NOT NULL COMMENT '创建时间',
  `update_time` int NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_key`(`key` ASC) USING BTREE,
  INDEX `idx_group`(`group` ASC) USING BTREE,
  INDEX `idx_status`(`status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 14 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '系统配置' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of system_config
-- ----------------------------
INSERT INTO `system_config` VALUES (1, 'site_title', 'GlassAdmin', 'site', '站点标题', '浏览器标签页标题', 'input', NULL, 10, 2, 1780128248, 1780128248);
INSERT INTO `system_config` VALUES (2, 'site_logo', '', 'site', '站点Logo', '站点Logo图片地址', 'upload', NULL, 20, 2, 1780128248, 1780156098);
INSERT INTO `system_config` VALUES (3, 'site_icp', '鲁ICP备17012218号-6', 'site', '备案号', 'ICP备案号', 'input', NULL, 30, 2, 1780128248, 1780191833);
INSERT INTO `system_config` VALUES (4, 'theme_primary', '#409EFF', 'theme', '主题色', 'Element Plus 主题色', 'color', NULL, 10, 1, 1780128248, 1780128248);
INSERT INTO `system_config` VALUES (5, 'theme_dark', '0', 'theme', '深色模式', '是否启用深色模式', 'switch', NULL, 20, 1, 1780128248, 1780128248);
INSERT INTO `system_config` VALUES (6, 'upload_max_size', '2048', 'upload', '上传大小限制', '单位KB', 'number', NULL, 10, 1, 1780128248, 1780128248);
INSERT INTO `system_config` VALUES (7, 'upload_allowed_types', 'jpg,jpeg,png,gif,webp', 'upload', '允许的文件类型', '逗号分隔', 'input', NULL, 20, 1, 1780128248, 1780128248);
INSERT INTO `system_config` VALUES (13, 'net_sec_reg', '鲁公网安备37172302372109号 ', 'site', '公网安备', '公安备案', 'input', NULL, 100, 1, 1780158005, 1780191850);

SET FOREIGN_KEY_CHECKS = 1;
