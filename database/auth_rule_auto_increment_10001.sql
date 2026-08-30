-- auth_rule 自增从 10001 起：1–10000 留给系统内置规则
-- 新装已由 database/install/schema.sql 覆盖；已有库执行本脚本
-- 若表中已有 id >= 10001 的行，MySQL 会忽略更小的 AUTO_INCREMENT

ALTER TABLE `auth_rule` AUTO_INCREMENT = 10001;
