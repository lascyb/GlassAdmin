<?php

declare(strict_types=1);

use phinx\Migration;

/**
 * RenameAuthTablesToSystemAuth 将 auth_* 表收拢为 system_auth_*，并更新接口 path / 组件 / 菜单名
 */
final class RenameAuthTablesToSystemAuth extends Migration
{
    /** @var array<string, string> 旧逻辑表名 → 新逻辑表名 */
    private const TABLES = [
        'auth_admin'                 => 'system_auth_admin',
        'auth_admin_role'            => 'system_auth_admin_role',
        'auth_admin_operation_log'   => 'system_auth_admin_operation_log',
        'auth_oauth2_feishu'         => 'system_auth_oauth2_feishu',
        'auth_role'                  => 'system_auth_role',
        'auth_role_rule'             => 'system_auth_role_rule',
        'auth_rule'                  => 'system_auth_rule',
        'auth_rule_api'              => 'system_auth_rule_api',
    ];

    public function up(): void
    {
        if ($this->logicalExists('auth_admin') && !$this->logicalExists('system_auth_admin')) {
            $parts = [];
            foreach (self::TABLES as $from => $to) {
                $parts[] = $this->q($from) . ' TO ' . $this->q($to);
            }
            $this->execute('RENAME TABLE ' . implode(', ', $parts));
        }

        if (!$this->logicalExists('system_auth_rule')) {
            return;
        }

        $rule = $this->q('system_auth_rule');
        $this->execute(
            "UPDATE {$rule} SET `path` = CONCAT('system.', `path`)
             WHERE `scope` = 'backend' AND `type` = 'api'
               AND (`path` LIKE 'auth.%' OR `path` LIKE 'account.%')",
        );
        $this->execute(
            "UPDATE {$rule} SET `component` = CONCAT('system/', `component`)
             WHERE `scope` = 'frontend' AND `component` LIKE 'auth/%'",
        );
        $this->execute(
            "UPDATE {$rule} SET `name` = '系统管理'
             WHERE `id` IN (54, 56) AND `name` = '常规管理'",
        );

        if ($this->logicalExists('system_attachment')) {
            $att = $this->q('system_attachment');
            $this->execute(
                "UPDATE {$att} SET `scene` = CONCAT('system.', `scene`)
                 WHERE `scene` LIKE 'account.%' OR `scene` LIKE 'auth.%'",
            );
        }
    }

    public function down(): void {}

    /** logicalExists 按逻辑表名探测（含前缀；不依赖 Phinx hasTable） */
    private function logicalExists(string $table): bool
    {
        $physical = $this->prefix() . $table;
        $quoted = "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], $physical) . "'";
        $row = $this->fetchRow('SHOW TABLES LIKE ' . $quoted);

        return is_array($row) && $row !== [];
    }
}
