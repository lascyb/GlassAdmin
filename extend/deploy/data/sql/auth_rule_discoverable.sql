-- auth_rule: 可发现 + 描述
ALTER TABLE `auth_rule`
  ADD COLUMN `discoverable` tinyint NOT NULL DEFAULT 0 COMMENT '{1:展示全部时可发现,0:仅已授权可见}' AFTER `status`,
  ADD COLUMN `description` varchar(500) NULL DEFAULT NULL COMMENT '功能描述（无权限页展示）' AFTER `discoverable`;
