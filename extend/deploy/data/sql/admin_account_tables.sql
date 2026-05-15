-- 个人中心：操作日志、站内通知（若配置了 DB_PREFIX，表名请加前缀）
-- category：{system:系统,friend:好友}

CREATE TABLE IF NOT EXISTS `admin_operation_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `admin_id` bigint unsigned NOT NULL COMMENT '管理员 id',
  `action` varchar(64) NOT NULL COMMENT '动作标识，如 profile.update',
  `summary` varchar(255) NOT NULL DEFAULT '' COMMENT '简述',
  `ip` varchar(45) DEFAULT NULL COMMENT '客户端 IP',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_admin_time` (`admin_id`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员操作日志';

CREATE TABLE IF NOT EXISTS `admin_notification` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `admin_id` bigint unsigned NOT NULL COMMENT '接收人管理员 id',
  `category` varchar(16) NOT NULL DEFAULT 'system' COMMENT '{system:系统,friend:好友}',
  `title` varchar(200) NOT NULL COMMENT '标题',
  `content` text COMMENT '正文',
  `read_at` datetime DEFAULT NULL COMMENT '已读时间，空为未读',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_admin_read_time` (`admin_id`,`read_at`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员站内通知';

INSERT INTO `admin_notification` (`admin_id`,`category`,`title`,`content`,`read_at`,`create_time`) VALUES
(1,'system','欢迎使用 GlassAdmin','您可在「个人中心」维护资料并查看消息。',NULL,NOW()),
(1,'system','安全提示','请定期更换登录密码。',NULL,NOW()),
(3,'system','欢迎使用 GlassAdmin','您可在「个人中心」维护资料并查看消息。',NULL,NOW());
