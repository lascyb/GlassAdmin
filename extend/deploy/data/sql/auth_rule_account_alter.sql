-- 已有库增量：账号中心表、auth_rule 账号相关前后端规则、前端接口绑定、角色授权（若 id 冲突请先调整）
-- 执行顺序建议：admin_account_tables.sql → 本文件
-- 说明：前端个人中心使用 id 208–211，避开现有库中 202–207 等按钮规则；后端账号 API 使用 25–31（接在管理员 API 24 之后）。
-- 若曾执行过 id 120–123 的旧版个人中心脚本，请先删除对应 auth_rule、auth_rule_frontend_api、auth_role_rule 后再执行本文件。

INSERT INTO `auth_rule` (`id`,`scope`,`type`,`pid`,`name`,`path`,`method`,`component`,`icon`,`sort`,`status`) VALUES
(25,'backend','menu',1,'账号中心接口',NULL,NULL,NULL,NULL,500,1),
(26,'backend','api',25,'当前用户资料','account/profile','GET',NULL,NULL,0,1),
(27,'backend','api',25,'更新当前用户资料','account/profile','PUT',NULL,NULL,1,1),
(28,'backend','api',25,'操作日志列表','account/operation-logs','GET',NULL,NULL,2,1),
(29,'backend','api',25,'通知列表','account/notifications','GET',NULL,NULL,3,1),
(30,'backend','api',25,'通知已读','account/notifications/read','PUT',NULL,NULL,4,1),
(31,'backend','api',25,'上传头像','account/profile/avatar','POST',NULL,NULL,5,1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`path`=VALUES(`path`),`method`=VALUES(`method`),`pid`=VALUES(`pid`),`sort`=VALUES(`sort`),`status`=VALUES(`status`);

INSERT INTO `auth_rule` (`id`,`scope`,`type`,`pid`,`name`,`path`,`method`,`component`,`icon`,`sort`,`status`) VALUES
(208,'frontend','menu',0,'个人中心','account',NULL,NULL,'el:UserFilled',5,1),
(209,'frontend','view',208,'个人信息','profile',NULL,'account/profile','el:User',0,1),
(210,'frontend','view',208,'操作日志','logs',NULL,'account/operation-logs','el:Document',1,1),
(211,'frontend','view',208,'消息通知','notifications',NULL,'account/notifications','el:Bell',2,1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`path`=VALUES(`path`),`component`=VALUES(`component`),`icon`=VALUES(`icon`),`pid`=VALUES(`pid`),`sort`=VALUES(`sort`),`status`=VALUES(`status`);

INSERT IGNORE INTO `auth_rule_frontend_api` (`frontend_rule_id`,`backend_rule_id`) VALUES
(209,26),(209,27),(209,31),
(210,28),
(211,29),(211,30);

-- 与现网一致：为「系统权限」角色 id=11 授权个人中心目录及三子页（按需为其他 role_id 增删）
INSERT IGNORE INTO `auth_role_rule` (`role_id`,`rule_id`) VALUES
(11,208),(11,209),(11,210),(11,211);
