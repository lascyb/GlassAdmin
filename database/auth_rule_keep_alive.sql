-- auth_rule.keep_alive：页面是否 keep-alive（仅 frontend view/route 有效）
-- 新装已由 database/install/schema.sql 覆盖；已有库执行本脚本

ALTER TABLE `auth_rule`
    ADD COLUMN `keep_alive` tinyint NOT NULL DEFAULT 1
        COMMENT '{1:页面缓存,0:离开即销毁} 仅 frontend view/route 有效'
        AFTER `visibility`;

UPDATE `auth_rule`
SET `keep_alive` = 0
WHERE `type` IN ('iframe', 'link');
