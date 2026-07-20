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

 Date: 20/07/2026 17:56:55
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
  AUTO_INCREMENT = 1
  CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT = '后台管理员账号'
  ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of admin
-- ----------------------------
INSERT INTO `admin`
VALUES (1, 'admin', '$2y$12$D.GdwQ9iSwqTXfKVviejZ.aYHrqI/IdO1OrI5Of.GAF.MnzCnSPOu', '超级管理员',
        'avatar/a7344206ea560ae9c0ac8e2884d098b4.png', 1, 1784538185, NULL, 1784541334);

-- ----------------------------
-- Table structure for admin_oauth_feishu
-- ----------------------------
DROP TABLE IF EXISTS `admin_oauth_feishu`;
CREATE TABLE `admin_oauth_feishu`
(
    `id`                       bigint UNSIGNED                                                NOT NULL AUTO_INCREMENT COMMENT '主键',
    `admin_id`                 bigint UNSIGNED                                                NOT NULL COMMENT '管理员 id',
    `open_id`                  varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NOT NULL COMMENT '飞书 open_id',
    `union_id`                 varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NOT NULL COMMENT '飞书 union_id',
    `name`                     varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NULL DEFAULT NULL COMMENT '飞书昵称',
    `avatar`                   varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NULL DEFAULT NULL COMMENT '飞书头像 URL',
    `access_token`             varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'access_token',
    `expires_in`               bigint                                                         NULL DEFAULT NULL COMMENT 'access_token 过期时间（Unix 秒）',
    `refresh_token`            varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'refresh_token',
    `refresh_token_expires_in` bigint                                                         NULL DEFAULT NULL COMMENT 'refresh_token 过期时间（Unix 秒）',
    `scope`                    varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NULL DEFAULT NULL COMMENT '授权 scope',
    `create_time`              bigint                                                         NULL DEFAULT NULL COMMENT '创建时间',
    `update_time`              bigint                                                         NULL DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE INDEX `uk_union_id` (`union_id` ASC) USING BTREE,
    INDEX `idx_admin_id` (`admin_id` ASC) USING BTREE
) ENGINE = InnoDB
  AUTO_INCREMENT = 1
  CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT = '管理员飞书 OAuth 绑定'
  ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of admin_oauth_feishu
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
  AUTO_INCREMENT = 1
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
VALUES (10, 711);
INSERT INTO `auth_role_rule`
VALUES (11, 711);

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
    `path`        varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL     DEFAULT NULL COMMENT '后端 api 路径或前端 path 片段',
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
  AUTO_INCREMENT = 713
  CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT = '权限规则（后端/前端双域树）'
  ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of auth_rule
-- ----------------------------
INSERT INTO `auth_rule`
VALUES (1, 'backend', 'menu', 0, '权限管理', NULL, NULL, NULL, NULL, 0, 1, NULL, '2026-07-20 17:09:25');
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
VALUES (16, 'backend', 'menu', 1, '规则管理', NULL, NULL, NULL, NULL, 400, 1, NULL, '2026-07-20 17:09:48');
INSERT INTO `auth_rule`
VALUES (17, 'backend', 'menu', 1, '角色管理', NULL, NULL, NULL, NULL, 300, 1, NULL, '2026-07-20 17:09:41');
INSERT INTO `auth_rule`
VALUES (19, 'backend', 'menu', 1, '管理员', NULL, NULL, NULL, NULL, 200, 1, NULL, '2026-07-20 17:09:33');
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
VALUES (100, 'frontend', 'menu', 0, '仪表盘', 'dashboard', NULL, NULL, 'el:DataAnalysis', 0, 1, NULL,
        '2026-07-20 17:26:46');
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
VALUES (212, 'frontend', 'view', 100, '系统使用情况', 'usage', NULL, 'dashboard/usage', 'el:Histogram', 0, 1,
        '2026-07-08 10:52:52', '2026-07-08 10:55:56');
INSERT INTO `auth_rule`
VALUES (213, 'backend', 'menu', 0, '仪表盘', NULL, NULL, NULL, NULL, 0, 1, '2026-07-08 10:53:10',
        '2026-07-20 17:08:24');
INSERT INTO `auth_rule`
VALUES (214, 'backend', 'api', 213, '系统使用情况', 'dashboard.Usage/index', 'GET', NULL, NULL, 0, 1,
        '2026-07-08 10:53:40', '2026-07-08 10:53:40');
INSERT INTO `auth_rule`
VALUES (337, 'backend', 'menu', 0, '开发工具接口', NULL, NULL, NULL, NULL, 700, 1, '2026-07-09 17:25:26',
        '2026-07-20 17:08:52');
INSERT INTO `auth_rule`
VALUES (338, 'backend', 'api', 337, 'CRUD样例列表', 'dev.curd.Index/samples', 'GET', NULL, NULL, 0, 1,
        '2026-07-09 17:25:26', '2026-07-09 17:25:26');
INSERT INTO `auth_rule`
VALUES (339, 'backend', 'api', 337, 'CRUD样例详情', 'dev.curd.Index/sample', 'GET', NULL, NULL, 1, 1,
        '2026-07-09 17:25:26', '2026-07-09 17:25:26');
INSERT INTO `auth_rule`
VALUES (340, 'backend', 'api', 337, 'CRUD执行生成', 'dev.curd.Index/generate', 'POST', NULL, NULL, 2, 1,
        '2026-07-09 17:25:26', '2026-07-09 17:25:26');
INSERT INTO `auth_rule`
VALUES (341, 'backend', 'api', 337, 'CRUD历史列表', 'dev.curd.History/index', 'GET', NULL, NULL, 3, 1,
        '2026-07-09 17:25:26', '2026-07-09 17:25:26');
INSERT INTO `auth_rule`
VALUES (342, 'backend', 'api', 337, 'CRUD历史详情', 'dev.curd.History/read', 'GET', NULL, NULL, 4, 1,
        '2026-07-09 17:25:26', '2026-07-09 17:25:26');
INSERT INTO `auth_rule`
VALUES (343, 'frontend', 'menu', 0, '开发', 'dev', NULL, NULL, 'el:SetUp', 90, 1, '2026-07-09 17:25:26',
        '2026-07-09 17:25:26');
INSERT INTO `auth_rule`
VALUES (344, 'frontend', 'view', 343, 'CRUD生成', 'curd', NULL, 'dev/curd/index.vue', 'el:Cpu', 0, 1,
        '2026-07-09 17:25:26', '2026-07-09 17:25:26');
INSERT INTO `auth_rule`
VALUES (345, 'backend', 'api', 337, 'CRUD数据表列表', 'dev.curd.Index/tables', 'GET', NULL, NULL, 5, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (346, 'backend', 'api', 337, 'CRUD表结构反推', 'dev.curd.Index/tableSchema', 'GET', NULL, NULL, 6, 1, NULL,
        NULL);
INSERT INTO `auth_rule`
VALUES (369, 'frontend', 'view', 100, '服务器性能', 'server', NULL, 'dashboard/server', 'el:Monitor', 10, 1,
        '2026-07-17 17:50:00', '2026-07-17 17:50:00');
INSERT INTO `auth_rule`
VALUES (370, 'backend', 'api', 213, '服务器性能', 'dashboard.Server/index', 'GET', NULL, NULL, 10, 1,
        '2026-07-17 17:50:00', '2026-07-17 17:50:00');
INSERT INTO `auth_rule`
VALUES (698, 'frontend', 'menu', 0, '系统管理', 'system', NULL, NULL, 'el:SetUp', 0, 1, '2026-07-20 13:51:07',
        '2026-07-20 13:51:07');
INSERT INTO `auth_rule`
VALUES (699, 'frontend', 'view', 698, '全局配置', 'config', NULL, 'system/config/index.vue', 'el:Setting', 0, 1,
        '2026-07-20 13:52:00', '2026-07-20 13:52:00');
INSERT INTO `auth_rule`
VALUES (700, 'backend', 'menu', 0, '系统管理', NULL, NULL, NULL, NULL, 0, 1, '2026-07-20 13:52:26',
        '2026-07-20 17:08:29');
INSERT INTO `auth_rule`
VALUES (701, 'backend', 'menu', 700, '系统配置', NULL, NULL, NULL, NULL, 0, 1, '2026-07-20 13:53:08',
        '2026-07-20 13:53:08');
INSERT INTO `auth_rule`
VALUES (702, 'backend', 'api', 701, '列表', 'system.Config/list', 'GET', NULL, NULL, 0, 1, '2026-07-20 13:53:36',
        '2026-07-20 13:54:03');
INSERT INTO `auth_rule`
VALUES (703, 'backend', 'api', 701, '配置详情', 'system.Config/list', 'GET', NULL, NULL, 0, 1, '2026-07-20 13:53:49',
        '2026-07-20 13:53:49');
INSERT INTO `auth_rule`
VALUES (704, 'backend', 'api', 701, '配置分组', 'system.Config/groups', 'GET', NULL, NULL, 0, 1, '2026-07-20 13:54:30',
        '2026-07-20 13:54:30');
INSERT INTO `auth_rule`
VALUES (705, 'backend', 'api', 701, '新建配置', 'system.Config/save', 'POST', NULL, NULL, 0, 1, '2026-07-20 13:54:48',
        '2026-07-20 13:54:48');
INSERT INTO `auth_rule`
VALUES (706, 'backend', 'api', 701, '更新配置', 'system.Config/update', 'PUT', NULL, NULL, 0, 1, '2026-07-20 13:55:06',
        '2026-07-20 13:55:06');
INSERT INTO `auth_rule`
VALUES (707, 'backend', 'api', 701, '删除配置', 'system.Config/delete', 'DELETE', NULL, NULL, 0, 1,
        '2026-07-20 13:55:26', '2026-07-20 13:55:26');
INSERT INTO `auth_rule`
VALUES (708, 'backend', 'api', 701, '批量保存配置(按 group)', 'system.Config/delete', 'PUT', NULL, NULL, 0, 1,
        '2026-07-20 13:55:58', '2026-07-20 13:56:31');
INSERT INTO `auth_rule`
VALUES (709, 'backend', 'menu', 0, '账号', NULL, NULL, NULL, NULL, 100, 1, NULL, '2026-07-20 17:08:33');
INSERT INTO `auth_rule`
VALUES (710, 'backend', 'api', 709, '修改密码', 'account.Profile/changePassword', 'PUT', NULL, NULL, 0, 1, NULL, NULL);
INSERT INTO `auth_rule`
VALUES (711, 'frontend', 'btn', 712, '修改密码', 'changePassword', NULL, NULL, 'el:Key', 0, 1, NULL,
        '2026-07-20 17:27:45');
INSERT INTO `auth_rule`
VALUES (712, 'frontend', 'menu', 0, '个人中心', 'account', 'route', NULL, NULL, 0, 1, '2026-07-20 17:27:38',
        '2026-07-20 17:28:26');

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
VALUES (212, 214);
INSERT INTO `auth_rule_frontend_api`
VALUES (344, 338);
INSERT INTO `auth_rule_frontend_api`
VALUES (344, 339);
INSERT INTO `auth_rule_frontend_api`
VALUES (344, 340);
INSERT INTO `auth_rule_frontend_api`
VALUES (344, 341);
INSERT INTO `auth_rule_frontend_api`
VALUES (344, 342);
INSERT INTO `auth_rule_frontend_api`
VALUES (344, 345);
INSERT INTO `auth_rule_frontend_api`
VALUES (344, 346);
INSERT INTO `auth_rule_frontend_api`
VALUES (369, 370);
INSERT INTO `auth_rule_frontend_api`
VALUES (699, 702);
INSERT INTO `auth_rule_frontend_api`
VALUES (699, 703);
INSERT INTO `auth_rule_frontend_api`
VALUES (699, 704);
INSERT INTO `auth_rule_frontend_api`
VALUES (699, 705);
INSERT INTO `auth_rule_frontend_api`
VALUES (699, 706);
INSERT INTO `auth_rule_frontend_api`
VALUES (699, 707);
INSERT INTO `auth_rule_frontend_api`
VALUES (699, 708);
INSERT INTO `auth_rule_frontend_api`
VALUES (711, 710);

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
  AUTO_INCREMENT = 1
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
  AUTO_INCREMENT = 1
  CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT = '登录RSA密钥'
  ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of rsa_key
-- ----------------------------

-- ----------------------------
-- Table structure for system_config
-- ----------------------------
DROP TABLE IF EXISTS `system_config`;
CREATE TABLE `system_config`
(
    `id`          bigint UNSIGNED                                                NOT NULL AUTO_INCREMENT COMMENT '主键',
    `key`         varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NOT NULL COMMENT '配置键名（唯一）',
    `value`       varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置值',
    `group`       varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci   NOT NULL DEFAULT 'default' COMMENT '配置分组',
    `name`        varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NOT NULL COMMENT '配置名称',
    `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NULL     DEFAULT NULL COMMENT '配置说明',
    `type`        varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci   NOT NULL DEFAULT 'input' COMMENT '{input:单行,textarea:多行,select:单选,multi_select:多选,switch:开关,number:数值,color:颜色}',
    `options`     varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL     DEFAULT NULL COMMENT '选项 JSON 数组',
    `sort`        int                                                            NOT NULL DEFAULT 0 COMMENT '排序',
    `status`      tinyint                                                        NOT NULL DEFAULT 1 COMMENT '{1:启用,0:禁用,2:系统内置}',
    `create_time` bigint                                                         NULL     DEFAULT NULL COMMENT '创建时间（Unix 秒）',
    `update_time` bigint                                                         NULL     DEFAULT NULL COMMENT '更新时间（Unix 秒）',
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE INDEX `uk_key` (`key` ASC) USING BTREE,
    INDEX `idx_group_sort` (`group` ASC, `sort` ASC) USING BTREE
) ENGINE = InnoDB
  AUTO_INCREMENT = 1
  CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT = '系统配置'
  ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of system_config
-- ----------------------------
INSERT INTO `system_config`
VALUES (1, 'site_title', 'GlassAdmin', '全局配置', '网站名', NULL, 'input', NULL, 0, 2, 1784527288, 1784529606);

SET FOREIGN_KEY_CHECKS = 1;
