-- 布局配置：系统默认初始值（个人偏好未自定义时使用）
INSERT INTO `system_config` (`key`, `value`, `group`, `name`, `description`, `type`, `options`, `sort`, `status`, `create_time`, `update_time`)
SELECT 'layout_show_all_menus', '0', '布局配置', '展示全部可发现菜单', '个人偏好的系统默认值；开启后侧栏可显示可发现但未授权的菜单', 'switch', NULL, 10, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `system_config` WHERE `key` = 'layout_show_all_menus');

INSERT INTO `system_config` (`key`, `value`, `group`, `name`, `description`, `type`, `options`, `sort`, `status`, `create_time`, `update_time`)
SELECT 'layout_container', 'Default', '布局配置', '菜单样式', '个人偏好的系统默认值：Default=左侧单层，Double=左侧双层，Module=顶侧双栏', 'select', '["Default","Double","Module"]', 20, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `system_config` WHERE `key` = 'layout_container');

UPDATE `system_config`
SET `value` = CASE
  WHEN `value` LIKE '%布局配置%' THEN `value`
  ELSE CONCAT('布局配置;', IFNULL(`value`, ''))
END
WHERE `key` = 'config_group_sort';
