-- 布局默认主题色 layout_theme_color
-- 新装已由 database/install/data.sql id=12 覆盖；已有库执行本脚本

INSERT IGNORE INTO `system_config`
    (`id`, `key`, `value`, `group`, `name`, `description`, `type`, `sort`, `status`, `create_time`, `update_time`)
VALUES
    (12, 'layout_theme_color', '#409eff', '布局配置', '主题色', '个人偏好的系统默认主题色（#rrggbb）', 'color', 40, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
