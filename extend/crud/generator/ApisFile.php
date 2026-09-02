<?php
declare (strict_types = 1);

namespace crud\generator;

use crud\field\FieldMeta;
use crud\field\Naming;
use crud\Context;
use crud\Template;

/**
 * ApisFile 生成 apis/*.ts
 */
class ApisFile
{
    /** render 渲染 apis 内容 */
    public function render(Context $ctx): string
    {
        $rowFields = [];
        $payloadFields = [];
        $listParamFields = ['    page?: number', '    limit?: number'];
        if ($ctx->keywordSourceFields() !== []) {
            $listParamFields[] = '    keyword?: string';
        }

        foreach ($ctx->fields as $f) {
            $prop = (string) $f['prop'];
            $relation = FieldMeta::relationType($f);
            $form = FieldMeta::formConfig($f);
            $ts = Helper::tsType($f, FieldMeta::isRequired($f), $ctx);

            if (FieldMeta::isPrimary($f)) {
                $ts = Helper::tsType($f, true, $ctx);
                $rowFields[] = "    {$prop}: {$ts}";
                $col = FieldMeta::columnConfig($f);
                $autoInc = !empty($col['autoIncrement']);
                if (!$autoInc && is_array($form) && ($form['enabled'] ?? true) !== false) {
                    $payloadFields[] = "    {$prop}: {$ts}";
                }
                continue;
            }
            if ($form === null) {
                // 不进表单：只读行字段
                $rowFields[] = "    {$prop}?: {$ts}";
                continue;
            }
            if ($relation === 'belongs_to') {
                $method = Naming::relationMethodName($f);
                $rowTs = Helper::remoteRelatedRowTs($f);
                $valTs = Helper::remoteValueTsType($f);
                $rowFields[] = "    {$prop}?: {$valTs}";
                $rowFields[] = "    {$method}?: {$rowTs} | null";
                $payloadFields[] = "    {$prop}?: {$valTs}";
            } elseif ($relation === 'belongs_to_many') {
                $method = Naming::relationMethodName($f);
                $rowTs = Helper::remoteRelatedRowTs($f);
                $rowFields[] = "    {$prop}?: number[]";
                $rowFields[] = "    {$method}?: Array<{$rowTs}>";
                $payloadFields[] = "    {$prop}?: number[]";
            } else {
                $req = FieldMeta::isRequired($f);
                $rowFields[] = $req ? "    {$prop}: {$ts}" : "    {$prop}?: {$ts}";
                $payloadFields[] = $req ? "    {$prop}: {$ts}" : "    {$prop}?: {$ts}";
            }
        }

        foreach ($ctx->advancedFields as $f) {
            $prop = (string) $f['prop'];
            $sr = FieldMeta::searchRender($f);
            $relation = FieldMeta::relationType($f);
            if ($relation === 'belongs_to_many' || $sr === 'remote_multi_select') {
                $listParamFields[] = "    {$prop}?: number[]";
            } elseif ($sr === 'number_range') {
                $listParamFields[] = "    {$prop}_min?: number";
                $listParamFields[] = "    {$prop}_max?: number";
            } elseif ($sr === 'date_range') {
                $listParamFields[] = "    {$prop}_start?: string";
                $listParamFields[] = "    {$prop}_end?: string";
            } elseif ($sr === 'datetime_range') {
                $rangeTs = FieldMeta::isUnixDatetime($f) ? 'number' : 'string';
                $listParamFields[] = "    {$prop}_start?: {$rangeTs}";
                $listParamFields[] = "    {$prop}_end?: {$rangeTs}";
            } elseif ($sr === 'input') {
                $listParamFields[] = "    {$prop}?: string";
            } elseif ($relation === 'belongs_to' || $sr === 'remote_select') {
                $listParamFields[] = Helper::remoteValueIsString($f)
                    ? "    {$prop}?: string"
                    : "    {$prop}?: number";
            } elseif ($sr === 'select' || $sr === 'radio' || FieldMeta::isEnumField($f)) {
                $vals = $ctx->enumValues($f);
                $listParamFields[] = Helper::enumIsString($vals)
                    ? "    {$prop}?: string"
                    : "    {$prop}?: number";
            } else {
                $listParamFields[] = "    {$prop}?: number";
            }
        }

        $uploadExports = '';
        foreach ($ctx->imageFields as $f) {
            $prop = (string) $f['prop'];
            $uploadExports .= Template::render('apis/upload_export.ts.tpl', [
                'fn' => Naming::uploadActionName($prop),
                'label' => (string) $f['label'],
            ]);
        }

        return Template::render('apis/apis.ts.tpl', [
            'entity' => $ctx->entity,
            'apiBase' => Helper::adminHttpUrl($ctx->apiBase),
            'rowBlock' => implode("\n", $rowFields),
            'payloadBlock' => implode("\n", $payloadFields),
            'listParams' => implode("\n", $listParamFields),
            'uploadExports' => $uploadExports,
            'pkTs' => $ctx->isIntPrimary() ? 'number' : 'string',
        ]);
    }
}
