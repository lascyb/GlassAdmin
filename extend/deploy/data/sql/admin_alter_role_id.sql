-- 管理员绑定主角色（单角色）；执行前请确认表前缀与库名

ALTER TABLE `admin` ADD COLUMN `role_id` bigint unsigned DEFAULT NULL COMMENT '主角色 auth_role.id' AFTER `status`;
