<?php
declare (strict_types = 1);

namespace app\validate\system;

use think\Validate;

class Config extends Validate
{
    /**
     * 基础验证规则
     *
     * @var array<string, string>
     */
    protected $rule = [
        'key'         => 'max:100',
        'value'       => 'max:2000',
        'group'       => 'max:50',
        'name'        => 'max:100',
        'type'        => 'in:input,textarea,select,multi_select,switch,number,color',
        'options'     => 'max:1000',
        'description' => 'max:255',
        'sort'        => 'number|min:0',
        'status'      => 'in:0,1,2',
    ];

    /**
     * 错误提示信息
     *
     * @var array<string, string>
     */
    protected $message = [
        'key.max'         => '键名最多100个字符',
        'value.max'       => '值最多2000个字符',
        'group.max'       => '分组最多50个字符',
        'name.max'        => '名称最多100个字符',
        'type.in'         => '类型无效',
        'options.max'     => '选项最多1000个字符',
        'description.max' => '说明最多255个字符',
        'sort.number'     => '排序必须是数字',
        'sort.min'        => '排序不能小于0',
        'status.in'       => '状态无效',
    ];

    /**
     * 新增场景：key/group/name/type 必填，key 唯一
     */
    public function sceneSave(): static
    {
        return $this
            ->only(['key', 'value', 'group', 'name', 'type', 'options', 'description', 'sort', 'status'])
            ->append('key', 'require|unique:system_config')
            ->append('group', 'require')
            ->append('name', 'require')
            ->append('type', 'require')
            ->append('status', 'notIn:2')
            ->message([
                'key.require'  => '键名不能为空',
                'key.unique'   => '键名已存在',
                'group.require'=> '分组不能为空',
                'name.require' => '名称不能为空',
                'type.require' => '类型不能为空',
                'status.notIn' => '状态不允许设置为系统内置',
            ]);
    }

    /**
     * 更新场景：所有字段可选，传了则校验格式
     */
    public function sceneUpdate(): static
    {
        return $this
            ->only(['value', 'group', 'name', 'type', 'options', 'description', 'sort', 'status']);
    }
}
