<?php
declare (strict_types = 1);

namespace crud;

use crud\field\FieldMeta;
use crud\field\Naming;

/**
 * Context 实例上下文：配置装载、字段分组、路径与菜单（字段探针见 FieldMeta）
 *
 * 字段契约：column / relation / list / search / form / ext（见 web/src/views/dev/curd/types）
 */
class Context
{
    public string $module;
    public string $entity;
    public string $entityFile;
    public string $table;
    public string $title;
    public string $apiBase;
    public string $nsModel;
    public string $nsController;
    public string $nsValidate;
    public bool $generateAuth;

    /** @var array{controller: string, model: string, validate: string, apis: string, viewsDir: string} */
    public array $paths;

    /** @var list<array{type: string, path: string, title: string, icon?: string, sort?: int, enabled: bool}> */
    public array $menus = [];

    /** @var list<array<string, mixed>> */
    public array $fields;

    /** @var array<string, list<array{value: string|int, label: string, type?: string}>> */
    public array $options;

    /** @var list<array<string, mixed>> */
    public array $formFields = [];

    /** @var list<array<string, mixed>> */
    public array $listFields = [];

    /** @var list<array<string, mixed>> */
    public array $keywordFields = [];

    /** @var list<array<string, mixed>> */
    public array $advancedFields = [];

    /** @var list<array<string, mixed>> */
    public array $writableFields = [];

    /** @var list<array<string, mixed>> */
    public array $belongsToFields = [];

    /** @var list<array<string, mixed>> */
    public array $belongsToManyFields = [];

    /** @var list<array<string, mixed>> */
    public array $imageFields = [];

    /**
     * fromConfig 构建上下文
     *
     * @param array<string, mixed> $config
     */
    public static function fromConfig(array $config): self
    {
        $ctx = new self();
        $ctx->module       = (string) $config['module'];
        $ctx->entity       = (string) $config['entity'];
        $ctx->table        = (string) $config['table'];
        $ctx->title        = (string) $config['title'];
        $moduleNs          = $ctx->module === '' ? '' : str_replace('/', '\\', $ctx->module);
        $ctx->apiBase      = $ctx->module === ''
            ? $ctx->entity
            : str_replace('/', '.', $ctx->module) . '.' . $ctx->entity;
        $ctx->nsModel      = $moduleNs === '' ? 'app\\admin\\model' : 'app\\admin\\model\\' . $moduleNs;
        $ctx->nsController = $moduleNs === '' ? 'app\\admin\\controller' : 'app\\admin\\controller\\' . $moduleNs;
        $ctx->nsValidate   = $moduleNs === '' ? 'app\\admin\\validate' : 'app\\admin\\validate\\' . $moduleNs;
        $ctx->generateAuth = (bool) $config['generateAuth'];
        $ctx->menus        = is_array($config['menus'] ?? null) ? $config['menus'] : [];
        $ctx->paths        = is_array($config['paths'] ?? null) ? $config['paths'] : [];
        $ctx->entityFile   = Naming::resolveEntityFile($ctx->entity, $ctx->paths);
        if ($ctx->paths === []) {
            $ctx->paths = [
                'controller' => ($ctx->module !== '' ? $ctx->module . '/' : '') . $ctx->entity . '.php',
                'model'      => ($ctx->module !== '' ? $ctx->module . '/' : '') . $ctx->entity . '.php',
                'validate'   => ($ctx->module !== '' ? $ctx->module . '/' : '') . $ctx->entity . '.php',
                'apis'       => ($ctx->module !== '' ? $ctx->module . '/' : '') . $ctx->entityFile . '.ts',
                'viewsDir'   => ($ctx->module !== '' ? $ctx->module . '/' : '') . $ctx->entityFile . '/',
            ];
        }
        $ctx->fields       = $config['fields'];
        $ctx->options      = self::normalizeOptionsMap($config['options'] ?? null);
        self::syncEnumKeysFromProp($ctx);
        self::ensureDefaultEnumOptions($ctx);

        foreach ($ctx->fields as $f) {
            $prop = (string) ($f['prop'] ?? '');
            if ($prop === '') {
                continue;
            }
            $relation = FieldMeta::relationType($f);
            $search   = $f['search'] ?? null;
            $form     = $f['form'] ?? null;
            $list     = $f['list'] ?? null;

            if (is_array($list) && ($list['enabled'] ?? true) !== false) {
                $ctx->listFields[] = $f;
            }
            if (is_array($form) && ($form['enabled'] ?? true) !== false) {
                $ctx->formFields[] = $f;
                if (in_array(FieldMeta::formRender($f), ['image', 'file', 'image_upload'], true)) {
                    $ctx->imageFields[] = $f;
                }
            }
            if (is_array($search) && ($search['enabled'] ?? true) !== false) {
                if (($search['keyword'] ?? false) === true) {
                    $ctx->keywordFields[] = $f;
                }
                if (($search['advanced'] ?? false) === true) {
                    $ctx->advancedFields[] = $f;
                }
            }
            if ($relation === 'belongs_to') {
                $ctx->belongsToFields[] = $f;
            }
            if ($relation === 'belongs_to_many') {
                $ctx->belongsToManyFields[] = $f;
            }
            // writable：进表单且非多对多（多对多走 sync）
            if (is_array($form) && ($form['enabled'] ?? true) !== false && $relation !== 'belongs_to_many') {
                $ctx->writableFields[] = $f;
            }
        }

        return $ctx;
    }

    /**
     * normalizeOptionsMap 规范化 options（拒绝 JSON [] list）
     *
     * @return array<string, list<array{value: string|int, label: string, type?: string}>>
     */
    private static function normalizeOptionsMap(mixed $raw): array
    {
        if (!is_array($raw) || $raw === []) {
            return [];
        }
        if (array_is_list($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $key => $items) {
            if (!is_string($key) || $key === '' || !is_array($items)) {
                continue;
            }
            $list = [];
            foreach ($items as $item) {
                if (!is_array($item) || !array_key_exists('value', $item)) {
                    continue;
                }
                $row = [
                    'value' => $item['value'],
                    'label' => (string) ($item['label'] ?? $item['value'] ?? ''),
                ];
                if (!empty($item['type']) && is_string($item['type'])) {
                    $row['type'] = $item['type'];
                }
                $list[] = $row;
            }
            if ($list !== []) {
                $out[$key] = $list;
            }
        }

        return $out;
    }

    /**
     * syncEnumKeysFromProp 枚举常量名按字段 prop 对齐（field_enmu → FIELD_ENMU_OPTIONS），并迁移 options
     */
    private static function syncEnumKeysFromProp(self $ctx): void
    {
        foreach ($ctx->fields as $i => $f) {
            if (!is_array($f) || FieldMeta::isRemoteField($f)) {
                continue;
            }
            $enum = FieldMeta::enumExt($f);
            if ($enum === null || empty($enum['key'])) {
                continue;
            }
            $prev = (string) $enum['key'];
            $next = Naming::enumOptionsKey((string) ($f['prop'] ?? ''));
            if ($prev === $next) {
                continue;
            }
            $ctx->fields[$i]['ext']['enum']['key'] = $next;
            if ($prev !== '' && !empty($ctx->options[$prev]) && empty($ctx->options[$next])) {
                $ctx->options[$next] = $ctx->options[$prev];
            }
            $stillUsed = false;
            foreach ($ctx->fields as $j => $other) {
                if ($j === $i || !is_array($other)) {
                    continue;
                }
                if ((string) ($ctx->fields[$j]['ext']['enum']['key'] ?? '') === $prev) {
                    $stillUsed = true;
                    break;
                }
            }
            if (!$stillUsed) {
                unset($ctx->options[$prev]);
            }
        }
    }

    /**
     * ensureDefaultEnumOptions 字段已绑 enum.key 但 options 为空时补默认可跑项
     */
    private static function ensureDefaultEnumOptions(self $ctx): void
    {
        foreach ($ctx->fields as $f) {
            if (!is_array($f)) {
                continue;
            }
            $key = FieldMeta::enumKey($f);
            if ($key === '' || !empty($ctx->options[$key])) {
                continue;
            }
            $ctx->options[$key] = self::defaultEnumOptions($f);
        }
    }

    /**
     * defaultEnumOptions 按 form.render 返回默认可跑 options
     *
     * @param array<string, mixed> $field
     * @return list<array{value: string|int, label: string, type?: string}>
     */
    private static function defaultEnumOptions(array $field): array
    {
        $render = FieldMeta::formRender($field);
        if (in_array($render, ['multi_select', 'checkbox'], true)) {
            return [
                ['value' => 'a', 'label' => '选项 A'],
                ['value' => 'b', 'label' => '选项 B'],
                ['value' => 'c', 'label' => '选项 C'],
            ];
        }
        if ($render === 'select') {
            return [
                ['value' => 1, 'label' => '选项一', 'type' => 'info'],
                ['value' => 2, 'label' => '选项二', 'type' => 'warning'],
                ['value' => 3, 'label' => '选项三', 'type' => 'danger'],
            ];
        }

        return [
            ['value' => 1, 'label' => '启用', 'type' => 'success'],
            ['value' => 0, 'label' => '禁用', 'type' => 'info'],
        ];
    }

    /**
     * resolvedMenuChain 过滤 enabled 的菜单链（含 view）
     *
     * @return list<array{type: string, path: string, title: string, icon?: string, sort?: int, enabled: bool}>
     */
    public function resolvedMenuChain(): array
    {
        $chain = [];
        foreach ($this->menus as $m) {
            if (!empty($m['enabled'])) {
                $chain[] = $m;
            }
        }

        return $chain;
    }

    /**
     * keywordSourceFields 参与顶部关键词搜索的字段（search.keyword=true）
     *
     * @return list<array<string, mixed>>
     */
    public function keywordSourceFields(): array
    {
        return $this->keywordFields;
    }

    /** viewComponent 前端 view 组件路径 */
    public function viewComponent(): string
    {
        return rtrim($this->paths['viewsDir'], '/') . '/index.vue';
    }

    /** pathModel Model 相对路径 */
    public function pathModel(): string
    {
        return 'app/admin/model/' . ltrim($this->paths['model'], '/');
    }

    /** pathController Controller 相对路径 */
    public function pathController(): string
    {
        return 'app/admin/controller/' . ltrim($this->paths['controller'], '/');
    }

    /** pathValidate Validate 相对路径 */
    public function pathValidate(): string
    {
        return 'app/admin/validate/' . ltrim($this->paths['validate'], '/');
    }

    /** pathApis 前端 apis 相对路径 */
    public function pathApis(): string
    {
        return 'web/src/apis/' . ltrim($this->paths['apis'], '/');
    }

    /** pathIndex 列表页相对路径 */
    public function pathIndex(): string
    {
        return 'web/src/views/' . rtrim($this->paths['viewsDir'], '/') . '/index.vue';
    }

    /** pathEdit 编辑页相对路径 */
    public function pathEdit(): string
    {
        return 'web/src/views/' . rtrim($this->paths['viewsDir'], '/') . '/edit.vue';
    }

    /** pathConstants constants 相对路径 */
    public function pathConstants(): string
    {
        return 'web/src/views/' . rtrim($this->paths['viewsDir'], '/') . '/constants.ts';
    }

    /** columnType 取 column.type；enabled=false 返回空 */

    /** columnConfig 取 column 对象；enabled=false 返回 null */

    /** relationType 取 relation.type；enabled=false 返回空 */

    /** relationConfig 取 relation 对象；enabled=false 返回 null */

    /** formConfig 取 form 对象；false/省略返回 null */

    /** listConfig 取 list 对象 */

    /** searchConfig 取 search 对象 */

    /** formRender 表单控件类型 */

    /** listRender 列表列渲染类型 */

    /** searchRender 搜索控件类型（显式 SearchRenderType，或按 form 映射推断） */

    /**
     * formToSearchRender 表单控件 → 默认搜索控件（与 web form-render.ts inference.search.render 对齐）
     * {number:number_range, date:date_range, datetime:datetime_range, switch:bool, radio:select}
     */

    /** isPrimary 是否主键列 */

    /** primaryKey 主键列名（无则 id） */
    public function primaryKey(): string
    {
        foreach ($this->fields as $f) {
            if (FieldMeta::isPrimary($f)) {
                return (string) $f['prop'];
            }
        }

        return 'id';
    }

    /** isIntPrimary 主键是否整型列 */
    public function isIntPrimary(): bool
    {
        foreach ($this->fields as $f) {
            if (!FieldMeta::isPrimary($f)) {
                continue;
            }
            $ct = FieldMeta::columnType($f);

            return in_array($ct, ['bigint', 'int', 'tinyint'], true);
        }

        return true;
    }

    /** hasTimestampFields 是否含 create_time / update_time 列 */
    public function hasTimestampFields(): bool
    {
        foreach ($this->fields as $f) {
            $prop = (string) ($f['prop'] ?? '');
            if ($prop === 'create_time' || $prop === 'update_time') {
                return true;
            }
        }

        return false;
    }

    /** isRequired 表单是否必填 */

    /** isUnique 列是否唯一 */

    /** isBoolField 开关/布尔语义（form switch 或 list/search bool） */

    /** isEnumField 带 enumKey 的枚举语义（远程关联字段排除） */

    /** isUnixDatetime bigint + datetime 渲染（Unix 秒） */

    /** isStringColumn varchar/char/text 类 */

    /** isIntColumn 整型列 */

    /** isRangeSearch 是否区间类搜索控件 */

    /**
     * fieldExt 取字段扩展参数
     */

    /**
     * enumExt 取枚举扩展参数
     */

    /**
     * remoteExt 取远程扩展参数
     */

    /** isRemoteField 远程关联/远程下拉字段 */

    /**
     * enumKey 取 options 键名（ext.enum.key；远程字段不使用枚举）
     */

    /**
     * remoteApi 取远程 api（ext.remote.api）
     */

    /** remoteValueField 远程选项 value 字段 */

    /** remoteLabelField 远程选项 label 字段 */

    /**
     * relationProp 关联展示字段（list.relationProp）
     */

    /** formMaxLength 表单 maxLength */

    /** formPlaceholder 按 form.render + label 生成占位符 */

    /** searchPlaceholder 按 search.render + label 生成占位符 */

    /** decimalScale decimal 小数位 */

    /** needConstants 是否需要生成 constants.ts */
    public function needConstants(): bool
    {
        return $this->constantNames() !== [];
    }

    /**
     * constantNames 枚举常量名（options 键 ∪ 字段 ext.enum.key）
     *
     * @return list<string>
     */
    public function constantNames(): array
    {
        $names = array_keys($this->options);
        foreach ($this->fields as $f) {
            if (!is_array($f)) {
                continue;
            }
            $key = FieldMeta::enumKey($f);
            if ($key !== '') {
                $names[] = $key;
            }
        }
        $names = array_values(array_unique(array_filter($names, static fn ($n): bool => is_string($n) && $n !== '')));
        sort($names);

        return $names;
    }

    /**
     * parseRemoteApi 解析 remoteApi 为 Module.Entity（支持嵌套模块 test.ft.Text/list）
     *
     * @return array{module: string, entity: string, url: string}|null
     */

    /**
     * relationMethodName 关联方法名（始终按 prop 推导，避免 relationProp 陈旧如 field_2）
     * {user_id:user, tag_ids:tags}；ThinkPHP with() 会 snake→camel，方法名勿含下划线
     */

    /**
     * uploadActionName image 字段上传动作名（cover → uploadCover）
     */

    /**
     * resolveEntityFile 前端文件名：优先 paths.apis 基名，否则 PascalCase → snake_case
     *
     * @param array<string, mixed> $paths
     */

    /**
     * toSnake PascalCase/camelCase → snake_case（MultiSelect → multi_select）
     */

    /**
     * toCamel snake → camel（category_id → categoryId）
     */

    /**
     * enumValues 从 options 取枚举合法值列表
     *
     * @return list<int|string>
     */
    public function enumValues(array $field): array
    {
        $key = FieldMeta::enumKey($field);
        if ($key === '' || !isset($this->options[$key]) || !is_array($this->options[$key])) {
            return [0, 1];
        }
        $vals = [];
        foreach ($this->options[$key] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $v = $item['value'] ?? null;
            if ($v === null || $v === '') {
                continue;
            }
            if (is_int($v) || is_float($v)) {
                $vals[] = $v;
            } elseif (is_string($v) && preg_match('/^-?\d+$/', $v) === 1) {
                // 纯整数字符串按 int（与 InlineOptionsEditor 写回一致）
                $vals[] = (int) $v;
            } else {
                $vals[] = is_string($v) ? $v : (string) $v;
            }
        }

        return $vals !== [] ? $vals : [0, 1];
    }
}
