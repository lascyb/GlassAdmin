-- 后端 api「分配前端」：Rule/frontends、Rule/assignFrontends + 规则管理页按钮
-- 新装已由 database/install/data.sql id=90–92 覆盖；已有库执行本脚本
-- 仓库已占用到 89（分配角色），本脚本从 90 起

INSERT IGNORE INTO `auth_rule`
    (`id`, `scope`, `type`, `pid`, `name`, `path`, `method`, `component`, `icon`, `sort`, `status`, `visibility`, `create_time`, `update_time`)
VALUES
    (90, 'backend', 'api', 16, '规则已绑定前端', 'auth.Rule/frontends', 'GET', NULL, NULL, 8, 1, 0, NOW(), NOW()),
    (91, 'backend', 'api', 16, '规则分配前端', 'auth.Rule/assignFrontends', 'PUT', NULL, NULL, 9, 1, 0, NOW(), NOW()),
    (92, 'frontend', 'btn', 31, '分配前端', 'assignFrontends', NULL, NULL, 'el:Connection', 4, 1, 0, NOW(), NOW());

INSERT IGNORE INTO `auth_role_rule` (`role_id`, `rule_id`)
SELECT rr.`role_id`, 92
FROM `auth_role_rule` rr
WHERE rr.`rule_id` = 31;

INSERT IGNORE INTO `auth_rule_api` (`frontend_rule_id`, `backend_rule_id`) VALUES
    (92, 90),
    (92, 91);
