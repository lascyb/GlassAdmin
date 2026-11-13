<?php

declare(strict_types=1);

use phinx\Migration;

/**
 * AddAdminLoginMethods 管理员 login_methods 列 + 默认登录方式系统配置
 */
final class AddAdminLoginMethods extends Migration
{
    public function up(): void
    {
        if ($this->logicalExists('system_auth_admin') && !$this->hasLogicalColumn('system_auth_admin', 'login_methods')) {
            $admin = $this->q('system_auth_admin');
            $this->execute(
                "ALTER TABLE {$admin}
                 ADD COLUMN `login_methods` json NULL COMMENT 'NULL=跟随系统默认；否则为允许的登录方式 JSON 数组' AFTER `status`",
            );
        }

        if (!$this->logicalExists('system_config')) {
            return;
        }

        $cfg = $this->q('system_config');
        $this->execute(
            "INSERT IGNORE INTO {$cfg}
                (`id`, `key`, `value`, `group`, `name`, `description`, `type`, `options`, `sort`, `status`, `create_time`, `update_time`)
             VALUES
                (13, 'admin_default_login_methods', '[\"password\",\"feishu\"]', '登录配置', '默认登录方式',
                 '账号未单独配置时继承；password=密码登录，feishu=飞书登录', 'multi_select', '[\"password\",\"feishu\"]',
                 10, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())",
        );

        $this->execute(
            "UPDATE {$cfg}
             SET `value` = '布局配置;上传配置;全局配置;登录配置;',
                 `update_time` = UNIX_TIMESTAMP()
             WHERE `key` = 'config_group_sort'
               AND `value` NOT LIKE '%登录配置%'",
        );

        if (class_exists(\app\admin\service\SystemConfigService::class)) {
            \app\admin\service\SystemConfigService::forgetConfigCache('admin_default_login_methods');
            \app\admin\service\SystemConfigService::forgetConfigCache('config_group_sort');
        }
    }

    public function down(): void {}

    /** logicalExists 按逻辑表名探测（含前缀） */
    private function logicalExists(string $table): bool
    {
        $physical = $this->prefix() . $table;
        $quoted = "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], $physical) . "'";
        $row = $this->fetchRow('SHOW TABLES LIKE ' . $quoted);

        return is_array($row) && $row !== [];
    }

    /** hasLogicalColumn 探测逻辑表是否已有列 */
    private function hasLogicalColumn(string $table, string $column): bool
    {
        $physical = $this->prefix() . $table;
        $sql = sprintf(
            'SHOW COLUMNS FROM `%s` LIKE %s',
            str_replace('`', '', $physical),
            "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], $column) . "'",
        );
        $row = $this->fetchRow($sql);

        return is_array($row) && $row !== [];
    }
}
