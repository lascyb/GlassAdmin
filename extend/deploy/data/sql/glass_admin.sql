/*
 Navicat Premium Data Transfer

 Source Server         : vm
 Source Server Type    : MySQL
 Source Server Version : 80400 (8.4.0)
 Source Host           : 192.168.22.129:3306
 Source Schema         : glass_admin

 Target Server Type    : MySQL
 Target Server Version : 80400 (8.4.0)
 File Encoding         : 65001

 Date: 08/07/2026 13:47:54
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for admin
-- ----------------------------
DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin`
(
    `id`              bigint UNSIGNED                                               NOT NULL AUTO_INCREMENT COMMENT '主键',
    `username`        varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NOT NULL COMMENT '登录名',
    `password`        varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'password_hash bcrypt',
    `nickname`        varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL     DEFAULT NULL COMMENT '昵称',
    `avatar`          varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL     DEFAULT NULL COMMENT '头像相对路径（public/storage 下，如 avatar/xxx.jpg）',
    `status`          tinyint                                                       NOT NULL DEFAULT 1 COMMENT '{1:正常,0:禁用}',
    `last_login_time` bigint                                                        NULL     DEFAULT NULL COMMENT '上次登录时间（Unix 秒）',
    `create_time`     bigint                                                        NULL     DEFAULT NULL COMMENT '创建时间',
    `update_time`     bigint                                                        NULL     DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE INDEX `uk_username` (`username` ASC) USING BTREE
) ENGINE = InnoDB
  AUTO_INCREMENT = 4
  CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT = '后台管理员账号'
  ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of admin
-- ----------------------------
INSERT INTO `admin`
VALUES (1, 'admin', '$2y$10$xwPex1q7KkR6aDhR2UtMAuYtYxRH8bN.bSXeyiQFs7IaKpYOfJq0a', '超级管理员', NULL, 1, 1783489606,
        NULL, 1783489606);
INSERT INTO `admin`
VALUES (3, 'black', '$2y$12$tLwn.aFK55sAFoYDvk25xOn0wooLU8qGIFN6zkyvnK1DK2OLOEuv2', 'lascyb',
        'storage/avatar/27f3488295fb624974736daf3148d3ae.jpg', 1, NULL, 1778745753, 1779094795);

-- ----------------------------
-- Table structure for admin_notification
-- ----------------------------
DROP TABLE IF EXISTS `admin_notification`;
CREATE TABLE `admin_notification`
(
    `id`          bigint UNSIGNED                                               NOT NULL AUTO_INCREMENT COMMENT '主键',
    `admin_id`    bigint UNSIGNED                                               NOT NULL COMMENT '接收人管理员 id',
    `category`    varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NOT NULL DEFAULT 'system' COMMENT '{system:系统,friend:好友}',
    `title`       varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '标题',
    `content`     text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci         NULL COMMENT '正文',
    `read_at`     datetime                                                      NULL     DEFAULT NULL COMMENT '已读时间，空为未读',
    `create_time` datetime                                                      NULL     DEFAULT NULL COMMENT '创建时间',
    PRIMARY KEY (`id`) USING BTREE,
    INDEX `idx_admin_read_time` (`admin_id` ASC, `read_at` ASC, `create_time` ASC) USING BTREE
) ENGINE = InnoDB
  AUTO_INCREMENT = 4
  CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT = '管理员站内通知'
  ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of admin_notification
-- ----------------------------

-- ----------------------------
-- Table structure for admin_operation_log
-- ----------------------------
DROP TABLE IF EXISTS `admin_operation_log`;
CREATE TABLE `admin_operation_log`
(
    `id`          bigint UNSIGNED                                               NOT NULL AUTO_INCREMENT COMMENT '主键',
    `admin_id`    bigint UNSIGNED                                               NOT NULL COMMENT '管理员 id',
    `action`      varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NOT NULL COMMENT '动作标识，如 profile.update',
    `summary`     varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '简述',
    `ip`          varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NULL     DEFAULT NULL COMMENT '客户端 IP',
    `create_time` datetime                                                      NULL     DEFAULT NULL COMMENT '创建时间',
    PRIMARY KEY (`id`) USING BTREE,
    INDEX `idx_admin_time` (`admin_id` ASC, `create_time` ASC) USING BTREE
) ENGINE = InnoDB
  AUTO_INCREMENT = 34
  CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT = '管理员操作日志'
  ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of admin_operation_log
-- ----------------------------

-- ----------------------------
-- Table structure for admin_role
-- ----------------------------
DROP TABLE IF EXISTS `admin_role`;
CREATE TABLE `admin_role`
(
    `admin_id` bigint UNSIGNED NOT NULL,
    `role_id`  bigint UNSIGNED NOT NULL,
    `enabled`  tinyint         NOT NULL DEFAULT 1 COMMENT '{1:启用,0:停用}',
    PRIMARY KEY (`admin_id`, `role_id`) USING BTREE,
    INDEX `idx_role` (`role_id` ASC) USING BTREE
) ENGINE = InnoDB
  CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_general_ci
  ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of admin_role
-- ----------------------------
INSERT INTO `admin_role`
VALUES (1, 1, 1);
INSERT INTO `admin_role`
VALUES (1, 10, 1);
INSERT INTO `admin_role`
VALUES (1, 11, 1);
INSERT INTO `admin_role`
VALUES (3, 10, 1);
INSERT INTO `admin_role`
VALUES (3, 11, 1);

-- ----------------------------
-- Table structure for auth_role
-- ----------------------------
DROP TABLE IF EXISTS `auth_role`;
CREATE TABLE `auth_role`
(
    `id`          bigint UNSIGNED                                               NOT NULL AUTO_INCREMENT COMMENT '主键',
    `pid`         bigint UNSIGNED                                               NOT NULL DEFAULT 0 COMMENT '父级 id，0 为根（仅展示，无权限继承）',
    `name`        varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '角色名',
    `desc`        varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL     DEFAULT NULL COMMENT '描述',
    `sort`        int                                                           NOT NULL DEFAULT 0 COMMENT '排序',
    `status`      tinyint                                                       NOT NULL DEFAULT 1 COMMENT '{1:启用,0:禁用}',
    `create_time` datetime                                                      NULL     DEFAULT NULL COMMENT '创建时间',
    `update_time` datetime                                                      NULL     DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    INDEX `idx_pid` (`pid` ASC) USING BTREE
) ENGINE = InnoDB
  AUTO_INCREMENT = 12
  CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT = '权限角色'
  ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of auth_role
-- ----------------------------
INSERT INTO `auth_role`
VALUES (1, 0, '超级管理员', '拥有全部菜单（id=1 特例：未勾选也视为全量）', 0, 1, NULL, NULL);
INSERT INTO `auth_role`
VALUES (10, 0, '领导权限', '领导权限-只能查看、不能编辑', 0, 1, '2026-05-13 15:20:53', '2026-05-14 16:47:19');
INSERT INTO `auth_role`
VALUES (11, 1, '系统权限', '拥有所有权限，但需要进行鉴权逻辑', 0, 1, '2026-05-14 16:47:45', '2026-05-15 15:17:35');

-- ----------------------------
-- Table structure for auth_role_rule
-- ----------------------------
DROP TABLE IF EXISTS `auth_role_rule`;
CREATE TABLE `auth_role_rule`
(
    `role_id` bigint UNSIGNED NOT NULL COMMENT '角色 id',
    `rule_id` bigint UNSIGNED NOT NULL COMMENT 'auth_rule.id，且 scope=frontend',
    PRIMARY KEY (`role_id`, `rule_id`) USING BTREE,
    INDEX `idx_rule` (`rule_id` ASC) USING BTREE
) ENGINE = InnoDB
  CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT = '角色-前端规则'
  ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of auth_role_rule
-- ----------------------------
INSERT INTO `auth_role_rule`
VALUES (10, 100);
INSERT INTO `auth_role_rule`
VALUES (11, 100);
INSERT INTO `auth_role_rule`
VALUES (11, 102);
INSERT INTO `auth_role_rule`
VALUES (10, 103);
INSERT INTO `auth_role_rule`
VALUES (11, 103);
INSERT INTO `auth_role_rule`
VALUES (11, 104);
INSERT INTO `auth_role_rule`
VALUES (11, 105);
INSERT INTO `auth_role_rule`
VALUES (11, 106);
INSERT INTO `auth_role_rule`
VALUES (10, 107);
INSERT INTO `auth_role_rule`
VALUES (11, 107);
INSERT INTO `auth_role_rule`
VALUES (10, 108);
INSERT INTO `auth_role_rule`
VALUES (11, 108);
INSERT INTO `auth_role_rule`
VALUES (11, 202);
INSERT INTO `auth_role_rule`
VALUES (11, 203);
INSERT INTO `auth_role_rule`
VALUES (11, 204);
INSERT INTO `auth_role_rule`
VALUES (11, 205);
INSERT INTO `auth_role_rule`
VALUES (11, 206);
INSERT INTO `auth_role_rule`
VALUES (11, 207);
INSERT INTO `auth_role_rule`
VALUES (11, 208);
INSERT INTO `auth_role_rule`
VALUES (11, 209);
INSERT INTO `auth_role_rule`
VALUES (11, 210);
INSERT INTO `auth_role_rule`
VALUES (11, 211);
INSERT INTO `auth_role_rule`
VALUES (11, 310);
INSERT INTO `auth_role_rule`
VALUES (11, 311);
INSERT INTO `auth_role_rule`
VALUES (11, 312);
INSERT INTO `auth_role_rule`
VALUES (11, 313);
INSERT INTO `auth_role_rule`
VALUES (11, 314);
INSERT INTO `auth_role_rule`
VALUES (10, 310);
INSERT INTO `auth_role_rule`
VALUES (10, 311);

-- ----------------------------
-- Table structure for auth_rule
-- ----------------------------
DROP TABLE IF EXISTS `auth_rule`;
CREATE TABLE `auth_rule`
(
    `id`          bigint UNSIGNED                                               NOT NULL AUTO_INCREMENT COMMENT '主键',
    `scope`       varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NOT NULL COMMENT '{backend:后端,frontend:前端}',
    `type`        varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NOT NULL COMMENT 'backend:{menu,api} frontend:{menu,view,btn}',
    `pid`         bigint UNSIGNED                                               NOT NULL DEFAULT 0 COMMENT '父级 id，0 为根',
    `name`        varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '名称',
    `path`        varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL     DEFAULT NULL COMMENT 'backend api: 与 pathinfo 一致如 auth.Rule/tree；frontend: 路由 path 片段',
    `method`      varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NULL     DEFAULT NULL COMMENT 'HTTP 方法，api 用，如 GET POST *',
    `component`   varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL     DEFAULT NULL COMMENT '前端组件路径，view 用',
    `icon`        varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL     DEFAULT NULL COMMENT '图标：{ep:ElementPlus图标组件名,url:图片绝对或相对地址}',
    `sort`        int                                                           NOT NULL DEFAULT 0 COMMENT '排序',
    `status`      tinyint                                                       NOT NULL DEFAULT 1 COMMENT '{1:启用,0:禁用}',
    `create_time` datetime                                                      NULL     DEFAULT NULL COMMENT '创建时间',
    `update_time` datetime                                                      NULL     DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    INDEX `idx_scope_pid` (`scope` ASC, `pid` ASC) USING BTREE,
    INDEX `idx_scope_status` (`scope` ASC, `status` ASC) USING BTREE
) ENGINE = InnoDB
  AUTO_INCREMENT = 320
  CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT = '权限规则（后端/前端双域树）'
  ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of auth_rule
-- ----------------------------
INSERT INTO `auth_rule`
VALUES (1, 'backend', 'menu', 0, '后端接口权限', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (2, 'backend', 'api', 16, '规则树', 'auth.Rule/tree', 'GET', NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (3, 'backend', 'api', 16, '规则详情', 'auth.Rule/read', 'GET', NULL, NULL, 1, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (4, 'backend', 'api', 16, '新增规则', 'auth.Rule/save', 'POST', NULL, NULL, 2, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (5, 'backend', 'api', 16, '更新规则', 'auth.Rule/update', 'PUT', NULL, NULL, 3, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (6, 'backend', 'api', 16, '删除规则', 'auth.Rule/delete', 'DELETE', NULL, NULL, 4, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (7, 'backend', 'api', 16, '绑定后端接口', 'auth.Rule/bindApis', 'POST', NULL, NULL, 5, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (9, 'backend', 'api', 17, '角色树', 'auth.Role/tree', 'GET', NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (10, 'backend', 'api', 17, '角色详情', 'auth.Role/read', 'GET', NULL, NULL, 1, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (11, 'backend', 'api', 17, '新增角色', 'auth.Role/save', 'POST', NULL, NULL, 2, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (12, 'backend', 'api', 17, '更新角色', 'auth.Role/update', 'PUT', NULL, NULL, 3, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (13, 'backend', 'api', 17, '删除角色', 'auth.Role/delete', 'DELETE', NULL, NULL, 4, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (14, 'backend', 'api', 17, '分配前端规则', 'auth.Role/assignRules', 'PUT', NULL, NULL, 5, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (15, 'backend', 'api', 18, '动态菜单', 'Menu/menus', 'GET', NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (16, 'backend', 'menu', 1, '规则管理', NULL, NULL, NULL, NULL, 400, 1, NULL, '2026-05-14 17:02:23');
INSERT INTO `auth_rule`
VALUES (17, 'backend', 'menu', 1, '角色管理', NULL, NULL, NULL, NULL, 300, 1, NULL, '2026-05-14 17:02:16');
INSERT INTO `auth_rule`
VALUES (18, 'backend', 'menu', 1, '菜单与导航', NULL, NULL, NULL, NULL, 100, 1, NULL, '2026-05-14 17:02:05');
INSERT INTO `auth_rule`
VALUES (19, 'backend', 'menu', 1, '管理员', NULL, NULL, NULL, NULL, 200, 1, NULL, '2026-05-14 17:02:10');
INSERT INTO `auth_rule`
VALUES (20, 'backend', 'api', 19, '管理员列表', 'auth.Admin/list', 'GET', NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (21, 'backend', 'api', 19, '管理员详情', 'auth.Admin/read', 'GET', NULL, NULL, 1, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (22, 'backend', 'api', 19, '新增管理员', 'auth.Admin/save', 'POST', NULL, NULL, 2, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (23, 'backend', 'api', 19, '更新管理员', 'auth.Admin/update', 'PUT', NULL, NULL, 3, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (24, 'backend', 'api', 19, '删除管理员', 'auth.Admin/delete', 'DELETE', NULL, NULL, 4, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (25, 'backend', 'menu', 1, '账号中心接口', NULL, NULL, NULL, NULL, 500, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (26, 'backend', 'api', 25, '当前用户资料', 'account.Profile/read', 'GET', NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (27, 'backend', 'api', 25, '更新当前用户资料', 'account.Profile/update', 'PUT', NULL, NULL, 1, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (28, 'backend', 'api', 25, '操作日志列表', 'account.OperationLog/index', 'GET', NULL, NULL, 2, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (29, 'backend', 'api', 25, '通知列表', 'account.Notification/index', 'GET', NULL, NULL, 3, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (30, 'backend', 'api', 25, '通知已读', 'account.Notification/markRead', 'PUT', NULL, NULL, 4, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (31, 'backend', 'api', 25, '上传头像', 'account.Profile/uploadAvatar', 'POST', NULL, NULL, 5, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (33, 'backend', 'api', 25, '切换生效角色', 'account.Profile/switchRoles', 'PUT', NULL, NULL, 6, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (100, 'frontend', 'menu', 0, '仪表盘', 'dashboard', NULL, NULL, 'el:DataAnalysis', 0, 1, NULL,
        '2026-07-08 10:35:42');
INSERT INTO `auth_rule`
VALUES (102, 'frontend', 'menu', 0, '权限管理', 'auth', NULL, NULL, 'el:Avatar', 10, 1, NULL, '2026-05-12 16:09:25');
INSERT INTO `auth_rule`
VALUES (103, 'frontend', 'view', 102, '角色管理', 'role', NULL, 'auth/role/index.vue', 'el:User', 20, 1, NULL,
        '2026-05-14 16:41:58');
INSERT INTO `auth_rule`
VALUES (104, 'frontend', 'btn', 103, '添加', 'add', NULL, NULL, 'el:Plus', 0, 1, NULL, '2026-05-13 14:36:32');
INSERT INTO `auth_rule`
VALUES (105, 'frontend', 'btn', 103, '编辑', 'edit', NULL, NULL, 'el:EditPen', 1, 1, NULL, '2026-05-13 14:36:18');
INSERT INTO `auth_rule`
VALUES (106, 'frontend', 'btn', 103, '删除', 'del', NULL, NULL, 'el:Delete', 2, 1, NULL, '2026-05-13 14:36:24');
INSERT INTO `auth_rule`
VALUES (107, 'frontend', 'view', 102, '规则管理', 'rule', NULL, 'auth/rule/index.vue', 'el:Tickets', 30, 1, NULL,
        '2026-05-14 16:42:05');
INSERT INTO `auth_rule`
VALUES (108, 'frontend', 'view', 102, '管理员', 'admin', NULL, 'auth/admin/index.vue', 'el:UserFilled', 10, 1, NULL,
        '2026-05-14 16:41:52');
INSERT INTO `auth_rule`
VALUES (202, 'frontend', 'btn', 108, '添加', 'add', NULL, NULL, 'el:Plus', 0, 1, '2026-05-14 16:45:07',
        '2026-05-14 16:45:07');
INSERT INTO `auth_rule`
VALUES (203, 'frontend', 'btn', 108, '编辑', 'edit', NULL, NULL, 'el:EditPen', 0, 1, '2026-05-14 16:46:02',
        '2026-05-14 16:46:02');
INSERT INTO `auth_rule`
VALUES (204, 'frontend', 'btn', 108, '删除', 'del', NULL, NULL, 'el:Delete', 0, 1, '2026-05-14 16:46:15',
        '2026-05-14 16:46:15');
INSERT INTO `auth_rule`
VALUES (205, 'frontend', 'btn', 107, '添加', 'add', NULL, NULL, 'el:Plus', 0, 1, '2026-05-14 17:09:06',
        '2026-05-14 17:09:06');
INSERT INTO `auth_rule`
VALUES (206, 'frontend', 'btn', 107, '编辑', 'edit', NULL, NULL, 'el:EditPen', 0, 1, '2026-05-14 17:09:22',
        '2026-05-14 17:09:22');
INSERT INTO `auth_rule`
VALUES (207, 'frontend', 'btn', 107, '删除', 'del', NULL, NULL, 'el:Delete', 0, 1, '2026-05-14 17:09:32',
        '2026-05-14 17:09:32');
INSERT INTO `auth_rule`
VALUES (208, 'frontend', 'menu', 0, '个人中心', 'account', NULL, NULL, 'el:UserFilled', 5, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (209, 'frontend', 'view', 208, '个人信息', 'profile', NULL, 'account/profile', 'el:User', 0, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (210, 'frontend', 'view', 208, '操作日志', 'logs', NULL, 'account/operation-logs', 'el:Document', 1, 1, NULL,
        NULL);
INSERT INTO `auth_rule`
VALUES (211, 'frontend', 'view', 208, '消息通知', 'notifications', NULL, 'account/notifications', 'el:Bell', 2, 1, NULL,
        NULL);
INSERT INTO `auth_rule`
VALUES (212, 'frontend', 'view', 100, '系统使用情况', 'usage', NULL, 'dashboard/usage', 'el:Histogram', 0, 1,
        '2026-07-08 10:52:52', '2026-07-08 10:55:56');
INSERT INTO `auth_rule`
VALUES (213, 'backend', 'menu', 1, '仪表盘', NULL, NULL, NULL, NULL, 0, 1, '2026-07-08 10:53:10',
        '2026-07-08 10:53:10');
INSERT INTO `auth_rule`
VALUES (214, 'backend', 'api', 213, '系统使用情况', 'dashboard.Usage/index', 'GET', NULL, NULL, 0, 1,
        '2026-07-08 10:53:40', '2026-07-08 10:53:40');
INSERT INTO `auth_rule`
VALUES (300, 'backend', 'menu', 1, '示例模块', NULL, NULL, NULL, NULL, 600, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (301, 'backend', 'api', 300, '示例数据列表', 'example.Data/list', 'GET', NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (302, 'backend', 'api', 300, '示例数据详情', 'example.Data/read', 'GET', NULL, NULL, 1, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (303, 'backend', 'api', 300, '新增示例数据', 'example.Data/save', 'POST', NULL, NULL, 2, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (304, 'backend', 'api', 300, '更新示例数据', 'example.Data/update', 'PUT', NULL, NULL, 3, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (305, 'backend', 'api', 300, '删除示例数据', 'example.Data/delete', 'DELETE', NULL, NULL, 4, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (306, 'backend', 'api', 300, '示例数据选项', 'example.Data/options', 'GET', NULL, NULL, 5, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (307, 'backend', 'api', 300, '上传示例封面', 'example.Data/uploadCover', 'POST', NULL, NULL, 6, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (310, 'frontend', 'menu', 0, '示例', 'example', NULL, NULL, 'el:DocumentCopy', 15, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (311, 'frontend', 'view', 310, '示例数据', 'data', NULL, 'example/data/index.vue', 'el:Document', 0, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (312, 'frontend', 'btn', 311, '添加', 'add', NULL, NULL, 'el:Plus', 0, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (313, 'frontend', 'btn', 311, '编辑', 'edit', NULL, NULL, 'el:EditPen', 1, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (314, 'frontend', 'btn', 311, '删除', 'del', NULL, NULL, 'el:Delete', 2, 1, NULL, NULL);

-- ----------------------------
-- Table structure for auth_rule_frontend_api
-- ----------------------------
DROP TABLE IF EXISTS `auth_rule_frontend_api`;
CREATE TABLE `auth_rule_frontend_api`
(
    `frontend_rule_id` bigint UNSIGNED NOT NULL COMMENT 'auth_rule id，scope=frontend 且 type 为 view 或 btn',
    `backend_rule_id`  bigint UNSIGNED NOT NULL COMMENT 'auth_rule id，scope=backend 且 type=api',
    PRIMARY KEY (`frontend_rule_id`, `backend_rule_id`) USING BTREE,
    INDEX `idx_backend` (`backend_rule_id` ASC) USING BTREE
) ENGINE = InnoDB
  CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT = '前端规则-后端接口'
  ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of auth_rule_frontend_api
-- ----------------------------
INSERT INTO `auth_rule_frontend_api`
VALUES (107, 2);
INSERT INTO `auth_rule_frontend_api`
VALUES (107, 3);
INSERT INTO `auth_rule_frontend_api`
VALUES (205, 4);
INSERT INTO `auth_rule_frontend_api`
VALUES (206, 5);
INSERT INTO `auth_rule_frontend_api`
VALUES (207, 6);
INSERT INTO `auth_rule_frontend_api`
VALUES (205, 7);
INSERT INTO `auth_rule_frontend_api`
VALUES (206, 7);
INSERT INTO `auth_rule_frontend_api`
VALUES (207, 7);
INSERT INTO `auth_rule_frontend_api`
VALUES (103, 9);
INSERT INTO `auth_rule_frontend_api`
VALUES (103, 10);
INSERT INTO `auth_rule_frontend_api`
VALUES (104, 11);
INSERT INTO `auth_rule_frontend_api`
VALUES (105, 12);
INSERT INTO `auth_rule_frontend_api`
VALUES (106, 13);
INSERT INTO `auth_rule_frontend_api`
VALUES (104, 14);
INSERT INTO `auth_rule_frontend_api`
VALUES (105, 14);
INSERT INTO `auth_rule_frontend_api`
VALUES (106, 14);
INSERT INTO `auth_rule_frontend_api`
VALUES (108, 20);
INSERT INTO `auth_rule_frontend_api`
VALUES (108, 21);
INSERT INTO `auth_rule_frontend_api`
VALUES (202, 22);
INSERT INTO `auth_rule_frontend_api`
VALUES (203, 23);
INSERT INTO `auth_rule_frontend_api`
VALUES (204, 24);
INSERT INTO `auth_rule_frontend_api`
VALUES (209, 26);
INSERT INTO `auth_rule_frontend_api`
VALUES (209, 27);
INSERT INTO `auth_rule_frontend_api`
VALUES (210, 28);
INSERT INTO `auth_rule_frontend_api`
VALUES (211, 29);
INSERT INTO `auth_rule_frontend_api`
VALUES (211, 30);
INSERT INTO `auth_rule_frontend_api`
VALUES (209, 31);
INSERT INTO `auth_rule_frontend_api`
VALUES (209, 33);
INSERT INTO `auth_rule_frontend_api`
VALUES (212, 214);
INSERT INTO `auth_rule_frontend_api`
VALUES (311, 301);
INSERT INTO `auth_rule_frontend_api`
VALUES (311, 302);
INSERT INTO `auth_rule_frontend_api`
VALUES (311, 306);
INSERT INTO `auth_rule_frontend_api`
VALUES (312, 303);
INSERT INTO `auth_rule_frontend_api`
VALUES (312, 307);
INSERT INTO `auth_rule_frontend_api`
VALUES (313, 304);
INSERT INTO `auth_rule_frontend_api`
VALUES (313, 307);
INSERT INTO `auth_rule_frontend_api`
VALUES (314, 305);

-- ----------------------------
-- Table structure for example_category
-- ----------------------------
DROP TABLE IF EXISTS `example_category`;
CREATE TABLE `example_category`
(
    `id`          bigint UNSIGNED                                               NOT NULL AUTO_INCREMENT COMMENT '主键',
    `name`        varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NOT NULL COMMENT '分类名',
    `sort`        int                                                           NOT NULL DEFAULT 0 COMMENT '排序',
    `status`      tinyint                                                       NOT NULL DEFAULT 1 COMMENT '{1:启用,0:禁用}',
    `create_time` bigint                                                        NULL DEFAULT NULL COMMENT '创建时间（Unix 秒）',
    `update_time` bigint                                                        NULL DEFAULT NULL COMMENT '更新时间（Unix 秒）',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB
  CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT = '示例分类（belongs_to 关联模板）'
  ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of example_category
-- ----------------------------
INSERT INTO `example_category`
VALUES (1, '默认分类', 0, 1, NULL, NULL),
       (2, '重要分类', 1, 1, NULL, NULL);

-- ----------------------------
-- Table structure for example_tag
-- ----------------------------
DROP TABLE IF EXISTS `example_tag`;
CREATE TABLE `example_tag`
(
    `id`          bigint UNSIGNED                                               NOT NULL AUTO_INCREMENT COMMENT '主键',
    `name`        varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NOT NULL COMMENT '标签名',
    `sort`        int                                                           NOT NULL DEFAULT 0 COMMENT '排序',
    `status`      tinyint                                                       NOT NULL DEFAULT 1 COMMENT '{1:启用,0:禁用}',
    `create_time` bigint                                                        NULL DEFAULT NULL COMMENT '创建时间（Unix 秒）',
    `update_time` bigint                                                        NULL DEFAULT NULL COMMENT '更新时间（Unix 秒）',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB
  CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT = '示例标签（belongs_to_many 关联模板）'
  ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of example_tag
-- ----------------------------
INSERT INTO `example_tag`
VALUES (1, '热门', 0, 1, NULL, NULL),
       (2, '推荐', 1, 1, NULL, NULL),
       (3, '草稿', 2, 1, NULL, NULL);

-- ----------------------------
-- Table structure for example_data
-- ----------------------------
DROP TABLE IF EXISTS `example_data_tag`;
DROP TABLE IF EXISTS `example_data`;
CREATE TABLE `example_data`
(
    `id`            bigint UNSIGNED                                               NOT NULL AUTO_INCREMENT COMMENT '主键',
    `title`         varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '标题',
    `code`          varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NOT NULL COMMENT '编码（唯一）',
    `category_id`   bigint UNSIGNED                                               NULL DEFAULT NULL COMMENT '分类 id（belongs_to）',
    `content`       text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci         NULL COMMENT '内容',
    `amount`        decimal(10, 2)                                                NOT NULL DEFAULT 0.00 COMMENT '金额',
    `quantity`      int                                                           NOT NULL DEFAULT 0 COMMENT '数量',
    `is_top`        tinyint                                                       NOT NULL DEFAULT 0 COMMENT '{1:是,0:否}',
    `status`        tinyint                                                       NOT NULL DEFAULT 1 COMMENT '{1:启用,0:禁用}',
    `level`         tinyint                                                       NOT NULL DEFAULT 1 COMMENT '{1:普通,2:重要,3:紧急}',
    `color`         varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NULL DEFAULT NULL COMMENT '颜色',
    `cover`         varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '封面相对路径',
    `publish_time`  bigint                                                        NULL DEFAULT NULL COMMENT '发布时间（Unix 秒）',
    `expire_date`   date                                                          NULL DEFAULT NULL COMMENT '过期日期',
    `extra`         json                                                          NULL COMMENT '扩展项 JSON 数组',
    `remark`        varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '备注',
    `sort`          int                                                           NOT NULL DEFAULT 0 COMMENT '排序',
    `create_time`   bigint                                                        NULL DEFAULT NULL COMMENT '创建时间（Unix 秒）',
    `update_time`   bigint                                                        NULL DEFAULT NULL COMMENT '更新时间（Unix 秒）',
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE INDEX `uk_code` (`code` ASC) USING BTREE,
    INDEX `idx_category_id` (`category_id` ASC) USING BTREE,
    INDEX `idx_status` (`status` ASC) USING BTREE,
    INDEX `idx_level` (`level` ASC) USING BTREE
) ENGINE = InnoDB
  CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT = '示例数据（CRUD 全字段类型模板表）'
  ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for example_data_tag
-- ----------------------------
CREATE TABLE `example_data_tag`
(
    `data_id` bigint UNSIGNED NOT NULL COMMENT 'example_data.id',
    `tag_id`  bigint UNSIGNED NOT NULL COMMENT 'example_tag.id',
    PRIMARY KEY (`data_id`, `tag_id`) USING BTREE,
    INDEX `idx_tag_id` (`tag_id` ASC) USING BTREE
) ENGINE = InnoDB
  CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT = '示例数据-标签（belongs_to_many 中间表）'
  ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of example_data
-- ----------------------------

-- ----------------------------
-- Table structure for dev_crud_history
-- ----------------------------
DROP TABLE IF EXISTS `dev_crud_history`;
CREATE TABLE `dev_crud_history`
(
    `id`          int UNSIGNED                                                  NOT NULL AUTO_INCREMENT,
    `name`        varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '方案名',
    `config`      longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci     NOT NULL COMMENT '方案配置',
    `fields`      longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci     NOT NULL COMMENT '字段配置',
    `create_time` datetime                                                      NOT NULL COMMENT '创建时间',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB
  CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_general_ci
  ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of dev_crud_history
-- ----------------------------

-- ----------------------------
-- Table structure for rsa_key
-- ----------------------------
DROP TABLE IF EXISTS `rsa_key`;
CREATE TABLE `rsa_key`
(
    `id`          bigint UNSIGNED                                              NOT NULL AUTO_INCREMENT COMMENT '主键',
    `serial`      varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '密钥序列号',
    `public_key`  text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci        NOT NULL COMMENT 'RSA公钥PEM',
    `private_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci        NOT NULL COMMENT 'RSA私钥PEM',
    `expire_time` bigint                                                       NOT NULL COMMENT '过期时间',
    `status`      tinyint                                                      NOT NULL DEFAULT 1 COMMENT '{1:有效,0:失效}',
    `create_time` bigint                                                       NULL     DEFAULT NULL COMMENT '创建时间',
    `update_time` bigint                                                       NULL     DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE INDEX `uk_serial` (`serial` ASC) USING BTREE,
    INDEX `idx_expire_time` (`expire_time` ASC) USING BTREE
) ENGINE = InnoDB
  AUTO_INCREMENT = 20
  CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT = '登录RSA密钥'
  ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of rsa_key
-- ----------------------------

SET FOREIGN_KEY_CHECKS = 1;
