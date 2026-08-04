<?php
declare (strict_types = 1);

namespace app\admin\validate\system\workbench;

use think\Validate;

/**
 * Shortcut 工作台快捷入口校验
 */
class Shortcut extends Validate
{
    /** 单管理员快捷入口上限 */
    public const int MAX_ITEMS = 16;

    /**
     * @var array<string, string>
     */
    protected $rule = [
        'rule_ids' => 'array|checkRuleIds',
    ];

    /**
     * @var array<string, string>
     */
    protected $message = [
        'rule_ids.array' => 'rule_ids 必须是数组',
    ];

    /**
     * sceneSave 全量保存
     */
    public function sceneSave(): static
    {
        return $this->only(['rule_ids']);
    }

    /**
     * checkRuleIds 校验 rule_ids：可空、正整数、去重后不超过上限
     *
     * @param mixed $value
     */
    protected function checkRuleIds(mixed $value): bool|string
    {
        if (!is_array($value)) {
            return 'rule_ids 必须是数组';
        }
        if (count($value) > self::MAX_ITEMS) {
            return '快捷入口最多 ' . self::MAX_ITEMS . ' 个';
        }
        $seen = [];
        foreach ($value as $id) {
            if (!is_numeric($id) || (int) $id <= 0) {
                return 'rule_ids 元素必须为正整数';
            }
            $rid = (int) $id;
            if (isset($seen[$rid])) {
                return 'rule_ids 不可重复';
            }
            $seen[$rid] = true;
        }

        return true;
    }
}
