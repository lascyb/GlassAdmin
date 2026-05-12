-- 角色与前端规则多对多（rule_id 仅指向 auth_rule.scope=frontend）

CREATE TABLE IF NOT EXISTS `auth_role_rule` (
  `role_id` bigint unsigned NOT NULL COMMENT '角色 id',
  `rule_id` bigint unsigned NOT NULL COMMENT 'auth_rule.id，且 scope=frontend',
  PRIMARY KEY (`role_id`,`rule_id`),
  KEY `idx_rule` (`rule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色-前端规则';
