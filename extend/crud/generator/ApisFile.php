<?php
declare (strict_types = 1);

namespace crud\generator;

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
            $relation = Context::relationType($f);
            $form = Context::formConfig($f);
            $ts = Helper::tsType($f, Context::isRequired($f));

            if (Context::isPrimary($f)) {
                $ts = Helper::tsType($f, true);
                $rowFields[] = "    {$prop}: {$ts}";
                $col = Context::columnConfig($f);
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
                $method = Context::relationMethodName($f);
                $rowFields[] = "    {$prop}?: number | null";
                $rowFields[] = "    {$method}?: { id: number; name: string } | null";
                $payloadFields[] = "    {$prop}?: number | null";
            } elseif ($relation === 'belongs_to_many') {
                $method = Context::relationMethodName($f);
                $rowFields[] = "    {$prop}?: number[]";
                $rowFields[] = "    {$method}?: Array<{ id: number; name: string }>";
                $payloadFields[] = "    {$prop}?: number[]";
            } else {
                $req = Context::isRequired($f);
                $rowFields[] = $req ? "    {$prop}: {$ts}" : "    {$prop}?: {$ts}";
                $payloadFields[] = $req ? "    {$prop}: {$ts}" : "    {$prop}?: {$ts}";
            }
        }

        foreach ($ctx->advancedFields as $f) {
            $prop = (string) $f['prop'];
            $sr = Context::searchRender($f);
            $relation = Context::relationType($f);
            if ($relation === 'belongs_to_many' || $sr === 'remote_multi_select') {
                $listParamFields[] = "    {$prop}?: number[]";
            } elseif ($sr === 'number_range') {
                $listParamFields[] = "    {$prop}_min?: number";
                $listParamFields[] = "    {$prop}_max?: number";
            } elseif ($sr === 'date_range') {
                $listParamFields[] = "    {$prop}_start?: string";
                $listParamFields[] = "    {$prop}_end?: string";
            } elseif ($sr === 'datetime_range') {
                $listParamFields[] = "    {$prop}_start?: number";
                $listParamFields[] = "    {$prop}_end?: number";
            } elseif ($sr === 'input') {
                $listParamFields[] = "    {$prop}?: string";
            } else {
                $listParamFields[] = "    {$prop}?: number";
            }
        }

        $uploadExports = '';
        foreach ($ctx->imageFields as $f) {
            $prop = (string) $f['prop'];
            $uploadExports .= Template::render('apis/upload_export.ts.tpl', [
                'const' => 'UPLOAD_' . strtoupper($prop) . '_ACTION',
                'label' => (string) $f['label'],
                'action' => Context::uploadActionName($prop),
            ]);
        }

        return Template::render('apis/apis.ts.tpl', [
            'entity' => $ctx->entity,
            'apiBase' => $ctx->apiBase,
            'rowBlock' => implode("\n", $rowFields),
            'payloadBlock' => implode("\n", $payloadFields),
            'listParams' => implode("\n", $listParamFields),
            'uploadExports' => $uploadExports,
            'pkTs' => $ctx->isIntPrimary() ? 'number' : 'string',
        ]);
    }
}
