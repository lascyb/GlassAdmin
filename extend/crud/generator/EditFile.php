<?php
declare (strict_types = 1);

namespace crud\generator;

use crud\field\FieldMeta;
use crud\field\Naming;
use crud\Context;
use crud\Template;
use crud\generator\ui\Registry;
use crud\generator\ui\resolve\FormKey;

/**
 * EditFile 生成 edit.vue
 */
class EditFile
{
    public function render(Context $ctx): string
    {
        $constImports = $ctx->constantNames();
        $constImport = $constImports === []
            ? ''
            : 'import { ' . implode(', ', $constImports) . " } from '@/views/{$ctx->module}/{$ctx->entityFile}/constants'\n";

        $needRemote = $ctx->belongsToFields !== [] || $ctx->belongsToManyFields !== [];
        $needUpload = $ctx->imageFields !== [];
        $needStorage = $needUpload
            || Helper::hasFormRender($ctx, 'image_upload')
            || Helper::hasFormRender($ctx, 'image')
            || Helper::hasFormRender($ctx, 'file');

        $imports = [
            "import * as apis from '@/apis/{$ctx->module}/{$ctx->entityFile}'",
            "import { ElMessage } from 'element-plus'",
            "import { onMounted, reactive, ref } from 'vue'",
        ];
        if ($needRemote) {
            $imports[] = "import GaSelectRemote from '@/components/ga-select-remote/index.vue'";
        }
        if ($needUpload) {
            $imports[] = "import { Plus } from '@element-plus/icons-vue'";
            $imports[] = "import type { UploadRequestOptions } from 'element-plus'";
        }
        if ($needStorage) {
            $imports[] = "import { resolveStorageUrl } from '@/utils/storageUrl'";
        }
        sort($imports);

        $formLines = [];
        $resetLines = [];
        $assignLines = [];
        $payloadLines = [];
        $initRefs = [];
        $initAssign = [];
        $initReset = [];

        foreach ($ctx->formFields as $f) {
            $prop = (string) $f['prop'];
            $ct = FieldMeta::columnType($f);
            $relation = FieldMeta::relationType($f);
            $render = FieldMeta::formRender($f);
            $uiKey = FormKey::resolve($f);
            $def = $this->formDefault($f, $ctx);
            $formLines[] = "    {$prop}: {$def},";
            $resetLines[] = "        {$prop}: {$def},";

            if ($relation === 'belongs_to') {
                $method = Naming::relationMethodName($f);
                $initVar = Naming::toCamel($method) . 'Init';
                $rowTs = Helper::remoteRelatedRowTs($f);
                $initRefs[] = "const {$initVar} = ref<Array<{$rowTs}> | undefined>()";
                $initReset[] = "    {$initVar}.value = undefined";
                $assignLines[] = "                {$prop}: d.{$prop} ?? undefined,";
                $initAssign[] = "            {$initVar}.value = d.{$method} ? [d.{$method}] : undefined";
                $payloadLines[] = "            {$prop}: form.{$prop} ?? null,";
            } elseif ($relation === 'belongs_to_many') {
                $method = Naming::relationMethodName($f);
                $initVar = Naming::toCamel($method) . 'Init';
                $rowTs = Helper::remoteRelatedRowTs($f);
                $vf = FieldMeta::remoteValueField($f);
                $initRefs[] = "const {$initVar} = ref<Array<{$rowTs}> | undefined>()";
                $initReset[] = "    {$initVar}.value = undefined";
                $assignLines[] = "                {$prop}: d.{$prop} ?? d.{$method}?.map((t) => t.{$vf}) ?? [],";
                $initAssign[] = "            {$initVar}.value = d.{$method}?.length ? d.{$method} : undefined";
                $payloadLines[] = "            {$prop}: form.{$prop},";
            } elseif (FieldMeta::isStringColumn($f) && in_array($render, ['input', 'password', 'textarea', 'color'], true)) {
                $assignLines[] = "                {$prop}: d.{$prop} ?? '',";
                $payloadLines[] = $render === 'color'
                    ? "            {$prop}: form.{$prop},"
                    : "            {$prop}: form.{$prop}.trim(),";
            } elseif ($ct === 'date' || $render === 'date' || $uiKey === 'image_upload') {
                $assignLines[] = "                {$prop}: d.{$prop} ?? '',";
                $payloadLines[] = $render === 'date'
                    ? "            {$prop}: form.{$prop} || undefined,"
                    : "            {$prop}: form.{$prop},";
            } elseif (FieldMeta::isUnixDatetime($f)) {
                $assignLines[] = "                {$prop}: d.{$prop} ?? null,";
                $payloadLines[] = "            {$prop}: form.{$prop} ?? undefined,";
            } elseif (in_array($render, ['datetime', 'time'], true) || in_array($ct, ['datetime', 'timestamp'], true)) {
                $assignLines[] = "                {$prop}: d.{$prop} ?? '',";
                $payloadLines[] = "            {$prop}: form.{$prop} || undefined,";
            } elseif ($ct === 'json' || in_array($render, ['multi_select', 'checkbox'], true)) {
                $assignLines[] = "                {$prop}: d.{$prop} ?? [],";
                $payloadLines[] = "            {$prop}: form.{$prop},";
            } else {
                $assignLines[] = "                {$prop}: d.{$prop},";
                $payloadLines[] = "            {$prop}: form.{$prop},";
            }
        }

        $formItems = '';
        foreach ($ctx->formFields as $f) {
            $formItems .= Registry::form(FormKey::resolve($f))->render($f, $ctx);
        }

        $uploadFns = '';
        foreach ($ctx->imageFields as $f) {
            $prop = (string) $f['prop'];
            $uploadFns .= Template::render('edit/upload_fn.ts.tpl', [
                'fn' => 'onUpload' . str_replace(' ', '', ucwords(str_replace('_', ' ', $prop))),
                'uploadApi' => Naming::uploadActionName($prop),
                'prop' => $prop,
            ]);
        }

        $requiredChecks = '';
        foreach ($ctx->formFields as $f) {
            if (FieldMeta::isRequired($f) && FieldMeta::isStringColumn($f) && FieldMeta::formRender($f) === 'input') {
                $requiredChecks .= Template::render('edit/required_check.ts.tpl', [
                    'prop' => (string) $f['prop'],
                    'label' => (string) $f['label'],
                ]);
            }
        }

        return Template::render('edit/edit.vue.tpl', [
            'importBlock' => implode("\n", $imports) . "\n" . $constImport,
            'title' => $ctx->title,
            'pk' => $ctx->primaryKey(),
            'pkTs' => $ctx->isIntPrimary() ? 'number' : 'string',
            'formLines' => implode("\n", $formLines),
            'resetLines' => implode("\n", $resetLines),
            'assignLines' => implode("\n", $assignLines),
            'payloadLines' => implode("\n", $payloadLines),
            'initRefsBlock' => $initRefs === [] ? '' : implode("\n", $initRefs) . "\n",
            'detailReady' => $needRemote ? "const detailReady = ref(false)\n" : '',
            'initResetBlock' => $initReset === [] ? '' : implode("\n", $initReset) . "\n",
            'detailReadySetFalse' => $needRemote ? "    detailReady.value = false\n" : '',
            'detailReadySetTrue' => $needRemote ? "    detailReady.value = true\n" : '',
            'initAssignBlock' => $initAssign === [] ? '' : implode("\n", $initAssign) . "\n",
            'uploadFns' => $uploadFns,
            'requiredChecks' => $requiredChecks,
            'formItems' => $formItems,
        ]);
    }

    /** formDefault 表单默认值字面量 */
    private function formDefault(array $f, Context $ctx): string
    {
        $ct = FieldMeta::columnType($f);
        $relation = FieldMeta::relationType($f);
        $render = FieldMeta::formRender($f);
        if ($relation === 'belongs_to' || $render === 'remote_select') {
            return Helper::remoteValueIsString($f)
                ? 'undefined as string | undefined'
                : 'undefined as number | undefined';
        }
        if ($relation === 'belongs_to_many' || $ct === 'json' || in_array($render, ['multi_select', 'checkbox'], true)) {
            return '[] as ' . (($ct === 'json' || in_array($render, ['multi_select', 'checkbox'], true)) ? 'string[]' : 'number[]');
        }
        if ($render === 'color') {
            return "'#409EFF'";
        }
        if (FieldMeta::isBoolField($f)) {
            return '0';
        }
        if (FieldMeta::isEnumField($f)) {
            $vals = $ctx->enumValues($f);
            if ($vals === []) {
                return '0';
            }
            if (in_array(1, $vals, true)) {
                return '1';
            }

            return Helper::jsLiteral($vals[0]);
        }
        if ($ct === 'decimal' || $render === 'number' || in_array($ct, ['int', 'tinyint', 'float'], true)) {
            return '0';
        }
        if (FieldMeta::isUnixDatetime($f)) {
            return 'null as number | null';
        }
        if (in_array($render, ['datetime', 'time'], true) || in_array($ct, ['datetime', 'timestamp'], true)) {
            return "''";
        }

        return "''";
    }
}
