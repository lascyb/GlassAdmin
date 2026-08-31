<?php

declare(strict_types=1);

use phinx\Migration;

/**
 * AddRuleSortApi 内置规则：Rule/sort 绑到规则管理「编辑」按钮
 */
final class AddRuleSortApi extends Migration
{
    public function up(): void
    {
        if (!$this->logicalExists('system_auth_rule')) {
            return;
        }

        $rule = $this->q('system_auth_rule');
        $this->execute(
            "INSERT IGNORE INTO {$rule}
                (`id`,`scope`,`type`,`pid`,`name`,`path`,`method`,`component`,`icon`,`sort`,`status`,`visibility`,`description`,`create_time`,`update_time`)
             VALUES
                (93,'backend','api',16,'规则排序','system.auth.Rule/sort','PUT',NULL,NULL,10,1,0,NULL,NOW(),NOW())",
        );

        if ($this->logicalExists('system_auth_rule_api')) {
            $api = $this->q('system_auth_rule_api');
            $this->execute(
                "INSERT IGNORE INTO {$api} (`frontend_rule_id`,`backend_rule_id`) VALUES (37, 93)",
            );
        }

        if (class_exists(\app\admin\service\AdminRbacService::class)) {
            \app\admin\service\AdminRbacService::forgetAllRoleRbacCaches();
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
