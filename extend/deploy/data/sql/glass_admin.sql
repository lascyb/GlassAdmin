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

 Date: 02/08/2026 01:12:03
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for auth_admin
-- ----------------------------
DROP TABLE IF EXISTS `auth_admin`;
CREATE TABLE `auth_admin`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `username` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '登录名',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'password_hash bcrypt',
  `nickname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '昵称',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '头像相对路径（public/storage 下，如 avatar/xxx.jpg）',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '{1:正常,0:禁用}',
  `last_login_time` bigint NULL DEFAULT NULL COMMENT '上次登录时间（Unix 秒）',
  `create_time` bigint NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` bigint NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_username`(`username` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '后台管理员账号' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of auth_admin
-- ----------------------------
INSERT INTO `auth_admin` VALUES (1, 'admin', '$2y$12$D.GdwQ9iSwqTXfKVviejZ.aYHrqI/IdO1OrI5Of.GAF.MnzCnSPOu', '超级管理员', 'avatar/fad633d2de58aaf2e09d26d495de70e1.jpg', 1, 1785603681, NULL, 1785603681);

-- ----------------------------
-- Table structure for auth_admin_operation_log
-- ----------------------------
DROP TABLE IF EXISTS `auth_admin_operation_log`;
CREATE TABLE `auth_admin_operation_log`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `admin_id` bigint UNSIGNED NOT NULL COMMENT '管理员 id',
  `action` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '动作标识，如 profile.update',
  `summary` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '简述',
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '客户端 IP',
  `create_time` datetime NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_admin_time`(`admin_id` ASC, `create_time` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '管理员操作日志' ROW_FORMAT = DYNAMIC;

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
-- Records of auth_admin_role
-- ----------------------------
INSERT INTO `auth_admin_role` VALUES (1, 1, 1);

-- ----------------------------
-- Table structure for auth_oauth2_feishu
-- ----------------------------
DROP TABLE IF EXISTS `auth_oauth2_feishu`;
CREATE TABLE `auth_oauth2_feishu`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `admin_id` bigint UNSIGNED NOT NULL COMMENT '管理员 id',
  `open_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '飞书 open_id',
  `union_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '飞书 union_id',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '飞书昵称',
  `avatar` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '飞书头像 URL',
  `access_token` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'access_token',
  `expires_in` bigint NULL DEFAULT NULL COMMENT 'access_token 过期时间（Unix 秒）',
  `refresh_token` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'refresh_token',
  `refresh_token_expires_in` bigint NULL DEFAULT NULL COMMENT 'refresh_token 过期时间（Unix 秒）',
  `scope` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '授权 scope',
  `create_time` bigint NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` bigint NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_union_id`(`union_id` ASC) USING BTREE,
  INDEX `idx_admin_id`(`admin_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '管理员飞书 OAuth 绑定' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of auth_oauth2_feishu
-- ----------------------------

-- ----------------------------
-- Table structure for auth_role
-- ----------------------------
DROP TABLE IF EXISTS `auth_role`;
CREATE TABLE `auth_role`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `pid` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '父级 id，0 为根（仅展示，无权限继承）',
  `code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '角色识别编码',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '角色名',
  `desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '描述',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '{1:启用,0:禁用}',
  `create_time` datetime NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_pid`(`pid` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '权限角色' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of auth_role
-- ----------------------------
INSERT INTO `auth_role` VALUES (1, 0, 'SUPER', '超级管理员', '拥有全部菜单（id=1 特例：未勾选也视为全量）', 0, 1, NULL, '2026-08-01 22:09:34');

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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '权限规则（后端/前端双域树）' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of auth_rule
-- ----------------------------
INSERT INTO `auth_rule` VALUES (1, 'backend', 'menu', 0, '权限管理', NULL, NULL, NULL, NULL, 20, 1, NULL, '2026-07-20 17:09:25');
INSERT INTO `auth_rule` VALUES (2, 'backend', 'api', 16, '规则树', 'auth.Rule/tree', 'GET', NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (3, 'backend', 'api', 16, '规则详情', 'auth.Rule/read', 'GET', NULL, NULL, 1, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (4, 'backend', 'api', 16, '新增规则', 'auth.Rule/save', 'POST', NULL, NULL, 2, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (5, 'backend', 'api', 16, '更新规则', 'auth.Rule/update', 'PUT', NULL, NULL, 3, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (6, 'backend', 'api', 16, '删除规则', 'auth.Rule/delete', 'DELETE', NULL, NULL, 4, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (7, 'backend', 'api', 16, '绑定后端接口', 'auth.Rule/bindApis', 'POST', NULL, NULL, 5, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (9, 'backend', 'api', 17, '角色树', 'auth.Role/tree', 'GET', NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (10, 'backend', 'api', 17, '角色详情', 'auth.Role/read', 'GET', NULL, NULL, 1, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (11, 'backend', 'api', 17, '新增角色', 'auth.Role/save', 'POST', NULL, NULL, 2, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (12, 'backend', 'api', 17, '更新角色', 'auth.Role/update', 'PUT', NULL, NULL, 3, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (13, 'backend', 'api', 17, '删除角色', 'auth.Role/delete', 'DELETE', NULL, NULL, 4, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (14, 'backend', 'api', 17, '分配前端规则', 'auth.Role/assignRules', 'PUT', NULL, NULL, 5, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (16, 'backend', 'menu', 1, '规则管理', NULL, NULL, NULL, NULL, 30, 1, NULL, '2026-07-20 17:09:48');
INSERT INTO `auth_rule` VALUES (17, 'backend', 'menu', 1, '角色管理', NULL, NULL, NULL, NULL, 20, 1, NULL, '2026-07-20 17:09:41');
INSERT INTO `auth_rule` VALUES (19, 'backend', 'menu', 1, '管理员', NULL, NULL, NULL, NULL, 10, 1, NULL, '2026-07-20 17:09:33');
INSERT INTO `auth_rule` VALUES (20, 'backend', 'api', 19, '管理员列表', 'auth.Admin/list', 'GET', NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (21, 'backend', 'api', 19, '管理员详情', 'auth.Admin/read', 'GET', NULL, NULL, 1, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (22, 'backend', 'api', 19, '新增管理员', 'auth.Admin/save', 'POST', NULL, NULL, 2, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (23, 'backend', 'api', 19, '更新管理员', 'auth.Admin/update', 'PUT', NULL, NULL, 3, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (24, 'backend', 'api', 19, '删除管理员', 'auth.Admin/delete', 'DELETE', NULL, NULL, 4, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (25, 'frontend', 'menu', 0, '仪表盘', 'dashboard', NULL, NULL, 'el:DataAnalysis', 0, 1, NULL, '2026-07-20 17:26:46');
INSERT INTO `auth_rule` VALUES (26, 'frontend', 'menu', 0, '权限管理', 'auth', NULL, NULL, 'el:Avatar', 20, 1, NULL, '2026-05-12 16:09:25');
INSERT INTO `auth_rule` VALUES (27, 'frontend', 'view', 26, '角色管理', 'role', NULL, 'auth/role/index.vue', 'el:User', 20, 1, NULL, '2026-05-14 16:41:58');
INSERT INTO `auth_rule` VALUES (28, 'frontend', 'btn', 27, '添加', 'add', NULL, NULL, 'el:Plus', 0, 1, NULL, '2026-05-13 14:36:32');
INSERT INTO `auth_rule` VALUES (29, 'frontend', 'btn', 27, '编辑', 'edit', NULL, NULL, 'el:EditPen', 1, 1, NULL, '2026-05-13 14:36:18');
INSERT INTO `auth_rule` VALUES (30, 'frontend', 'btn', 27, '删除', 'del', NULL, NULL, 'el:Delete', 2, 1, NULL, '2026-05-13 14:36:24');
INSERT INTO `auth_rule` VALUES (31, 'frontend', 'view', 26, '规则管理', 'rule', NULL, 'auth/rule/index.vue', 'el:Tickets', 30, 1, NULL, '2026-05-14 16:42:05');
INSERT INTO `auth_rule` VALUES (32, 'frontend', 'view', 26, '管理员', 'admin', NULL, 'auth/admin/index.vue', 'el:UserFilled', 10, 1, NULL, '2026-05-14 16:41:52');
INSERT INTO `auth_rule` VALUES (33, 'frontend', 'btn', 32, '添加', 'add', NULL, NULL, 'el:Plus', 0, 1, '2026-05-14 16:45:07', '2026-05-14 16:45:07');
INSERT INTO `auth_rule` VALUES (34, 'frontend', 'btn', 32, '编辑', 'edit', NULL, NULL, 'el:EditPen', 1, 1, '2026-05-14 16:46:02', '2026-05-14 16:46:02');
INSERT INTO `auth_rule` VALUES (35, 'frontend', 'btn', 32, '删除', 'del', NULL, NULL, 'el:Delete', 2, 1, '2026-05-14 16:46:15', '2026-05-14 16:46:15');
INSERT INTO `auth_rule` VALUES (36, 'frontend', 'btn', 31, '添加', 'add', NULL, NULL, 'el:Plus', 0, 1, '2026-05-14 17:09:06', '2026-05-14 17:09:06');
INSERT INTO `auth_rule` VALUES (37, 'frontend', 'btn', 31, '编辑', 'edit', NULL, NULL, 'el:EditPen', 1, 1, '2026-05-14 17:09:22', '2026-05-14 17:09:22');
INSERT INTO `auth_rule` VALUES (38, 'frontend', 'btn', 31, '删除', 'del', NULL, NULL, 'el:Delete', 2, 1, '2026-05-14 17:09:32', '2026-05-14 17:09:32');
INSERT INTO `auth_rule` VALUES (39, 'frontend', 'view', 25, '系统使用情况', 'usage', NULL, 'dashboard/usage', 'el:Histogram', 0, 1, '2026-07-08 10:52:52', '2026-07-08 10:55:56');
INSERT INTO `auth_rule` VALUES (40, 'backend', 'menu', 0, '仪表盘', NULL, NULL, NULL, NULL, 0, 1, '2026-07-08 10:53:10', '2026-07-20 17:08:24');
INSERT INTO `auth_rule` VALUES (41, 'backend', 'api', 70, '系统使用情况', 'dashboard.Usage/index', 'GET', NULL, NULL, 0, 1, '2026-07-08 10:53:40', '2026-08-01 12:50:17');
INSERT INTO `auth_rule` VALUES (42, 'backend', 'menu', 69, 'CURD工具', NULL, NULL, NULL, NULL, 0, 1, '2026-07-09 17:25:26', '2026-07-21 17:52:07');
INSERT INTO `auth_rule` VALUES (43, 'backend', 'api', 42, 'CRUD样例列表', 'dev.curd.Index/samples', 'GET', NULL, NULL, 0, 1, '2026-07-09 17:25:26', '2026-07-09 17:25:26');
INSERT INTO `auth_rule` VALUES (44, 'backend', 'api', 42, 'CRUD样例详情', 'dev.curd.Index/sample', 'GET', NULL, NULL, 1, 1, '2026-07-09 17:25:26', '2026-07-09 17:25:26');
INSERT INTO `auth_rule` VALUES (45, 'backend', 'api', 42, 'CRUD执行生成', 'dev.curd.Index/generate', 'POST', NULL, NULL, 2, 1, '2026-07-09 17:25:26', '2026-07-09 17:25:26');
INSERT INTO `auth_rule` VALUES (46, 'backend', 'api', 42, 'CRUD历史列表', 'dev.curd.History/index', 'GET', NULL, NULL, 3, 1, '2026-07-09 17:25:26', '2026-07-09 17:25:26');
INSERT INTO `auth_rule` VALUES (47, 'backend', 'api', 42, 'CRUD历史详情', 'dev.curd.History/read', 'GET', NULL, NULL, 4, 1, '2026-07-09 17:25:26', '2026-07-09 17:25:26');
INSERT INTO `auth_rule` VALUES (48, 'frontend', 'menu', 0, '开发', 'dev', NULL, NULL, 'el:SetUp', 90, 1, '2026-07-09 17:25:26', '2026-07-09 17:25:26');
INSERT INTO `auth_rule` VALUES (49, 'frontend', 'view', 48, 'CRUD生成', 'curd', NULL, 'dev/curd/index.vue', 'el:Cpu', 0, 1, '2026-07-09 17:25:26', '2026-07-09 17:25:26');
INSERT INTO `auth_rule` VALUES (50, 'backend', 'api', 42, 'CRUD数据表列表', 'dev.curd.Index/tables', 'GET', NULL, NULL, 5, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (51, 'backend', 'api', 42, 'CRUD表结构反推', 'dev.curd.Index/tableSchema', 'GET', NULL, NULL, 6, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (52, 'frontend', 'view', 25, '服务器性能', 'server', NULL, 'dashboard/server', 'el:Monitor', 10, 1, '2026-07-17 17:50:00', '2026-07-17 17:50:00');
INSERT INTO `auth_rule` VALUES (53, 'backend', 'api', 71, '服务器性能', 'dashboard.Server/index', 'GET', NULL, NULL, 0, 1, '2026-07-17 17:50:00', '2026-08-01 12:50:24');
INSERT INTO `auth_rule` VALUES (54, 'frontend', 'menu', 0, '常规管理', 'system', NULL, NULL, 'el:SetUp', 10, 1, '2026-07-20 13:51:07', '2026-07-21 17:45:28');
INSERT INTO `auth_rule` VALUES (55, 'frontend', 'view', 54, '全局配置', 'config', NULL, 'system/config/index.vue', 'el:Setting', 0, 1, '2026-07-20 13:52:00', '2026-08-01 14:09:21');
INSERT INTO `auth_rule` VALUES (56, 'backend', 'menu', 0, '常规管理', NULL, NULL, NULL, NULL, 10, 1, '2026-07-20 13:52:26', '2026-07-21 17:48:15');
INSERT INTO `auth_rule` VALUES (57, 'backend', 'menu', 56, '系统配置', NULL, NULL, NULL, NULL, 0, 1, '2026-07-20 13:53:08', '2026-07-20 13:53:08');
INSERT INTO `auth_rule` VALUES (58, 'backend', 'api', 57, '列表', 'system.Config/list', 'GET', NULL, NULL, 0, 1, '2026-07-20 13:53:36', '2026-07-20 13:54:03');
INSERT INTO `auth_rule` VALUES (59, 'backend', 'api', 57, '配置详情', 'system.Config/read', 'GET', NULL, NULL, 1, 1, '2026-07-20 13:53:49', '2026-07-20 13:53:49');
INSERT INTO `auth_rule` VALUES (60, 'backend', 'api', 57, '配置分组', 'system.Config/groups', 'GET', NULL, NULL, 2, 1, '2026-07-20 13:54:30', '2026-07-20 13:54:30');
INSERT INTO `auth_rule` VALUES (61, 'backend', 'api', 57, '新建配置', 'system.Config/save', 'POST', NULL, NULL, 3, 1, '2026-07-20 13:54:48', '2026-07-20 13:54:48');
INSERT INTO `auth_rule` VALUES (62, 'backend', 'api', 57, '更新配置', 'system.Config/update', 'PUT', NULL, NULL, 4, 1, '2026-07-20 13:55:06', '2026-07-20 13:55:06');
INSERT INTO `auth_rule` VALUES (63, 'backend', 'api', 57, '删除配置', 'system.Config/delete', 'DELETE', NULL, NULL, 5, 1, '2026-07-20 13:55:26', '2026-07-20 13:55:26');
INSERT INTO `auth_rule` VALUES (64, 'backend', 'api', 57, '批量保存配置(按 group)', 'system.Config/batchSave', 'PUT', NULL, NULL, 6, 1, '2026-07-20 13:55:58', '2026-07-20 13:56:31');
INSERT INTO `auth_rule` VALUES (65, 'backend', 'menu', 56, '个人资料', NULL, NULL, NULL, NULL, 20, 1, NULL, '2026-07-21 17:48:30');
INSERT INTO `auth_rule` VALUES (66, 'backend', 'api', 65, '修改密码', 'account.Profile/changePassword', 'PUT', NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `auth_rule` VALUES (67, 'frontend', 'btn', 68, '修改密码', 'changePassword', NULL, NULL, 'el:Key', 0, 1, NULL, '2026-07-20 17:27:45');
INSERT INTO `auth_rule` VALUES (68, 'frontend', 'view', 54, '个人资料', 'account', NULL, 'system/account/profile.vue', 'el:UserFilled', 20, 1, '2026-07-20 17:27:38', '2026-08-01 17:30:09');
INSERT INTO `auth_rule` VALUES (69, 'backend', 'menu', 0, '开发', NULL, NULL, NULL, NULL, 90, 1, '2026-07-21 17:51:50', '2026-07-21 17:51:50');
INSERT INTO `auth_rule` VALUES (70, 'backend', 'menu', 40, '系统使用情况', NULL, NULL, NULL, NULL, 10, 1, '2026-07-21 17:53:07', '2026-07-21 17:53:07');
INSERT INTO `auth_rule` VALUES (71, 'backend', 'menu', 40, '服务器性能', NULL, NULL, NULL, NULL, 20, 1, '2026-07-21 17:53:19', '2026-07-21 17:53:19');
INSERT INTO `auth_rule` VALUES (72, 'frontend', 'view', 54, '附件管理', 'attachment', NULL, 'system/attachment/index.vue', 'el:FolderOpened', 10, 1, '2026-08-01 14:03:50', '2026-08-01 14:09:27');
INSERT INTO `auth_rule` VALUES (73, 'frontend', 'btn', 72, '删除', 'del', NULL, NULL, 'el:Delete', 0, 1, '2026-08-01 14:03:50', '2026-08-01 14:03:50');
INSERT INTO `auth_rule` VALUES (74, 'backend', 'menu', 56, '附件管理', NULL, NULL, NULL, NULL, 10, 1, '2026-08-01 14:03:50', '2026-08-01 14:03:50');
INSERT INTO `auth_rule` VALUES (75, 'backend', 'api', 74, '列表', 'system.Attachment/list', 'GET', NULL, NULL, 0, 1, '2026-08-01 14:03:50', '2026-08-01 14:03:50');
INSERT INTO `auth_rule` VALUES (76, 'backend', 'api', 74, '详情', 'system.Attachment/read', 'GET', NULL, NULL, 1, 1, '2026-08-01 14:03:50', '2026-08-01 14:03:50');
INSERT INTO `auth_rule` VALUES (77, 'backend', 'api', 74, '删除', 'system.Attachment/delete', 'DELETE', NULL, NULL, 2, 1, '2026-08-01 14:03:50', '2026-08-01 14:03:50');
INSERT INTO `auth_rule` VALUES (78, 'backend', 'api', 57, '读取配置值', 'system.Config/values', 'GET', NULL, NULL, 7, 1, '2026-08-02 01:10:49', '2026-08-02 01:10:49');

-- ----------------------------
-- Table structure for auth_rule_api
-- ----------------------------
DROP TABLE IF EXISTS `auth_rule_api`;
CREATE TABLE `auth_rule_api`  (
  `frontend_rule_id` bigint UNSIGNED NOT NULL COMMENT 'auth_rule id，scope=frontend 且 type 为 view 或 btn',
  `backend_rule_id` bigint UNSIGNED NOT NULL COMMENT 'auth_rule id，scope=backend 且 type=api',
  PRIMARY KEY (`frontend_rule_id`, `backend_rule_id`) USING BTREE,
  INDEX `idx_backend`(`backend_rule_id` ASC) USING BTREE,
  CONSTRAINT `frontend_rule_id` FOREIGN KEY (`frontend_rule_id`) REFERENCES `auth_rule` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `backend_rule_id` FOREIGN KEY (`backend_rule_id`) REFERENCES `auth_rule` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '前端规则-后端接口' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of auth_rule_api
-- ----------------------------
INSERT INTO `auth_rule_api` VALUES (31, 2);
INSERT INTO `auth_rule_api` VALUES (31, 3);
INSERT INTO `auth_rule_api` VALUES (36, 4);
INSERT INTO `auth_rule_api` VALUES (37, 5);
INSERT INTO `auth_rule_api` VALUES (38, 6);
INSERT INTO `auth_rule_api` VALUES (36, 7);
INSERT INTO `auth_rule_api` VALUES (37, 7);
INSERT INTO `auth_rule_api` VALUES (38, 7);
INSERT INTO `auth_rule_api` VALUES (27, 9);
INSERT INTO `auth_rule_api` VALUES (27, 10);
INSERT INTO `auth_rule_api` VALUES (28, 11);
INSERT INTO `auth_rule_api` VALUES (29, 12);
INSERT INTO `auth_rule_api` VALUES (30, 13);
INSERT INTO `auth_rule_api` VALUES (28, 14);
INSERT INTO `auth_rule_api` VALUES (29, 14);
INSERT INTO `auth_rule_api` VALUES (30, 14);
INSERT INTO `auth_rule_api` VALUES (32, 20);
INSERT INTO `auth_rule_api` VALUES (32, 21);
INSERT INTO `auth_rule_api` VALUES (33, 22);
INSERT INTO `auth_rule_api` VALUES (34, 23);
INSERT INTO `auth_rule_api` VALUES (35, 24);
INSERT INTO `auth_rule_api` VALUES (39, 41);
INSERT INTO `auth_rule_api` VALUES (49, 43);
INSERT INTO `auth_rule_api` VALUES (49, 44);
INSERT INTO `auth_rule_api` VALUES (49, 45);
INSERT INTO `auth_rule_api` VALUES (49, 46);
INSERT INTO `auth_rule_api` VALUES (49, 47);
INSERT INTO `auth_rule_api` VALUES (49, 50);
INSERT INTO `auth_rule_api` VALUES (49, 51);
INSERT INTO `auth_rule_api` VALUES (52, 53);
INSERT INTO `auth_rule_api` VALUES (55, 58);
INSERT INTO `auth_rule_api` VALUES (55, 59);
INSERT INTO `auth_rule_api` VALUES (55, 60);
INSERT INTO `auth_rule_api` VALUES (55, 61);
INSERT INTO `auth_rule_api` VALUES (55, 62);
INSERT INTO `auth_rule_api` VALUES (55, 63);
INSERT INTO `auth_rule_api` VALUES (55, 64);
INSERT INTO `auth_rule_api` VALUES (67, 66);
INSERT INTO `auth_rule_api` VALUES (72, 75);
INSERT INTO `auth_rule_api` VALUES (72, 76);
INSERT INTO `auth_rule_api` VALUES (73, 77);
INSERT INTO `auth_rule_api` VALUES (55, 78);

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
-- Records of dev_crud_history
-- ----------------------------

-- ----------------------------
-- Table structure for system_attachment
-- ----------------------------
DROP TABLE IF EXISTS `system_attachment`;
CREATE TABLE `system_attachment`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '原始文件名',
  `path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '相对存储盘根路径',
  `disk` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public' COMMENT '存储磁盘',
  `mime` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'MIME 类型',
  `ext` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '扩展名',
  `size` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '字节大小',
  `scene` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '上传接口路径',
  `sha1` char(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '文件哈希',
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '系统附件' ROW_FORMAT = DYNAMIC;


-- ----------------------------
-- Table structure for system_config
-- ----------------------------
DROP TABLE IF EXISTS `system_config`;
CREATE TABLE `system_config`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '配置键名（唯一）',
  `value` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置值',
  `group` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default' COMMENT '配置分组',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '配置名称',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '配置说明',
  `type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'input' COMMENT '{input:单行,textarea:多行,select:单选,multi_select:多选,switch:开关,number:数值,color:颜色}',
  `options` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '选项 JSON 数组',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '{1:启用,0:禁用,2:系统内置}',
  `create_time` bigint NULL DEFAULT NULL COMMENT '创建时间（Unix 秒）',
  `update_time` bigint NULL DEFAULT NULL COMMENT '更新时间（Unix 秒）',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_key`(`key` ASC) USING BTREE,
  INDEX `idx_group_sort`(`group` ASC, `sort` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '系统配置' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of system_config
-- ----------------------------
INSERT INTO `system_config` VALUES (1, 'site_title', 'GlassAdmin', '全局配置', '网站名', NULL, 'input', NULL, 0, 2, 1784527288, 1784529606);
INSERT INTO `system_config` VALUES (4, 'upload_max_bytes', '10485760', '上传配置', '默认上传大小上限', '通用文件上传最大字节数（字节）', 'number', NULL, 10, 2, 1785567993, 1785567993);
INSERT INTO `system_config` VALUES (5, 'upload_allowed_ext', 'jpg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip,txt', '上传配置', '默认允许扩展名', '逗号分隔，如 jpg,png,pdf', 'input', NULL, 20, 2, 1785567993, 1785567993);
INSERT INTO `system_config` VALUES (6, 'upload_allowed_mime', '', '上传配置', '默认允许 MIME', '逗号分隔；留空表示不校验 MIME', 'input', NULL, 30, 2, 1785567993, 1785567993);
INSERT INTO `system_config` VALUES (7, 'config_group_sort', '上传配置;全局配置;', '全局配置', '配置分组排序', NULL, 'input', NULL, 0, 2, 1785568826, 1785568989);

-- ----------------------------
-- Table structure for system_rsa_key
-- ----------------------------
DROP TABLE IF EXISTS `system_rsa_key`;
CREATE TABLE `system_rsa_key`  (
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '登录RSA密钥' ROW_FORMAT = DYNAMIC;

SET FOREIGN_KEY_CHECKS = 1;
