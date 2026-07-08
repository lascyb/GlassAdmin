-- 示例数据 CRUD 全量模板：表结构 + 权限规则（已有库执行一次，不兼容旧 example_data 结构）

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `example_data_tag`;
DROP TABLE IF EXISTS `example_data`;
DROP TABLE IF EXISTS `example_tag`;
DROP TABLE IF EXISTS `example_category`;

CREATE TABLE `example_category`
(
    `id`          bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `name`        varchar(50)     NOT NULL COMMENT '分类名',
    `sort`        int             NOT NULL DEFAULT 0 COMMENT '排序',
    `status`      tinyint         NOT NULL DEFAULT 1 COMMENT '{1:启用,0:禁用}',
    `create_time` bigint          NULL DEFAULT NULL COMMENT '创建时间（Unix 秒）',
    `update_time` bigint          NULL DEFAULT NULL COMMENT '更新时间（Unix 秒）',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '示例分类';

CREATE TABLE `example_tag`
(
    `id`          bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `name`        varchar(50)     NOT NULL COMMENT '标签名',
    `sort`        int             NOT NULL DEFAULT 0 COMMENT '排序',
    `status`      tinyint         NOT NULL DEFAULT 1 COMMENT '{1:启用,0:禁用}',
    `create_time` bigint          NULL DEFAULT NULL COMMENT '创建时间（Unix 秒）',
    `update_time` bigint          NULL DEFAULT NULL COMMENT '更新时间（Unix 秒）',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '示例标签';

CREATE TABLE `example_data`
(
    `id`            bigint UNSIGNED  NOT NULL AUTO_INCREMENT COMMENT '主键',
    `title`         varchar(100)     NOT NULL COMMENT '标题',
    `code`          varchar(50)      NOT NULL COMMENT '编码（唯一）',
    `category_id`   bigint UNSIGNED  NULL DEFAULT NULL COMMENT '分类 id',
    `content`       text             NULL COMMENT '内容',
    `amount`        decimal(10, 2)   NOT NULL DEFAULT 0.00 COMMENT '金额',
    `quantity`      int              NOT NULL DEFAULT 0 COMMENT '数量',
    `is_top`        tinyint          NOT NULL DEFAULT 0 COMMENT '{1:是,0:否}',
    `status`        tinyint          NOT NULL DEFAULT 1 COMMENT '{1:启用,0:禁用}',
    `level`         tinyint          NOT NULL DEFAULT 1 COMMENT '{1:普通,2:重要,3:紧急}',
    `color`         varchar(20)      NULL DEFAULT NULL COMMENT '颜色',
    `cover`         varchar(255)     NULL DEFAULT NULL COMMENT '封面相对路径',
    `publish_time`  bigint           NULL DEFAULT NULL COMMENT '发布时间（Unix 秒）',
    `expire_date`   date             NULL DEFAULT NULL COMMENT '过期日期',
    `extra`         json             NULL COMMENT '扩展项 JSON 数组',
    `remark`        varchar(500)     NULL DEFAULT NULL COMMENT '备注',
    `sort`          int              NOT NULL DEFAULT 0 COMMENT '排序',
    `create_time`   bigint           NULL DEFAULT NULL COMMENT '创建时间（Unix 秒）',
    `update_time`   bigint           NULL DEFAULT NULL COMMENT '更新时间（Unix 秒）',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`),
    KEY `idx_category_id` (`category_id`),
    KEY `idx_status` (`status`),
    KEY `idx_level` (`level`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '示例数据';

CREATE TABLE `example_data_tag`
(
    `data_id` bigint UNSIGNED NOT NULL COMMENT 'example_data.id',
    `tag_id`  bigint UNSIGNED NOT NULL COMMENT 'example_tag.id',
    PRIMARY KEY (`data_id`, `tag_id`),
    KEY `idx_tag_id` (`tag_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '示例数据-标签';

INSERT INTO `example_category` (`id`, `name`, `sort`, `status`) VALUES
(1, '默认分类', 0, 1),
(2, '重要分类', 1, 1);

INSERT INTO `example_tag` (`id`, `name`, `sort`, `status`) VALUES
(1, '热门', 0, 1),
(2, '推荐', 1, 1),
(3, '草稿', 2, 1);

INSERT IGNORE INTO `auth_rule` (`id`, `scope`, `type`, `pid`, `name`, `path`, `method`, `component`, `icon`, `sort`, `status`)
VALUES (300, 'backend', 'menu', 1, '示例模块', NULL, NULL, NULL, NULL, 600, 1),
       (301, 'backend', 'api', 300, '示例数据列表', 'example.Data/list', 'GET', NULL, NULL, 0, 1),
       (302, 'backend', 'api', 300, '示例数据详情', 'example.Data/read', 'GET', NULL, NULL, 1, 1),
       (303, 'backend', 'api', 300, '新增示例数据', 'example.Data/save', 'POST', NULL, NULL, 2, 1),
       (304, 'backend', 'api', 300, '更新示例数据', 'example.Data/update', 'PUT', NULL, NULL, 3, 1),
       (305, 'backend', 'api', 300, '删除示例数据', 'example.Data/delete', 'DELETE', NULL, NULL, 4, 1),
       (306, 'backend', 'api', 300, '示例数据选项', 'example.Data/options', 'GET', NULL, NULL, 5, 1),
       (307, 'backend', 'api', 300, '上传示例封面', 'example.Data/uploadCover', 'POST', NULL, NULL, 6, 1),
       (310, 'frontend', 'menu', 0, '示例', 'example', NULL, NULL, 'el:DocumentCopy', 15, 1),
       (311, 'frontend', 'view', 310, '示例数据', 'data', NULL, 'example/data/index.vue', 'el:Document', 0, 1),
       (312, 'frontend', 'btn', 311, '添加', 'add', NULL, NULL, 'el:Plus', 0, 1),
       (313, 'frontend', 'btn', 311, '编辑', 'edit', NULL, NULL, 'el:EditPen', 1, 1),
       (314, 'frontend', 'btn', 311, '删除', 'del', NULL, NULL, 'el:Delete', 2, 1);

INSERT IGNORE INTO `auth_rule_frontend_api` (`frontend_rule_id`, `backend_rule_id`)
VALUES (311, 301), (311, 302), (311, 306),
       (312, 303), (312, 307),
       (313, 304), (313, 307),
       (314, 305);

INSERT IGNORE INTO `auth_role_rule` (`role_id`, `rule_id`)
VALUES (11, 310), (11, 311), (11, 312), (11, 313), (11, 314),
       (10, 310), (10, 311);
