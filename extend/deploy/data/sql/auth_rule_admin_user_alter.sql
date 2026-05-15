-- 已有库增量：管理员管理菜单、后端 API 规则、前端 view 与接口绑定（若 id 冲突请先调整再执行）
-- 与 auth_rule.sql / auth_rule_frontend_api.sql 中管理员块一致

INSERT INTO `auth_rule` (`id`,`scope`,`type`,`pid`,`name`,`path`,`method`,`component`,`sort`,`status`) VALUES
(19,'backend','menu',1,'管理员',NULL,NULL,NULL,4,1),
(20,'backend','api',19,'管理员列表','auth/admin/list','GET',NULL,0,1),
(21,'backend','api',19,'管理员详情','auth/admin/read','GET',NULL,1,1),
(22,'backend','api',19,'新增管理员','auth/admin/save','POST',NULL,2,1),
(23,'backend','api',19,'更新管理员','auth/admin/update','PUT',NULL,3,1),
(24,'backend','api',19,'删除管理员','auth/admin/delete','DELETE',NULL,4,1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`path`=VALUES(`path`),`method`=VALUES(`method`),`pid`=VALUES(`pid`),`sort`=VALUES(`sort`),`status`=VALUES(`status`);

INSERT INTO `auth_rule` (`id`,`scope`,`type`,`pid`,`name`,`path`,`method`,`component`,`sort`,`status`) VALUES
(108,'frontend','view',102,'管理员','admin',NULL,'auth/admin/index.vue',2,1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`path`=VALUES(`path`),`component`=VALUES(`component`),`pid`=VALUES(`pid`),`sort`=VALUES(`sort`),`status`=VALUES(`status`);

INSERT IGNORE INTO `auth_rule_frontend_api` (`frontend_rule_id`,`backend_rule_id`) VALUES
(108,20),(108,21),(108,22),(108,23),(108,24);
