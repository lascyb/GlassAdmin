CREATE TABLE IF NOT EXISTS `system_workbench_shortcut` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `admin_id` bigint UNSIGNED NOT NULL COMMENT '管理员 auth_admin.id',
  `rule_id` bigint UNSIGNED NOT NULL COMMENT '前端规则 auth_rule.id',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序（越小越靠前）',
  `create_time` bigint NULL DEFAULT NULL COMMENT '创建时间（Unix 秒）',
  `update_time` bigint NULL DEFAULT NULL COMMENT '更新时间（Unix 秒）',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_admin_rule`(`admin_id` ASC, `rule_id` ASC) USING BTREE,
  INDEX `idx_admin_sort`(`admin_id` ASC, `sort` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '工作台快捷入口（按管理员）' ROW_FORMAT = DYNAMIC;
