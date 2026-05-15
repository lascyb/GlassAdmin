-- 前端 view/btn 与后端 api 规则关联

CREATE TABLE IF NOT EXISTS `auth_rule_frontend_api` (
  `frontend_rule_id` bigint unsigned NOT NULL COMMENT 'auth_rule id，scope=frontend 且 type 为 view 或 btn',
  `backend_rule_id` bigint unsigned NOT NULL COMMENT 'auth_rule id，scope=backend 且 type=api',
  PRIMARY KEY (`frontend_rule_id`,`backend_rule_id`),
  KEY `idx_backend` (`backend_rule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='前端规则-后端接口';

-- 示例：角色页、规则页关联常用接口（可按需调整）
INSERT INTO `auth_rule_frontend_api` (`frontend_rule_id`,`backend_rule_id`) VALUES
(103,9),(103,10),(103,11),(103,12),(103,13),(103,14),
(107,2),(107,3),(107,4),(107,5),(107,6),(107,7),(107,8),
(108,20),(108,21),(108,22),(108,23),(108,24),
(209,26),(209,27),(209,31),
(210,28),
(211,29),(211,30);
