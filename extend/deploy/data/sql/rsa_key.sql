-- 登录 RSA 密钥表（若在 .env 中配置了 DB_PREFIX，建表时请为表名加上相同前缀）

CREATE TABLE IF NOT EXISTS `rsa_key` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `serial` varchar(64) NOT NULL COMMENT '密钥序列号',
  `public_key` text NOT NULL COMMENT 'RSA公钥PEM',
  `private_key` text NOT NULL COMMENT 'RSA私钥PEM',
  `expire_time` bigint unsigned NOT NULL COMMENT '过期时间戳',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '{1:有效,0:失效}',
  `create_time` bigint unsigned DEFAULT NULL COMMENT '创建时间戳',
  `update_time` bigint unsigned DEFAULT NULL COMMENT '更新时间戳',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_serial` (`serial`),
  KEY `idx_expire_time` (`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='登录RSA密钥';
