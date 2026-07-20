<?php
declare (strict_types = 1);

namespace crud\generator;

use crud\Context;
use crud\Template;

/**
 * EditFile 生成 edit.vue
 */
class EditFile
{
    public function render(Context $ctx): string
    {
        $constImports = array_keys($ctx->options);
        $constImport = $constImports === []
            ? ''
            : 'import { ' . implode(', ', $constImports) . " } from '@/views/{$ctx->module}/{$ctx->entityFile}/constants'\n";

        $needRemote = $ctx->belongsToFields !== [] || $ctx->belongsToManyFields !== [];
        $needUpload = $ctx->imageFields !== [];
        $needStorage = $needUpload || Helper::hasFormRender($ctx, 'image');

        $imports = [
            "import * as apis from '@/apis/{$ctx->module}/{$ctx->entityFile}'",
            "import { ElMessage } from 'element-plus'",
            "import { onMounted, reactive, ref } from 'vue'",
        ];
        if ($needRemote) {
            $imports[] = "import GaSelectRemote from '@/components/ga-select-remote/index.vue'";
        }
        if ($needUpload) {
            $imports[] = "import { postFormDataData } from '@/apis/http'";
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
            $ct = Context::columnType($f);
            $relation = Context::relationType($f);
            $render = Context::formRender($f);
            $def = $this->formDefault($f, $ctx);
            $formLines[] = "    {$prop}: {$def},";
            $resetLines[] = "        {$prop}: {$def},";

            if ($relation === 'belongs_to') {
                $method = Context::relationMethodName($f);
                $labelField = Context::remoteLabelField($f);
                $initVar = Context::toCamel($method) . 'Init';
                $initRefs[] = "const {$initVar} = ref<Array<{ id: number; {$labelField}: string }> | undefined>()";
                $initReset[] = "    {$initVar}.value = undefined";
                $assignLines[] = "                {$prop}: d.{$prop} ?? undefined,";
                $initAssign[] = "            {$initVar}.value = d.{$method} ? [d.{$method}] : undefined";
                $payloadLines[] = "            {$prop}: form.{$prop} ?? null,";
            } elseif ($relation === 'belongs_to_many') {
                $method = Context::relationMethodName($f);
                $labelField = Context::remoteLabelField($f);
                $initVar = Context::toCamel($method) . 'Init';
                $initRefs[] = "const {$initVar} = ref<Array<{ id: number; {$labelField}: string }> | undefined>()";
                $initReset[] = "    {$initVar}.value = undefined";
                $assignLines[] = "                {$prop}: d.{$prop} ?? d.{$method}?.map((t) => t.id) ?? [],";
                $initAssign[] = "            {$initVar}.value = d.{$method}?.length ? d.{$method} : undefined";
                $payloadLines[] = "            {$prop}: form.{$prop},";
            } elseif (Context::isStringColumn($f) && in_array($render, ['input', 'password', 'textarea', 'color'], true)) {
                $assignLines[] = "                {$prop}: d.{$prop} ?? '',";
                $payloadLines[] = $render === 'color'
                    ? "            {$prop}: form.{$prop},"
                    : "            {$prop}: form.{$prop}.trim(),";
            } elseif ($ct === 'date' || $render === 'date' || in_array($render, ['image', 'file'], true)) {
                $assignLines[] = "                {$prop}: d.{$prop} ?? '',";
                $payloadLines[] = $render === 'date'
                    ? "            {$prop}: form.{$prop} || undefined,"
                    : "            {$prop}: form.{$prop},";
            } elseif (Context::isUnixDatetime($f)) {
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
            $formItems .= $this->renderFormItem($f, $ctx);
        }

        $uploadFns = '';
        foreach ($ctx->imageFields as $f) {
            $prop = (string) $f['prop'];
            $uploadFns .= Template::render('edit/upload_fn.ts.tpl', [
                'fn' => 'onUpload' . str_replace(' ', '', ucwords(str_replace('_', ' ', $prop))),
                'constName' => 'UPLOAD_' . strtoupper($prop) . '_ACTION',
                'prop' => $prop,
            ]);
        }

        $requiredChecks = '';
        foreach ($ctx->formFields as $f) {
            if (Context::isRequired($f) && Context::isStringColumn($f) && Context::formRender($f) === 'input') {
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
        $ct = Context::columnType($f);
        $relation = Context::relationType($f);
        $render = Context::formRender($f);
        if ($relation === 'belongs_to') {
            return 'undefined as number | undefined';
        }
        if ($relation === 'belongs_to_many' || $ct === 'json' || in_array($render, ['multi_select', 'checkbox'], true)) {
            return '[] as ' . (($ct === 'json' || in_array($render, ['multi_select', 'checkbox'], true)) ? 'string[]' : 'number[]');
        }
        if ($render === 'color') {
            return "'#409EFF'";
        }
        if (Context::isBoolField($f)) {
            return '0';
        }
        if (Context::isEnumField($f)) {
            $vals = $ctx->enumValues($f);

            return in_array(1, $vals, true) ? '1' : (string) ($vals[0] ?? 0);
        }
        if ($ct === 'decimal' || $render === 'number' || in_array($ct, ['int', 'tinyint'], true)) {
            return '0';
        }
        if (Context::isUnixDatetime($f)) {
            return 'null as number | null';
        }
        if (in_array($render, ['datetime', 'time'], true) || in_array($ct, ['datetime', 'timestamp'], true)) {
            return "''";
        }

        return "''";
    }

    /** renderFormItem 表单项 */
    private function renderFormItem(array $f, Context $ctx): string
    {
        $prop = (string) $f['prop'];
        $label = (string) $f['label'];
        $form = Context::formConfig($f) ?? [];
        $render = (string) ($form['render'] ?? 'input');
        $required = !empty($form['required']) ? ' required' : '';
        $maxLen = Context::formMaxLength($f);
        $max = $maxLen !== null ? " maxlength=\"{$maxLen}\" show-word-limit" : '';
        $mapKey = Context::enumKey($f);
        $optKey = Context::enumKey($f);
        $remoteUrl = Context::remoteApi($f);
        $relation = Context::relationType($f);
        $base = [
            'label' => $label,
            'prop' => $prop,
            'required' => $required,
            'placeholder' => Context::formPlaceholder($f),
        ];

        if ($relation === 'belongs_to' || $relation === 'belongs_to_many'
            || in_array($render, ['remote_select', 'remote_multi_select'], true)
        ) {
            $method = Context::relationMethodName($f);
            $multi = ($relation === 'belongs_to_many' || $render === 'remote_multi_select');

            $valueField = Context::remoteValueField($f);
            $labelField = Context::remoteLabelField($f);

            return Template::render('edit/field/remote.vue.tpl', array_merge($base, [
                'key' => $multi ? 'tag' : 'rel',
                'url' => $remoteUrl,
                'valueField' => $valueField,
                'labelField' => $labelField,
                'initVar' => Context::toCamel($method) . 'Init',
                'multiAttrs' => $multi
                    ? "\n                        multiple\n                        collapse-tags\n                        collapse-tags-tooltip"
                    : '',
            ])) . "\n";
        }

        return match ($render) {
            'textarea' => Template::render('edit/field/textarea.vue.tpl', array_merge($base, ['max' => $max])) . "\n",
            'number' => $this->renderNumberItem($f),
            'switch' => Template::render('edit/field/switch.vue.tpl', $base) . "\n",
            'radio' => Template::render('edit/field/radio.vue.tpl', array_merge($base, ['optKey' => $mapKey])) . "\n",
            'select' => Template::render('edit/field/select.vue.tpl', array_merge($base, ['optKey' => $mapKey])) . "\n",
            'multi_select', 'checkbox' => Template::render('edit/field/multi_select.vue.tpl', array_merge($base, ['optKey' => $optKey])) . "\n",
            'color' => Template::render('edit/field/color.vue.tpl', $base) . "\n",
            'image', 'file' => $this->renderImageItem($f),
            'datetime', 'time' => Template::render('edit/field/datetime.vue.tpl', array_merge($base, [
                'valueFormat' => Context::isUnixDatetime($f) ? 'X' : 'YYYY-MM-DD HH:mm:ss',
            ])) . "\n",
            'date' => Template::render('edit/field/date.vue.tpl', $base) . "\n",
            default => Template::render('edit/field/input.vue.tpl', array_merge($base, ['max' => $max])) . "\n",
        };
    }

    /** renderNumberItem number 表单项 */
    private function renderNumberItem(array $f): string
    {
        $form = Context::formConfig($f) ?? [];
        $attrs = '';
        if (isset($form['numberMin'])) {
            $attrs .= ' :min="' . $form['numberMin'] . '"';
        }
        if (isset($form['numberMax'])) {
            $attrs .= ' :max="' . $form['numberMax'] . '"';
        }
        if (isset($form['numberStep'])) {
            $attrs .= ' :step="' . $form['numberStep'] . '"';
        }
        if (Context::columnType($f) === 'decimal') {
            $attrs .= ' :precision="' . Context::decimalScale($f) . '"';
        }

        return Template::render('edit/field/number.vue.tpl', [
            'label' => (string) $f['label'],
            'prop' => (string) $f['prop'],
            'required' => !empty($form['required']) ? ' required' : '',
            'attrs' => $attrs,
            'placeholder' => Context::formPlaceholder($f),
        ]) . "\n";
    }

    /** renderImageItem image 表单项 */
    private function renderImageItem(array $f): string
    {
        $prop = (string) $f['prop'];

        return Template::render('edit/field/image.vue.tpl', [
            'label' => (string) $f['label'],
            'prop' => $prop,
            'uploadFn' => 'onUpload' . str_replace(' ', '', ucwords(str_replace('_', ' ', $prop))),
        ]) . "\n";
    }
}
