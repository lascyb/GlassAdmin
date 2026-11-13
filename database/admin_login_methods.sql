-- 管理员登录方式：账号可空列 + 系统默认配置
-- 新装已由 database/install/schema.sql + data.sql 覆盖；已有库执行本脚本或 php think version:update

ALTER TABLE `system_auth_admin`
  ADD COLUMN `login_methods` json NULL COMMENT 'NULL=跟随系统默认；否则为允许的登录方式 JSON 数组' AFTER `status`;

INSERT IGNORE INTO `system_config`
    (`id`, `key`, `value`, `group`, `name`, `description`, `type`, `options`, `sort`, `status`, `create_time`, `update_time`)
VALUES
    (13, 'admin_default_login_methods', '["password","feishu"]', '登录配置', '默认登录方式',
     '账号未单独配置时继承；password=密码登录，feishu=飞书登录', 'multi_select', '["password","feishu"]',
     10, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

UPDATE `system_config`
SET `value` = '布局配置;上传配置;全局配置;登录配置;',
    `update_time` = UNIX_TIMESTAMP()
WHERE `key` = 'config_group_sort'
  AND `value` NOT LIKE '%登录配置%';
