<?php
declare (strict_types = 1);

namespace crud\generator;

use crud\Context;
use crud\Template;

/**
 * ControllerFile 生成 Controller.php
 */
class ControllerFile
{
    /** render 渲染 Controller 内容 */
    public function render(Context $ctx): string
    {
        $modelAlias = $ctx->entity . 'Model';
        $listManyFields = $this->relationFieldsIn($ctx->listFields, 'belongs_to_many');
        $formManyFields = $this->relationFieldsIn($ctx->formFields, 'belongs_to_many');
        $manyForWrite = $formManyFields;

        $uses = [
            "use {$ctx->nsModel}\\{$ctx->entity} as {$modelAlias};",
            'use app\\common\\controller\\AdminController;',
            'use app\\common\\helper\\CrudHelper;',
            'use think\\Request;',
            'use think\\Response;',
        ];
        if ($ctx->imageFields !== []) {
            $uses[] = 'use Symfony\\Component\\Filesystem\\Exception\\FileException;';
            $uses[] = 'use think\\file\\UploadedFile;';
        }
        foreach ($manyForWrite as $f) {
            $remote = Context::parseRemoteApi(Context::remoteApi($f) ?: null);
            if ($remote) {
                $remoteNs = Helper::modelNamespace($remote['module']);
                $uses[] = "use {$remoteNs}\\{$remote['entity']} as {$remote['entity']}Model;";
            }
        }
        $uses = array_unique($uses);
        sort($uses);

        $listWithBlock = $this->withBlock($ctx->listFields);
        $readWithBlock = $this->withBlock($this->relationFieldsIn($ctx->formFields, 'belongs_to'));

        $keywordProps = array_map(static fn ($f) => (string) $f['prop'], $ctx->keywordSourceFields());
        $keywordExpr = $keywordProps === [] ? '' : implode('|', $keywordProps);

        $listParams = [];
        $listFilters = '';
        if ($keywordExpr !== '') {
            $listParams[] = "        \$keyword = trim((string) \$request->get('keyword', ''));";
            $listFilters .= Template::render('controller/filter/keyword.php.tpl', [
                'keywordExpr' => $keywordExpr,
            ]);
        }
        foreach ($ctx->advancedFields as $f) {
            $prop = (string) $f['prop'];
            $relation = Context::relationType($f);
            $sr = Context::searchRender($f);
            $var = '$' . Context::toCamel($prop);
            if ($relation === 'belongs_to_many' || $sr === 'remote_multi_select') {
                $listParams[] = "        {$var} = CrudHelper::parseIdList(\$request->get('{$prop}'));";
                $rel = Context::relationConfig($f) ?? [];
                $listFilters .= Template::render('controller/filter/belongs_to_many.php.tpl', [
                    'var' => $var,
                    'pivot' => (string) ($rel['pivotTable'] ?? ''),
                    'fk' => (string) ($rel['foreignKey'] ?? ''),
                    'rk' => (string) ($rel['relatedKey'] ?? ''),
                    'pk' => $ctx->primaryKey(),
                ]);
            } elseif ($sr === 'number_range') {
                $minVar = '$' . Context::toCamel($prop) . 'Min';
                $maxVar = '$' . Context::toCamel($prop) . 'Max';
                $listParams[] = "        {$minVar} = \$request->get('{$prop}_min');";
                $listParams[] = "        {$maxVar} = \$request->get('{$prop}_max');";
                $listFilters .= Template::render('controller/filter/number_range.php.tpl', [
                    'prop' => $prop,
                    'minVar' => $minVar,
                    'maxVar' => $maxVar,
                ]);
            } elseif ($sr === 'date_range' || $sr === 'datetime_range') {
                $startVar = '$' . Context::toCamel($prop) . 'Start';
                $endVar = '$' . Context::toCamel($prop) . 'End';
                $listParams[] = "        {$startVar} = \$request->get('{$prop}_start');";
                $listParams[] = "        {$endVar} = \$request->get('{$prop}_end');";
                // Unix 秒走 int 比较；date / MySQL datetime 走字符串比较
                $rangeTpl = Context::isUnixDatetime($f)
                    ? 'controller/filter/datetime_range.php.tpl'
                    : 'controller/filter/date_range.php.tpl';
                $listFilters .= Template::render($rangeTpl, [
                    'prop' => $prop,
                    'startVar' => $startVar,
                    'endVar' => $endVar,
                ]);
            } elseif ($relation === 'belongs_to' || $sr === 'remote_select') {
                if (Helper::remoteValueIsString($f)) {
                    $listParams[] = "        {$var} = trim((string) \$request->get('{$prop}', ''));";
                    $check = "{$var} !== ''";
                } else {
                    $listParams[] = "        {$var} = (int) \$request->get('{$prop}', 0);";
                    $check = "{$var} > 0";
                }
                $listFilters .= Template::render('controller/filter/remote_select.php.tpl', [
                    'prop' => $prop,
                    'var' => $var,
                    'check' => $check,
                ]);
            } elseif ($sr === 'input') {
                $listParams[] = "        {$var} = trim((string) \$request->get('{$prop}', ''));";
                $listFilters .= Template::render('controller/filter/input.php.tpl', [
                    'prop' => $prop,
                    'var' => $var,
                ]);
            } elseif ($sr === 'bool' || $sr === 'select' || $sr === 'radio' || Context::isBoolField($f) || Context::isEnumField($f)) {
                $raw = '$' . Context::toCamel($prop) . 'Raw';
                $listParams[] = "        {$raw} = \$request->get('{$prop}');";
                $vals = ($sr === 'bool' || Context::isBoolField($f)) ? [0, 1] : $ctx->enumValues($f);
                $valsPhp = Helper::phpLiteralList($vals);
                if ($valsPhp === '') {
                    $listFilters .= Template::render('controller/filter/enum_bool_any.php.tpl', [
                        'prop' => $prop,
                        'raw' => $raw,
                    ]);
                } else {
                    $castExpr = Helper::enumIsString($vals)
                        ? '(string) ' . $raw
                        : '(int) ' . $raw;
                    $listFilters .= Template::render('controller/filter/enum_bool.php.tpl', [
                        'prop' => $prop,
                        'raw' => $raw,
                        'valsPhp' => $valsPhp,
                        'castExpr' => $castExpr,
                    ]);
                }
            }
        }

        $listMapExtra = '';
        foreach ($listManyFields as $f) {
            $listMapExtra .= Template::render('controller/list_map_many.php.tpl', [
                'method' => Context::relationMethodName($f),
                'prop' => (string) $f['prop'],
            ]);
        }

        $pk = $ctx->primaryKey();
        $order = "['{$pk}' => 'desc']";
        foreach ($ctx->fields as $f) {
            if (($f['prop'] ?? '') === 'sort') {
                $order = "['sort' => 'asc', '{$pk}' => 'desc']";
                break;
            }
        }

        $isIntPk = $ctx->isIntPrimary();
        if ($isIntPk) {
            $pkParse = "\$id = (int) \$request->get('id', 0);";
            $pkInvalid = "        if (\$id <= 0) {\n            return \$this->fail(400, 'id 无效');\n        }";
            $pkGetAttr = "(int) \$m->getAttr('{$pk}')";
        } else {
            $pkParse = "\$id = trim((string) \$request->get('id', ''));";
            $pkInvalid = "        if (\$id === '') {\n            return \$this->fail(400, 'id 无效');\n        }";
            $pkGetAttr = "(string) \$m->getAttr('{$pk}')";
        }

        $manyUpdate = '';
        $manyDelete = '';
        $manyRead = '';
        foreach ($manyForWrite as $f) {
            $prop = (string) $f['prop'];
            $method = Context::relationMethodName($f);
            $sync = 'sync' . ucfirst($method);
            $idsFor = Helper::idsForMethodName((string) $f['prop']);
            $remote = Context::parseRemoteApi(Context::remoteApi($f) ?: null);
            $relatedModel = ($remote['entity'] ?? 'Related') . 'Model';
            $manyUpdate .= Template::render('controller/many_update.php.tpl', [
                'prop' => $prop,
                'relatedModel' => $relatedModel,
                'modelAlias' => $modelAlias,
                'sync' => $sync,
            ]);
            $manyDelete .= Template::render('controller/many_delete.php.tpl', [
                'modelAlias' => $modelAlias,
                'sync' => $sync,
            ]);
            $manyRead .= Template::render('controller/many_read.php.tpl', [
                'prop' => $prop,
                'modelAlias' => $modelAlias,
                'idsFor' => $idsFor,
            ]);
        }

        $uniqueUpdate = '';
        foreach ($ctx->fields as $f) {
            if (Context::isUnique($f)) {
                $uniqueUpdate = Template::render('controller/unique_update.php.tpl', [
                    'prop' => (string) $f['prop'],
                    'modelAlias' => $modelAlias,
                    'label' => (string) $f['label'],
                    'pk' => $pk,
                ]);
                break;
            }
        }

        $payloadLines = [];
        foreach ($ctx->writableFields as $f) {
            $prop = (string) $f['prop'];
            $ct = Context::columnType($f);
            $relation = Context::relationType($f);
            $render = Context::formRender($f);
            $fn = match (true) {
                $relation === 'belongs_to' => Helper::remotePayloadFn($f),
                Context::isBoolField($f) => "static fn (mixed \$v): int => (int) \$v === 1 ? 1 : 0",
                $ct === 'json' || $render === 'multi_select' => 'fn (mixed $v): ?array => CrudHelper::normalizeStringList($v)',
                Context::isEnumField($f) => Helper::enumPayloadFn($ctx, $f),
                $ct === 'decimal' => 'static fn (mixed $v): float => round((float) $v, ' . Context::decimalScale($f) . ')',
                Context::isUnixDatetime($f) || ($ct === 'bigint' && $render === 'datetime') => 'static fn (mixed $v): ?int => ($ts = (int) $v) > 0 ? $ts : null',
                in_array($ct, ['bigint', 'int', 'tinyint'], true) || $render === 'number' => 'static fn (mixed $v): int => (int) $v',
                default => 'fn (mixed $v): ?string => CrudHelper::nullableString($v)',
            };
            if (Context::isRequired($f) && Context::isStringColumn($f) && $render === 'input') {
                $fn = 'static fn (mixed $v): string => trim((string) $v)';
            }
            $payloadLines[] = "            '{$prop}' => {$fn},";
        }

        $uploadMethods = '';
        foreach ($ctx->imageFields as $f) {
            $uploadMethods .= "\n" . Template::render('controller/upload.php.tpl', [
                'action' => Context::uploadActionName((string) $f['prop']),
                'label' => (string) $f['label'],
                'dir' => $ctx->module,
            ]);
        }

        $uploadConst = $ctx->imageFields !== []
            ? Template::render('controller/upload_const.php.tpl')
            : '';

        $listFieldExpr = $this->fieldExpr($ctx, $ctx->listFields);
        $readFieldExpr = $this->fieldExpr($ctx, $ctx->formFields);

        $listQuery = $listWithBlock === ''
            ? "{$modelAlias}::field('{$listFieldExpr}')->order({$order})"
            : "{$modelAlias}::field('{$listFieldExpr}')->with([{$listWithBlock}])->order({$order})";
        $readQuery = $readWithBlock === ''
            ? "{$modelAlias}::field('{$readFieldExpr}')->find(\$id)"
            : "{$modelAlias}::field('{$readFieldExpr}')->with([{$readWithBlock}])->find(\$id)";
        $listMapFn = rtrim($listMapExtra === ''
            ? Template::render('controller/list_map_plain.php.tpl', ['modelAlias' => $modelAlias])
            : Template::render('controller/list_map_extra.php.tpl', [
                'modelAlias' => $modelAlias,
                'listMapExtra' => rtrim($listMapExtra),
            ]));

        $createManyBefore = '';
        $createManyAfter = '';
        foreach ($manyForWrite as $f) {
            $prop = (string) $f['prop'];
            $remote = Context::parseRemoteApi(Context::remoteApi($f) ?: null);
            $relatedModel = ($remote['entity'] ?? 'Related') . 'Model';
            $createManyBefore .= Template::render('controller/create_many_before.php.tpl', [
                'prop' => $prop,
                'relatedModel' => $relatedModel,
            ]);
            $createManyAfter .= Template::render('controller/create_many_after.php.tpl', [
                'modelAlias' => $modelAlias,
                'sync' => 'sync' . ucfirst(Context::relationMethodName($f)),
                'prop' => $prop,
            ]);
        }

        $autoFillOnCreate = '';
        foreach ($ctx->fields as $f) {
            if ((string) ($f['uiType'] ?? '') !== 'uuid') {
                continue;
            }
            $prop = (string) ($f['prop'] ?? '');
            if ($prop === '') {
                continue;
            }
            $autoFillOnCreate .= "        if (!array_key_exists('{$prop}', \$payload) || \$payload['{$prop}'] === null || \$payload['{$prop}'] === '') {\n"
                . "            \$payload['{$prop}'] = CrudHelper::newUuid();\n"
                . "        }\n";
        }
        if ($autoFillOnCreate !== '') {
            $autoFillOnCreate = "\n" . $autoFillOnCreate;
        }

        return Template::render('controller/controller.php.tpl', [
            'nsController' => $ctx->nsController,
            'nsValidate' => $ctx->nsValidate,
            'useBlock' => implode("\n", $uses),
            'entity' => $ctx->entity,
            'title' => $ctx->title,
            'modelAlias' => $modelAlias,
            'uploadConst' => $uploadConst,
            'listParamsBlock' => implode("\n", $listParams),
            'listQuery' => $listQuery,
            'listFilters' => $listFilters,
            'listMapFn' => $listMapFn,
            'readQuery' => $readQuery,
            'manyRead' => $manyRead,
            'createManyBefore' => $createManyBefore,
            'createManyAfter' => $createManyAfter,
            'uniqueUpdate' => $uniqueUpdate,
            'manyUpdate' => $manyUpdate,
            'manyDelete' => $manyDelete,
            'uploadMethods' => $uploadMethods,
            'payloadMap' => implode("\n", $payloadLines),
            'autoFillOnCreate' => $autoFillOnCreate,
            'pk' => $pk,
            'pkParse' => $pkParse,
            'pkInvalid' => $pkInvalid,
            'pkGetAttr' => $pkGetAttr,
        ]);
    }

    /**
     * fieldExpr 查询列白名单（主键始终包含；跳过无物理列的 belongs_to_many）
     *
     * @param list<array<string, mixed>> $fields
     */
    private function fieldExpr(Context $ctx, array $fields): string
    {
        $pk = $ctx->primaryKey();
        $props = [$pk];
        foreach ($fields as $f) {
            $prop = (string) ($f['prop'] ?? '');
            if ($prop === '' || $prop === $pk) {
                continue;
            }
            if (Context::relationType($f) === 'belongs_to_many') {
                continue;
            }
            // belongs_to 有外键列；其余须 column.enabled
            if (Context::relationType($f) === 'belongs_to') {
                $props[] = $prop;
                continue;
            }
            if (Context::columnConfig($f) === null) {
                continue;
            }
            $props[] = $prop;
        }

        return implode(',', array_values(array_unique($props)));
    }

    /**
     * withBlock 为启用字段生成关联预加载
     *
     * @param list<array<string, mixed>> $fields
     */
    private function withBlock(array $fields): string
    {
        $parts = [];
        foreach ($fields as $f) {
            $rel = Context::relationType($f);
            if ($rel !== 'belongs_to' && $rel !== 'belongs_to_many') {
                continue;
            }
            $vf = Context::remoteValueField($f);
            $lf = Context::remoteLabelField($f);
            $fields = $vf === $lf ? $vf : "{$vf},{$lf}";
            $parts[] = rtrim(Template::render('controller/with_closure.php.tpl', [
                'method' => Context::relationMethodName($f),
                'fields' => $fields,
            ]));
        }

        return $parts === [] ? '' : "\n" . implode("\n", $parts) . "\n        ";
    }

    /**
     * relationFieldsIn 从字段桶中筛选指定关联类型
     *
     * @param list<array<string, mixed>> $fields
     * @return list<array<string, mixed>>
     */
    private function relationFieldsIn(array $fields, string $type): array
    {
        $out = [];
        foreach ($fields as $f) {
            if (Context::relationType($f) === $type) {
                $out[] = $f;
            }
        }

        return $out;
    }
}
