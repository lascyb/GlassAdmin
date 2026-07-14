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
        $uses = [
            "use {$ctx->nsModel}\\{$ctx->entity} as {$modelAlias};",
            'use app\\common\\controller\\AdminController;',
            'use think\\Request;',
            'use think\\Response;',
        ];
        if ($ctx->imageFields !== []) {
            $uses[] = 'use Symfony\\Component\\Filesystem\\Exception\\FileException;';
            $uses[] = 'use think\\file\\UploadedFile;';
        }
        foreach ($ctx->belongsToManyFields as $f) {
            $remote = Context::parseRemoteApi(Context::optionsKey($f) ?: null);
            if ($remote) {
                $remoteNs = Helper::modelNamespace($remote['module']);
                $uses[] = "use {$remoteNs}\\{$remote['entity']} as {$remote['entity']}Model;";
            }
        }
        $uses = array_unique($uses);
        sort($uses);

        $withParts = [];
        foreach (array_merge($ctx->belongsToFields, $ctx->belongsToManyFields) as $f) {
            $withParts[] = rtrim(Template::render('controller/with_closure.php.tpl', [
                'method' => Context::relationMethodName($f),
            ]));
        }
        $withBlock = $withParts === [] ? '' : "\n" . implode("\n", $withParts) . "\n        ";

        $keywordProps = array_map(static fn ($f) => (string) $f['prop'], $ctx->keywordFields);
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
                $listParams[] = "        {$var} = \$this->parseIdList(\$request->get('{$prop}'));";
                $rel = Context::relationConfig($f) ?? [];
                $listFilters .= Template::render('controller/filter/belongs_to_many.php.tpl', [
                    'var' => $var,
                    'pivot' => (string) ($rel['pivotTable'] ?? ''),
                    'fk' => (string) ($rel['foreignKey'] ?? ''),
                    'rk' => (string) ($rel['relatedKey'] ?? ''),
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
                $rangeTpl = ($sr === 'datetime_range' || Context::isUnixDatetime($f))
                    ? 'controller/filter/datetime_range.php.tpl'
                    : 'controller/filter/date_range.php.tpl';
                $listFilters .= Template::render($rangeTpl, [
                    'prop' => $prop,
                    'startVar' => $startVar,
                    'endVar' => $endVar,
                ]);
            } elseif ($relation === 'belongs_to' || $sr === 'remote_select') {
                $listParams[] = "        {$var} = (int) \$request->get('{$prop}', 0);";
                $listFilters .= Template::render('controller/filter/remote_select.php.tpl', [
                    'prop' => $prop,
                    'var' => $var,
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
                $valsPhp = implode(', ', $vals);
                if ($valsPhp === '') {
                    $listFilters .= Template::render('controller/filter/enum_bool_any.php.tpl', [
                        'prop' => $prop,
                        'raw' => $raw,
                    ]);
                } else {
                    $listFilters .= Template::render('controller/filter/enum_bool.php.tpl', [
                        'prop' => $prop,
                        'raw' => $raw,
                        'valsPhp' => $valsPhp,
                    ]);
                }
            }
        }

        $listMapExtra = '';
        foreach ($ctx->belongsToManyFields as $f) {
            $listMapExtra .= Template::render('controller/list_map_many.php.tpl', [
                'method' => Context::relationMethodName($f),
                'prop' => (string) $f['prop'],
            ]);
        }

        $order = "['id' => 'desc']";
        foreach ($ctx->fields as $f) {
            if (($f['prop'] ?? '') === 'sort') {
                $order = "['sort' => 'asc', 'id' => 'desc']";
                break;
            }
        }

        $manyUpdate = '';
        $manyDelete = '';
        $manyRead = '';
        foreach ($ctx->belongsToManyFields as $f) {
            $prop = (string) $f['prop'];
            $method = Context::relationMethodName($f);
            $sync = 'sync' . ucfirst($method);
            $idsFor = Helper::idsForMethodName((string) $f['prop']);
            $remote = Context::parseRemoteApi(Context::optionsKey($f) ?: null);
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

        $manyHelpers = $ctx->belongsToManyFields !== []
            ? "\n" . Template::render('controller/many_helpers.php.tpl')
            : '';

        $uniqueUpdate = '';
        foreach ($ctx->fields as $f) {
            if (Context::isUnique($f)) {
                $uniqueUpdate = Template::render('controller/unique_update.php.tpl', [
                    'prop' => (string) $f['prop'],
                    'modelAlias' => $modelAlias,
                    'label' => (string) $f['label'],
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
                $relation === 'belongs_to' => "static fn (mixed \$v): ?int => (\$id = (int) \$v) > 0 ? \$id : null",
                Context::isBoolField($f) => "static fn (mixed \$v): int => (int) \$v === 1 ? 1 : 0",
                Context::isEnumField($f) => Helper::enumPayloadFn($ctx, $f),
                $ct === 'decimal' => 'static fn (mixed $v): float => round((float) $v, ' . Context::decimalScale($f) . ')',
                Context::isUnixDatetime($f) || ($ct === 'bigint' && $render === 'datetime') => 'static fn (mixed $v): ?int => ($ts = (int) $v) > 0 ? $ts : null',
                in_array($ct, ['bigint', 'int', 'tinyint'], true) || $render === 'number' => 'static fn (mixed $v): int => (int) $v',
                $ct === 'json' || $render === 'multi_select' => 'fn (mixed $v): ?array => $this->normalizeStringList($v)',
                default => 'fn (mixed $v): ?string => $this->nullableString($v)',
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

        $listQuery = $withBlock === ''
            ? "{$modelAlias}::order({$order})"
            : "{$modelAlias}::with([{$withBlock}])->order({$order})";
        $readQuery = $withBlock === ''
            ? "{$modelAlias}::find(\$id)"
            : "{$modelAlias}::with([{$withBlock}])->find(\$id)";
        $listMapFn = rtrim($listMapExtra === ''
            ? Template::render('controller/list_map_plain.php.tpl', ['modelAlias' => $modelAlias])
            : Template::render('controller/list_map_extra.php.tpl', [
                'modelAlias' => $modelAlias,
                'listMapExtra' => rtrim($listMapExtra),
            ]));

        $createManyBefore = '';
        $createManyAfter = '';
        foreach ($ctx->belongsToManyFields as $f) {
            $prop = (string) $f['prop'];
            $remote = Context::parseRemoteApi(Context::optionsKey($f) ?: null);
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

        $jsonHelper = '';
        foreach ($ctx->writableFields as $f) {
            if (Context::columnType($f) === 'json' || Context::formRender($f) === 'multi_select') {
                $jsonHelper = "\n" . Template::render('controller/json_helper.php.tpl');
                break;
            }
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
            'manyHelpers' => $manyHelpers,
            'jsonHelper' => $jsonHelper,
        ]);
    }
}
