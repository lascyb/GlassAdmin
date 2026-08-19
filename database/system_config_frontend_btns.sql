-- 全局配置页补前端 add/edit/del，写接口从页面改挂到按钮
-- 新装已由 database/install/data.sql id=80–82 覆盖；已有库执行本脚本
-- 执行前 max(id)=79

INSERT IGNORE INTO `auth_rule`
    (`id`, `scope`, `type`, `pid`, `name`, `path`, `method`, `component`, `icon`, `sort`, `status`, `visibility`, `create_time`, `update_time`)
VALUES
    (80, 'frontend', 'btn', 55, '新增', 'add',  NULL, NULL, 'el:Plus',    0, 1, 0, NOW(), NOW()),
    (81, 'frontend', 'btn', 55, '编辑', 'edit', NULL, NULL, 'el:EditPen', 1, 1, 0, NOW(), NOW()),
    (82, 'frontend', 'btn', 55, '删除', 'del',  NULL, NULL, 'el:Delete',  2, 1, 0, NOW(), NOW());

INSERT IGNORE INTO `auth_role_rule` (`role_id`, `rule_id`)
SELECT rr.`role_id`, v.`btn_id`
FROM `auth_role_rule` rr
JOIN (
    SELECT 55 AS `view_id`, 80 AS `btn_id` UNION ALL
    SELECT 55, 81 UNION ALL
    SELECT 55, 82
) v ON rr.`rule_id` = v.`view_id`;

DELETE FROM `auth_rule_api` WHERE (`frontend_rule_id`, `backend_rule_id`) IN (
    (55, 61),
    (55, 62),
    (55, 63),
    (55, 64)
);

INSERT IGNORE INTO `auth_rule_api` (`frontend_rule_id`, `backend_rule_id`) VALUES
    (80, 61),
    (81, 62),
    (82, 63),
    (81, 64);
