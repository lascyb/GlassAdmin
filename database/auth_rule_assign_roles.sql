-- 前端规则「分配角色」：Rule/roles、Rule/assignRoles + 规则管理页按钮
-- 新装已由 database/install/data.sql id=87–89 覆盖；已有库执行本脚本
-- 仓库已占用到 86（CRUD 远程接口），本脚本从 87 起

INSERT IGNORE INTO `auth_rule`
    (`id`, `scope`, `type`, `pid`, `name`, `path`, `method`, `component`, `icon`, `sort`, `status`, `visibility`, `create_time`, `update_time`)
VALUES
    (87, 'backend', 'api', 16, '规则已分配角色', 'auth.Rule/roles', 'GET', NULL, NULL, 6, 1, 0, NOW(), NOW()),
    (88, 'backend', 'api', 16, '规则分配角色', 'auth.Rule/assignRoles', 'PUT', NULL, NULL, 7, 1, 0, NOW(), NOW()),
    (89, 'frontend', 'btn', 31, '分配角色', 'assignRoles', NULL, NULL, 'el:User', 3, 1, 0, NOW(), NOW());

INSERT IGNORE INTO `auth_role_rule` (`role_id`, `rule_id`)
SELECT rr.`role_id`, 89
FROM `auth_role_rule` rr
WHERE rr.`rule_id` = 31;

INSERT IGNORE INTO `auth_rule_api` (`frontend_rule_id`, `backend_rule_id`) VALUES
    (89, 87),
    (89, 88);
