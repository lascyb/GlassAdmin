-- 后端 api「规则排序」：Rule/sort，绑到规则管理页「编辑」按钮（id=37）
-- 新装已由 database/install/data.sql id=93 覆盖；已有库执行本脚本或跑 Phinx
-- 仓库已占用到 92（分配前端），本脚本从 93 起

INSERT IGNORE INTO `system_auth_rule`
    (`id`, `scope`, `type`, `pid`, `name`, `path`, `method`, `component`, `icon`, `sort`, `status`, `visibility`, `create_time`, `update_time`)
VALUES
    (93, 'backend', 'api', 16, '规则排序', 'system.auth.Rule/sort', 'PUT', NULL, NULL, 10, 1, 0, NOW(), NOW());

INSERT IGNORE INTO `system_auth_rule_api` (`frontend_rule_id`, `backend_rule_id`) VALUES
    (37, 93);
