-- 公开配置接口 system.Config/public（登录页读取 site_title）
-- 新装已由 database/install/data.sql id=79 覆盖；已有库执行本脚本

INSERT IGNORE INTO `auth_rule`
    (`id`, `scope`, `type`, `pid`, `name`, `path`, `method`, `component`, `icon`, `sort`, `status`, `visibility`, `create_time`, `update_time`)
VALUES
    (79, 'backend', 'api', 57, '公开配置', 'system.Config/public', 'GET', NULL, NULL, 8, 1, 0, NOW(), NOW());
