-- 管理员头像：表字段 + 上传接口 RBAC（若已执行可跳过或按需调整 id）
ALTER TABLE `admin` ADD COLUMN `avatar` varchar(255) DEFAULT NULL COMMENT '头像相对路径（public/storage 下，如 avatar/xxx.jpg）' AFTER `nickname`;

INSERT INTO `auth_rule` (`id`,`scope`,`type`,`pid`,`name`,`path`,`method`,`component`,`icon`,`sort`,`status`) VALUES
(31,'backend','api',25,'上传头像','account/profile/avatar','POST',NULL,NULL,5,1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`path`=VALUES(`path`),`method`=VALUES(`method`),`pid`=VALUES(`pid`),`sort`=VALUES(`sort`),`status`=VALUES(`status`);

INSERT IGNORE INTO `auth_rule_frontend_api` (`frontend_rule_id`,`backend_rule_id`) VALUES
(209,31);
