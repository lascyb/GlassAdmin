<?php
declare (strict_types = 1);

namespace crud;

/**
 * Context 由配置派生命名与字段分组，供 Generator 使用
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
        $ctx->entityFile   = self::resolveEntityFile($ctx->entity, $ctx->paths);
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
        self::ensureDefaultEnumOptions($ctx);

        foreach ($ctx->fields as $f) {
            $prop = (string) ($f['prop'] ?? '');
            if ($prop === '') {
                continue;
            }
            $relation = self::relationType($f);
            $search   = $f['search'] ?? null;
            $form     = $f['form'] ?? null;
            $list     = $f['list'] ?? null;

            if (is_array($list) && ($list['enabled'] ?? true) !== false) {
                $ctx->listFields[] = $f;
            }
            if (is_array($form) && ($form['enabled'] ?? true) !== false) {
                $ctx->formFields[] = $f;
                if (in_array(self::formRender($f), ['image', 'file'], true)) {
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
     * ensureDefaultEnumOptions 字段已绑 enum.key 但 options 为空时补默认可跑项
     */
    private static function ensureDefaultEnumOptions(self $ctx): void
    {
        foreach ($ctx->fields as $f) {
            if (!is_array($f)) {
                continue;
            }
            $key = self::enumKey($f);
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
        $render = self::formRender($field);
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
    public static function columnType(array $field): string
    {
        $col = self::columnConfig($field);

        return is_array($col) ? (string) ($col['type'] ?? '') : '';
    }

    /** columnConfig 取 column 对象；enabled=false 返回 null */
    public static function columnConfig(array $field): ?array
    {
        $col = $field['column'] ?? null;
        if (!is_array($col) || (($col['enabled'] ?? true) === false)) {
            return null;
        }

        return $col;
    }

    /** relationType 取 relation.type；enabled=false 返回空 */
    public static function relationType(array $field): string
    {
        $rel = $field['relation'] ?? null;
        if (!is_array($rel) || (($rel['enabled'] ?? true) === false)) {
            return '';
        }

        return (string) ($rel['type'] ?? '');
    }

    /** relationConfig 取 relation 对象；enabled=false 返回 null */
    public static function relationConfig(array $field): ?array
    {
        $rel = $field['relation'] ?? null;
        if (!is_array($rel) || (($rel['enabled'] ?? true) === false)) {
            return null;
        }

        return $rel;
    }

    /** formConfig 取 form 对象；false/省略返回 null */
    public static function formConfig(array $field): ?array
    {
        $form = $field['form'] ?? null;

        return is_array($form) ? $form : null;
    }

    /** listConfig 取 list 对象 */
    public static function listConfig(array $field): ?array
    {
        $list = $field['list'] ?? null;

        return is_array($list) ? $list : null;
    }

    /** searchConfig 取 search 对象 */
    public static function searchConfig(array $field): ?array
    {
        $search = $field['search'] ?? null;

        return is_array($search) ? $search : null;
    }

    /** formRender 表单控件类型 */
    public static function formRender(array $field): string
    {
        $form = self::formConfig($field);

        return $form !== null ? (string) ($form['render'] ?? '') : '';
    }

    /** listRender 列表列渲染类型 */
    public static function listRender(array $field): string
    {
        $list = self::listConfig($field);

        return $list !== null ? (string) ($list['render'] ?? 'text') : '';
    }

    /** searchRender 搜索控件类型（显式 SearchRenderType，或按 form 映射推断） */
    public static function searchRender(array $field): string
    {
        $search = self::searchConfig($field);
        if ($search !== null && !empty($search['render'])) {
            return (string) $search['render'];
        }
        $form = self::formConfig($field);
        if ($form !== null && ($form['enabled'] ?? true) !== false) {
            $mapped = self::formToSearchRender((string) ($form['render'] ?? ''));
            if ($mapped !== '') {
                return $mapped;
            }
        }
        $col = self::columnConfig($field);
        $ct = is_array($col) ? (string) ($col['type'] ?? '') : '';

        return match ($ct) {
            'tinyint' => 'bool',
            'int', 'bigint', 'decimal' => 'number_range',
            'date' => 'date_range',
            'datetime', 'timestamp' => 'datetime_range',
            default => 'input',
        };
    }

    /**
     * formToSearchRender 表单控件 → 默认搜索控件（与 web form-render.ts inference.search.render 对齐）
     * {number:number_range, date:date_range, datetime:datetime_range, switch:bool, radio:select}
     */
    public static function formToSearchRender(string $formRender): string
    {
        return match ($formRender) {
            'input', 'password' => 'input',
            'number' => 'number_range',
            'switch' => 'bool',
            'radio', 'select' => 'select',
            'remote_select' => 'remote_select',
            'remote_multi_select' => 'remote_multi_select',
            'date' => 'date_range',
            'datetime', 'time' => 'datetime_range',
            default => '',
        };
    }

    /** isPrimary 是否主键列 */
    public static function isPrimary(array $field): bool
    {
        $col = self::columnConfig($field);

        return $col !== null && !empty($col['primary']);
    }

    /** primaryKey 主键列名（无则 id） */
    public function primaryKey(): string
    {
        foreach ($this->fields as $f) {
            if (self::isPrimary($f)) {
                return (string) $f['prop'];
            }
        }

        return 'id';
    }

    /** isIntPrimary 主键是否整型列 */
    public function isIntPrimary(): bool
    {
        foreach ($this->fields as $f) {
            if (!self::isPrimary($f)) {
                continue;
            }
            $ct = self::columnType($f);

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
    public static function isRequired(array $field): bool
    {
        $form = self::formConfig($field);

        return $form !== null && !empty($form['required']);
    }

    /** isUnique 列是否唯一 */
    public static function isUnique(array $field): bool
    {
        $col = self::columnConfig($field);

        return $col !== null && !empty($col['unique']);
    }

    /** isBoolField 开关/布尔语义（form switch 或 list/search bool） */
    public static function isBoolField(array $field): bool
    {
        return self::formRender($field) === 'switch'
            || self::listRender($field) === 'bool'
            || self::searchRender($field) === 'bool';
    }

    /** isEnumField 带 enumKey 的枚举语义（远程关联字段排除） */
    public static function isEnumField(array $field): bool
    {
        if (self::isRemoteField($field)) {
            return false;
        }

        return self::enumKey($field) !== '';
    }

    /** isUnixDatetime bigint + datetime 渲染（Unix 秒） */
    public static function isUnixDatetime(array $field): bool
    {
        return self::columnType($field) === 'bigint'
            && (self::formRender($field) === 'datetime' || self::listRender($field) === 'datetime');
    }

    /** isStringColumn varchar/char/text 类 */
    public static function isStringColumn(array $field): bool
    {
        return in_array(self::columnType($field), ['varchar', 'char', 'text', 'mediumtext', 'longtext'], true);
    }

    /** isIntColumn 整型列 */
    public static function isIntColumn(array $field): bool
    {
        return in_array(self::columnType($field), ['bigint', 'int', 'tinyint'], true);
    }

    /** isRangeSearch 是否区间类搜索控件 */
    public static function isRangeSearch(array $field): bool
    {
        return in_array(self::searchRender($field), ['number_range', 'date_range', 'datetime_range'], true);
    }

    /**
     * fieldExt 取字段扩展参数
     */
    public static function fieldExt(array $field): array
    {
        return is_array($field['ext'] ?? null) ? $field['ext'] : [];
    }

    /**
     * enumExt 取枚举扩展参数
     */
    public static function enumExt(array $field): ?array
    {
        $ext = self::fieldExt($field);

        return is_array($ext['enum'] ?? null) ? $ext['enum'] : null;
    }

    /**
     * remoteExt 取远程扩展参数
     */
    public static function remoteExt(array $field): ?array
    {
        $ext = self::fieldExt($field);

        return is_array($ext['remote'] ?? null) ? $ext['remote'] : null;
    }

    /** isRemoteField 远程关联/远程下拉字段 */
    public static function isRemoteField(array $field): bool
    {
        if (in_array(self::formRender($field), ['remote_select', 'remote_multi_select'], true)) {
            return true;
        }
        $search = self::searchConfig($field);
        if ($search !== null && in_array((string) ($search['render'] ?? ''), ['remote_select', 'remote_multi_select'], true)) {
            return true;
        }
        if (self::remoteApi($field) !== '') {
            return true;
        }

        return false;
    }

    /**
     * enumKey 取 options 键名（ext.enum.key；远程字段不使用枚举）
     */
    public static function enumKey(array $field): string
    {
        if (self::isRemoteField($field)) {
            return '';
        }
        $enum = self::enumExt($field);
        if ($enum !== null && !empty($enum['key'])) {
            return (string) $enum['key'];
        }

        return '';
    }

    /**
     * remoteApi 取远程 api（ext.remote.api）
     */
    public static function remoteApi(array $field): string
    {
        $remote = self::remoteExt($field);
        if ($remote !== null && !empty($remote['api'])) {
            return (string) $remote['api'];
        }

        return '';
    }

    /** remoteValueField 远程选项 value 字段 */
    public static function remoteValueField(array $field): string
    {
        $remote = self::remoteExt($field);
        $v = $remote['valueField'] ?? 'id';

        return $v === '' ? 'id' : (string) $v;
    }

    /** remoteLabelField 远程选项 label 字段 */
    public static function remoteLabelField(array $field): string
    {
        $remote = self::remoteExt($field);
        $v = $remote['labelField'] ?? 'name';

        return $v === '' ? 'name' : (string) $v;
    }

    /**
     * relationProp 关联展示字段（list.relationProp）
     */
    public static function relationProp(array $field): string
    {
        $list = self::listConfig($field);

        return $list !== null ? (string) ($list['relationProp'] ?? '') : '';
    }

    /** formMaxLength 表单 maxLength */
    public static function formMaxLength(array $field): ?int
    {
        $form = self::formConfig($field);
        if ($form !== null && isset($form['maxLength'])) {
            return (int) $form['maxLength'];
        }
        $col = self::columnConfig($field);
        if ($col !== null && isset($col['length'])) {
            return (int) $col['length'];
        }

        return null;
    }

    /** formPlaceholder 按 form.render + label 生成占位符 */
    public static function formPlaceholder(array $field): string
    {
        $label = trim((string) ($field['label'] ?? ''));
        $render = self::formRender($field);

        return match ($render) {
            'input', 'password', 'textarea', 'number', 'readonly' => $label !== '' ? "请输入{$label}" : '请输入',
            'select', 'multi_select', 'checkbox', 'radio' => '请选择',
            'remote_select', 'remote_multi_select' => $label !== '' ? "输入关键词搜索{$label}" : '输入关键词搜索',
            'date' => '选择日期',
            'datetime', 'time' => '选择日期时间',
            default => '',
        };
    }

    /** searchPlaceholder 按 search.render + label 生成占位符 */
    public static function searchPlaceholder(array $field, string $searchRender): string
    {
        $label = trim((string) ($field['label'] ?? ''));

        return match ($searchRender) {
            'remote_select', 'remote_multi_select' => $label !== '' ? "搜索{$label}" : '搜索',
            'input' => '模糊匹配',
            default => '',
        };
    }

    /** decimalScale decimal 小数位 */
    public static function decimalScale(array $field): int
    {
        $col = self::columnConfig($field);
        if ($col !== null && isset($col['scale'])) {
            return (int) $col['scale'];
        }

        return 2;
    }

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
            $key = self::enumKey($f);
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
    public static function parseRemoteApi(?string $optionsKey): ?array
    {
        if ($optionsKey === null || $optionsKey === '') {
            return null;
        }
        // module 为点分小写路径（可多级），entity 为 PascalCase
        if (!preg_match('/^((?:[a-z][a-z0-9_]*)(?:\.[a-z][a-z0-9_]*)*)\.([A-Z][A-Za-z0-9]*)\/list$/', $optionsKey, $m)) {
            return null;
        }

        return [
            'module' => str_replace('.', '/', $m[1]),
            'entity' => $m[2],
            'url'    => $optionsKey,
        ];
    }

    /**
     * relationMethodName 关联方法名（始终按 prop 推导，避免 relationProp 陈旧如 field_2）
     * {user_id:user, tag_ids:tags}；ThinkPHP with() 会 snake→camel，方法名勿含下划线
     */
    public static function relationMethodName(array $field): string
    {
        $prop = (string) ($field['prop'] ?? '');
        if (str_ends_with($prop, '_ids')) {
            return substr($prop, 0, -4) . 's';
        }
        if (str_ends_with($prop, '_id')) {
            return substr($prop, 0, -3);
        }
        // 无 _id/_ids 后缀时退回 relationProp 首段，并规范为可调用方法名
        $rp = self::relationProp($field);
        $name = $rp !== ''
            ? (str_contains($rp, '.') ? explode('.', $rp, 2)[0] : $rp)
            : $prop;
        if ($name === '') {
            return 'related';
        }
        // field_2 → field2，避免 with('field_2') 找不到 field_2() 而落到 Query->field2
        $name = str_contains($name, '_') ? self::toCamel($name) : $name;
        // 关联方法名不能与本地列名相同：否则模型属性与关联同名、Row 出现重复键、with() 覆盖标量列
        if ($name === $prop) {
            $name .= 'Related';
        }

        return $name;
    }

    /**
     * uploadActionName image 字段上传动作名（cover → uploadCover）
     */
    public static function uploadActionName(string $prop): string
    {
        $parts = explode('_', $prop);
        $parts = array_map(static fn (string $p): string => ucfirst($p), $parts);

        return 'upload' . implode('', $parts);
    }

    /**
     * resolveEntityFile 前端文件名：优先 paths.apis 基名，否则 PascalCase → snake_case
     *
     * @param array<string, mixed> $paths
     */
    public static function resolveEntityFile(string $entity, array $paths): string
    {
        $apis = trim(str_replace('\\', '/', (string) ($paths['apis'] ?? '')));
        if ($apis !== '') {
            $base = basename(preg_replace('/\.ts$/i', '', $apis) ?? $apis);
            if ($base !== '') {
                return $base;
            }
        }
        $views = trim(str_replace('\\', '/', (string) ($paths['viewsDir'] ?? '')));
        if ($views !== '') {
            $base = basename(rtrim($views, '/'));
            if ($base !== '') {
                return $base;
            }
        }

        return self::toSnake($entity);
    }

    /**
     * toSnake PascalCase/camelCase → snake_case（MultiSelect → multi_select）
     */
    public static function toSnake(string $name): string
    {
        $s = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $name) ?? $name;
        $s = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1_$2', $s) ?? $s;

        return strtolower($s);
    }

    /**
     * toCamel snake → camel（category_id → categoryId）
     */
    public static function toCamel(string $snake): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $snake))));
    }

    /**
     * enumValues 从 options 取枚举合法值列表
     *
     * @return list<int|string>
     */
    public function enumValues(array $field): array
    {
        $key = self::enumKey($field);
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
