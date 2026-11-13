<?php

declare(strict_types=1);

use phinx\Migration;

/**
 * AddRoleAssignAdminsApi 内置规则：Role/assignAdmins + 角色管理页「绑定用户」按钮
 *
 * 已拥有「角色管理」页（rule 27）的角色一并获得新按钮，避免升级后按钮消失；
 * 未绑定的角色仍可在规则管理里手动分配。
 */
final class AddRoleAssignAdminsApi extends Migration
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
                (94,'backend','api',17,'角色绑定管理员','system.auth.Role/assignAdmins','PUT',NULL,NULL,6,1,0,NULL,NOW(),NOW()),
                (95,'frontend','btn',27,'绑定用户','assignAdmins',NULL,NULL,'el:UserFilled',3,1,0,NULL,NOW(),NOW())",
        );

        if ($this->logicalExists('system_auth_rule_api')) {
            $api = $this->q('system_auth_rule_api');
            $this->execute(
                "INSERT IGNORE INTO {$api} (`frontend_rule_id`,`backend_rule_id`) VALUES (95, 94)",
            );
        }

        if ($this->logicalExists('system_auth_role_rule')) {
            $roleRule = $this->q('system_auth_role_rule');
            $this->execute(
                "INSERT IGNORE INTO {$roleRule} (`role_id`,`rule_id`)
                 SELECT rr.`role_id`, 95 FROM {$roleRule} rr WHERE rr.`rule_id` = 27",
            );
        }

        if (class_exists(\app\admin\model\system\auth\Rule::class)) {
            \app\admin\model\system\auth\Rule::forgetMenuCaches();
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
