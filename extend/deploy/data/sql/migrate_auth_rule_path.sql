-- auth_rule.path 修正：与 ThinkPHP pathinfo 一致（{目录}.{控制器}/{方法}）
-- 适用已有库；全新安装请直接用 glass_admin.sql
-- 执行后建议清理 RBAC 缓存（Redis/文件缓存中 admin_rbac_api_ids:*）

UPDATE `auth_rule` SET `path` = 'auth.Rule/tree' WHERE `id` = 2;
UPDATE `auth_rule` SET `path` = 'auth.Rule/read' WHERE `id` = 3;
UPDATE `auth_rule` SET `path` = 'auth.Rule/save' WHERE `id` = 4;
UPDATE `auth_rule` SET `path` = 'auth.Rule/update' WHERE `id` = 5;
UPDATE `auth_rule` SET `path` = 'auth.Rule/delete' WHERE `id` = 6;
UPDATE `auth_rule` SET `path` = 'auth.Rule/bindApis' WHERE `id` = 7;
UPDATE `auth_rule` SET `path` = 'auth.Role/tree' WHERE `id` = 9;
UPDATE `auth_rule` SET `path` = 'auth.Role/read' WHERE `id` = 10;
UPDATE `auth_rule` SET `path` = 'auth.Role/save' WHERE `id` = 11;
UPDATE `auth_rule` SET `path` = 'auth.Role/update' WHERE `id` = 12;
UPDATE `auth_rule` SET `path` = 'auth.Role/delete' WHERE `id` = 13;
UPDATE `auth_rule` SET `path` = 'auth.Role/assignRules' WHERE `id` = 14;
UPDATE `auth_rule` SET `path` = 'Menu/menus' WHERE `id` = 15;
UPDATE `auth_rule` SET `path` = 'auth.Admin/list' WHERE `id` = 20;
UPDATE `auth_rule` SET `path` = 'auth.Admin/read' WHERE `id` = 21;
UPDATE `auth_rule` SET `path` = 'auth.Admin/save' WHERE `id` = 22;
UPDATE `auth_rule` SET `path` = 'auth.Admin/update' WHERE `id` = 23;
UPDATE `auth_rule` SET `path` = 'auth.Admin/delete' WHERE `id` = 24;
UPDATE `auth_rule` SET `path` = 'account.Profile/read' WHERE `id` = 26;
UPDATE `auth_rule` SET `path` = 'account.Profile/update' WHERE `id` = 27;
UPDATE `auth_rule` SET `path` = 'account.OperationLog/index' WHERE `id` = 28;
UPDATE `auth_rule` SET `path` = 'account.Notification/index' WHERE `id` = 29;
UPDATE `auth_rule` SET `path` = 'account.Notification/markRead' WHERE `id` = 30;
UPDATE `auth_rule` SET `path` = 'account.Profile/uploadAvatar' WHERE `id` = 31;
UPDATE `auth_rule` SET `path` = 'account.Profile/switchRoles' WHERE `id` = 33;
