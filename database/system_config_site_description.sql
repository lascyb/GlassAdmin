-- 登录页站点简介 site_description
-- 新装已由 database/install/data.sql id=11 覆盖；已有库执行本脚本

INSERT IGNORE INTO `system_config`
    (`id`, `key`, `value`, `group`, `name`, `type`, `sort`, `status`, `create_time`, `update_time`)
VALUES
    (11, 'site_description', '透明可视的后台框架', '全局配置', '网站简介', 'input', 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
