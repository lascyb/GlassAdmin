-- 后台管理员表（若在 .env 中配置了 DB_PREFIX，建表时请为表名加上相同前缀）

CREATE TABLE IF NOT EXISTS `admin` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `username` varchar(64) NOT NULL COMMENT '登录名',
  `password` varchar(255) NOT NULL COMMENT 'password_hash bcrypt',
  `nickname` varchar(100) DEFAULT NULL COMMENT '昵称',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '{1:正常,0:禁用}',
  `role_id` bigint unsigned DEFAULT NULL COMMENT '主角色 auth_role.id',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台管理员账号';

-- 以下为首次部署使用的初始化数据；若用户名 admin 记录已存在请勿重复执行（否则会因唯一约束报错）
INSERT INTO `admin` (`username`, `password`, `nickname`, `status`, `role_id`)
VALUES ('admin', '$2y$10$xwPex1q7KkR6aDhR2UtMAuYtYxRH8bN.bSXeyiQFs7IaKpYOfJq0a', '管理员', 1, 1);
