<?php
declare (strict_types = 1);

namespace crud\generator;

use crud\field\FieldMeta;
use crud\field\Naming;
use crud\Context;
use crud\Template;
use crud\generator\ui\Registry;
use crud\generator\ui\resolve\FilterKey;

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
            $uses[] = 'use app\\admin\\model\\system\\Attachment;';
            $uses[] = 'use app\\admin\\model\\system\\AttachmentUploadOptions;';
            $uses[] = 'use InvalidArgumentException;';
            $uses[] = 'use RuntimeException;';
            $uses[] = 'use think\\file\\UploadedFile;';
        }
        foreach ($manyForWrite as $f) {
            $remote = Naming::parseRemoteApi(FieldMeta::remoteApi($f) ?: null);
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
            $kw = Registry::filter('keyword');
            if ($kw !== null) {
                $part = $kw->render(['_keywordExpr' => $keywordExpr], $ctx);
                foreach ($part['params'] as $line) {
                    $listParams[] = $line;
                }
                $listFilters .= $part['filter'];
            }
        }
        foreach ($ctx->advancedFields as $f) {
            $filterUi = Registry::filter(FilterKey::resolve($f));
            if ($filterUi === null) {
                continue;
            }
            $part = $filterUi->render($f, $ctx);
            foreach ($part['params'] as $line) {
                $listParams[] = $line;
            }
            $listFilters .= $part['filter'];
        }

        $listMapExtra = '';
        foreach ($listManyFields as $f) {
            $listMapExtra .= Template::render('controller/list_map_many.php.tpl', [
                'method' => Naming::relationMethodName($f),
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
            $method = Naming::relationMethodName($f);
            $sync = 'sync' . ucfirst($method);
            $idsFor = Helper::idsForMethodName((string) $f['prop']);
            $remote = Naming::parseRemoteApi(FieldMeta::remoteApi($f) ?: null);
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
            if (FieldMeta::isUnique($f)) {
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
            $ct = FieldMeta::columnType($f);
            $relation = FieldMeta::relationType($f);
            $render = FieldMeta::formRender($f);
            $fn = match (true) {
                $relation === 'belongs_to' => Helper::remotePayloadFn($f),
                FieldMeta::isBoolField($f) => "static fn (mixed \$v): int => (int) \$v === 1 ? 1 : 0",
                $ct === 'json' || $render === 'multi_select' => 'fn (mixed $v): ?array => CrudHelper::normalizeStringList($v)',
                FieldMeta::isEnumField($f) => Helper::enumPayloadFn($ctx, $f),
                $ct === 'decimal' => 'static fn (mixed $v): float => round((float) $v, ' . FieldMeta::decimalScale($f) . ')',
                $ct === 'float' => 'static fn (mixed $v): float => (float) $v',
                FieldMeta::isUnixDatetime($f) || ($ct === 'bigint' && $render === 'datetime') => 'static fn (mixed $v): ?int => ($ts = (int) $v) > 0 ? $ts : null',
                in_array($ct, ['bigint', 'int', 'tinyint'], true) || $render === 'number' => 'static fn (mixed $v): int => (int) $v',
                default => 'fn (mixed $v): ?string => CrudHelper::nullableString($v)',
            };
            if (FieldMeta::isRequired($f) && FieldMeta::isStringColumn($f) && $render === 'input') {
                $fn = 'static fn (mixed $v): string => trim((string) $v)';
            }
            $payloadLines[] = "            '{$prop}' => {$fn},";
        }

        $uploadMethods = '';
        foreach ($ctx->imageFields as $f) {
            $render = FieldMeta::formRender($f);
            $action = Naming::uploadActionName((string) $f['prop']);
            $uploadMethods .= "\n" . Template::render('controller/upload.php.tpl', [
                'action'  => $action,
                'label'   => (string) $f['label'],
                'dir'     => $ctx->module !== '' ? $ctx->module : 'default',
                'factory' => $render === 'file' ? 'default' : 'image', // file 走通用上传；image/image_upload 走图片
                'api'     => $ctx->apiBase . '/' . $action,
            ]);
        }

        $uploadConst = '';

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
            $remote = Naming::parseRemoteApi(FieldMeta::remoteApi($f) ?: null);
            $relatedModel = ($remote['entity'] ?? 'Related') . 'Model';
            $createManyBefore .= Template::render('controller/create_many_before.php.tpl', [
                'prop' => $prop,
                'relatedModel' => $relatedModel,
            ]);
            $createManyAfter .= Template::render('controller/create_many_after.php.tpl', [
                'modelAlias' => $modelAlias,
                'sync' => 'sync' . ucfirst(Naming::relationMethodName($f)),
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
            if (FieldMeta::relationType($f) === 'belongs_to_many') {
                continue;
            }
            // belongs_to 有外键列；其余须 column.enabled
            if (FieldMeta::relationType($f) === 'belongs_to') {
                $props[] = $prop;
                continue;
            }
            if (FieldMeta::columnConfig($f) === null) {
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
            $rel = FieldMeta::relationType($f);
            if ($rel !== 'belongs_to' && $rel !== 'belongs_to_many') {
                continue;
            }
            $vf = FieldMeta::remoteValueField($f);
            $lf = FieldMeta::remoteLabelField($f);
            $fields = $vf === $lf ? $vf : "{$vf},{$lf}";
            $parts[] = rtrim(Template::render('controller/with_closure.php.tpl', [
                'method' => Naming::relationMethodName($f),
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
            if (FieldMeta::relationType($f) === $type) {
                $out[] = $f;
            }
        }

        return $out;
    }
}
