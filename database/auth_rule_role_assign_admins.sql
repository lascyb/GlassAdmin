-- 角色批量绑定管理员：Role/assignAdmins + 角色管理页「绑定用户」按钮
-- 新装已由 database/install/data.sql id=94–95 覆盖；已有库执行本脚本或跑 Phinx
-- 仓库已占用到 93（规则排序），本脚本从 94 起

INSERT IGNORE INTO `system_auth_rule`
    (`id`, `scope`, `type`, `pid`, `name`, `path`, `method`, `component`, `icon`, `sort`, `status`, `visibility`, `create_time`, `update_time`)
VALUES
    (94, 'backend', 'api', 17, '角色绑定管理员', 'system.auth.Role/assignAdmins', 'PUT', NULL, NULL, 6, 1, 0, NOW(), NOW()),
    (95, 'frontend', 'btn', 27, '绑定用户', 'assignAdmins', NULL, NULL, 'el:UserFilled', 3, 1, 0, NOW(), NOW());

-- 已拥有「角色管理」页（27）的角色同时获得新按钮
INSERT IGNORE INTO `system_auth_role_rule` (`role_id`, `rule_id`)
SELECT rr.`role_id`, 95
FROM `system_auth_role_rule` rr
WHERE rr.`rule_id` = 27;

INSERT IGNORE INTO `system_auth_rule_api` (`frontend_rule_id`, `backend_rule_id`) VALUES
    (95, 94);
