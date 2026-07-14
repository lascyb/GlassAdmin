<?php
declare (strict_types = 1);

namespace crud;

/**
 * Context 由配置派生命名与字段分组，供 Generator 使用
 *
 * 字段契约：column / relation / list / search / form（见 web/src/crud/types.ts）
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
    public string $writeMode;
    public string $moduleTitle;
    public bool $generateAuth;
    public string $menuIcon;
    public int $menuSort;

    /** @var array{controller: string, model: string, validate: string, apis: string, viewsDir: string} */
    public array $paths;

    /** @var list<array{type: string, path: string, title: string, icon?: string, sort?: int, enabled: bool}> */
    public array $menus = [];

    /** @var list<array<string, mixed>> */
    public array $fields;

    /** @var array<string, array<string, array{label: string, type?: string}>> */
    public array $maps;

    /** @var array<string, list<array{value: string|int, label: string}>> */
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
        $ctx->entityFile   = strtolower($ctx->entity);
        $ctx->table        = (string) $config['table'];
        $ctx->title        = (string) $config['title'];
        $moduleNs          = $ctx->module === '' ? '' : str_replace('/', '\\', $ctx->module);
        $ctx->apiBase      = $ctx->module === ''
            ? $ctx->entity
            : str_replace('/', '.', $ctx->module) . '.' . $ctx->entity;
        $ctx->nsModel      = $moduleNs === '' ? 'app\\admin\\model' : 'app\\admin\\model\\' . $moduleNs;
        $ctx->nsController = $moduleNs === '' ? 'app\\admin\\controller' : 'app\\admin\\controller\\' . $moduleNs;
        $ctx->nsValidate   = $moduleNs === '' ? 'app\\admin\\validate' : 'app\\admin\\validate\\' . $moduleNs;
        $ctx->writeMode    = (string) $config['writeMode'];
        $ctx->moduleTitle  = (string) $config['moduleTitle'];
        $ctx->generateAuth = (bool) $config['generateAuth'];
        $ctx->menuIcon     = (string) $config['menuIcon'];
        $ctx->menuSort     = (int) $config['menuSort'];
        $ctx->menus        = is_array($config['menus'] ?? null) ? $config['menus'] : [];
        $ctx->paths        = is_array($config['paths'] ?? null) ? $config['paths'] : [
            'controller' => ($ctx->module !== '' ? $ctx->module . '/' : '') . $ctx->entity . '.php',
            'model'      => ($ctx->module !== '' ? $ctx->module . '/' : '') . $ctx->entity . '.php',
            'validate'   => ($ctx->module !== '' ? $ctx->module . '/' : '') . $ctx->entity . '.php',
            'apis'       => ($ctx->module !== '' ? $ctx->module . '/' : '') . $ctx->entityFile . '.ts',
            'viewsDir'   => ($ctx->module !== '' ? $ctx->module . '/' : '') . $ctx->entityFile . '/',
        ];
        $ctx->fields       = $config['fields'];
        $ctx->maps         = $config['maps'];
        $ctx->options      = $config['options'];

        foreach ($ctx->fields as $f) {
            $prop = (string) ($f['prop'] ?? '');
            if ($prop === '') {
                continue;
            }
            $relation = self::relationType($f);
            $search   = $f['search'] ?? null;
            $form     = $f['form'] ?? null;
            $list     = $f['list'] ?? null;

            if (is_array($list)) {
                $ctx->listFields[] = $f;
            }
            if (is_array($form)) {
                $ctx->formFields[] = $f;
                if (self::formRender($f) === 'image') {
                    $ctx->imageFields[] = $f;
                }
            }
            if (is_array($search)) {
                $mode = (string) ($search['mode'] ?? '');
                if ($mode === 'keyword') {
                    $ctx->keywordFields[] = $f;
                }
                if ($mode === 'advanced') {
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
            if (is_array($form) && $relation !== 'belongs_to_many') {
                $ctx->writableFields[] = $f;
            }
        }

        return $ctx;
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

    /** columnType 取 column.type */
    public static function columnType(array $field): string
    {
        $col = $field['column'] ?? null;

        return is_array($col) ? (string) ($col['type'] ?? '') : '';
    }

    /** columnConfig 取 column 对象 */
    public static function columnConfig(array $field): ?array
    {
        $col = $field['column'] ?? null;

        return is_array($col) ? $col : null;
    }

    /** relationType 取 relation.type */
    public static function relationType(array $field): string
    {
        $rel = $field['relation'] ?? null;

        return is_array($rel) ? (string) ($rel['type'] ?? '') : '';
    }

    /** relationConfig 取 relation 对象 */
    public static function relationConfig(array $field): ?array
    {
        $rel = $field['relation'] ?? null;

        return is_array($rel) ? $rel : null;
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
        $formRender = self::formRender($field);
        $mapped = self::formToSearchRender($formRender);
        if ($mapped !== '') {
            return $mapped;
        }
        $ct = self::columnType($field);

        return match ($ct) {
            'tinyint' => 'bool',
            'int', 'bigint', 'decimal' => 'number_range',
            'date' => 'date_range',
            'datetime', 'timestamp' => 'datetime_range',
            default => 'input',
        };
    }

    /**
     * formToSearchRender 表单控件 → 默认搜索控件
     * {number:number_range, date:date_range, datetime:datetime_range, switch:bool, radio:select}
     */
    public static function formToSearchRender(string $formRender): string
    {
        return match ($formRender) {
            'input' => 'input',
            'number' => 'number_range',
            'switch' => 'bool',
            'radio', 'select' => 'select',
            'remote_select' => 'remote_select',
            'remote_multi_select' => 'remote_multi_select',
            'date' => 'date_range',
            'datetime' => 'datetime_range',
            default => '',
        };
    }

    /** isPrimary 是否主键列 */
    public static function isPrimary(array $field): bool
    {
        $col = self::columnConfig($field);

        return $col !== null && !empty($col['primary']);
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

    /** isEnumField 带 statusMapKey 的枚举语义 */
    public static function isEnumField(array $field): bool
    {
        return self::statusMapKey($field) !== '';
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
     * statusMapKey 取 statusMapKey（form → list → search）
     */
    public static function statusMapKey(array $field): string
    {
        foreach ([self::formConfig($field), self::listConfig($field), self::searchConfig($field)] as $cfg) {
            if ($cfg !== null && !empty($cfg['statusMapKey'])) {
                return (string) $cfg['statusMapKey'];
            }
        }

        return '';
    }

    /**
     * optionsKey 取 optionsKey（form → search → list）
     */
    public static function optionsKey(array $field): string
    {
        foreach ([self::formConfig($field), self::searchConfig($field), self::listConfig($field)] as $cfg) {
            if ($cfg !== null && !empty($cfg['optionsKey'])) {
                return (string) $cfg['optionsKey'];
            }
        }

        return '';
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
        return $this->maps !== [] || $this->options !== [];
    }

    /**
     * parseRemoteApi 解析 optionsKey 为 Module.Entity
     *
     * @return array{module: string, entity: string, url: string}|null
     */
    public static function parseRemoteApi(?string $optionsKey): ?array
    {
        if ($optionsKey === null || $optionsKey === '') {
            return null;
        }
        if (!preg_match('/^([a-z][a-z0-9_]*)\.([A-Z][A-Za-z0-9]*)\/list$/', $optionsKey, $m)) {
            return null;
        }

        return ['module' => $m[1], 'entity' => $m[2], 'url' => $optionsKey];
    }

    /**
     * relationMethodName 关联方法名（category_id → category；tag_ids → tags）
     */
    public static function relationMethodName(array $field): string
    {
        $rp = self::relationProp($field);
        if ($rp !== '') {
            if (str_contains($rp, '.')) {
                return explode('.', $rp, 2)[0];
            }

            return $rp;
        }
        $prop = (string) $field['prop'];
        if (str_ends_with($prop, '_ids')) {
            return substr($prop, 0, -4) . 's';
        }
        if (str_ends_with($prop, '_id')) {
            return substr($prop, 0, -3);
        }

        return $prop;
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
     * toCamel snake → camel（category_id → categoryId）
     */
    public static function toCamel(string $snake): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $snake))));
    }

    /**
     * enumValues 从 maps 取枚举合法值列表
     *
     * @return list<int|string>
     */
    public function enumValues(array $field): array
    {
        $key = self::statusMapKey($field);
        if ($key === '' || !isset($this->maps[$key])) {
            return [0, 1];
        }

        return array_map(static function ($k) {
            return is_numeric($k) ? (int) $k : $k;
        }, array_keys($this->maps[$key]));
    }
}
