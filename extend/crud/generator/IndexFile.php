<?php
declare (strict_types = 1);

namespace crud\generator;

use crud\Context;
use crud\Template;

/**
 * IndexFile 生成 index.vue
 */
class IndexFile
{
    public function render(Context $ctx): string
    {
        $constImports = array_keys($ctx->options);
        $constImport = $constImports === []
            ? ''
            : 'import { ' . implode(', ', $constImports) . " } from '@/views/{$ctx->module}/{$ctx->entityFile}/constants'\n";

        $needRemote = $ctx->belongsToFields !== [] || $ctx->belongsToManyFields !== [];
        $needStatus = false;
        $needFormat = false;
        $needStorage = false;
        foreach ($ctx->listFields as $f) {
            $lr = Context::listRender($f);
            $list = Context::listConfig($f) ?? [];
            if ($lr === 'status' || ($lr === 'tag' && Context::enumKey($f) !== '')) {
                $needStatus = true;
            }
            if ($lr === 'datetime' && Context::isUnixDatetime($f)) {
                $needFormat = true;
            }
            if ($lr === 'image') {
                $needStorage = true;
            }
        }

        $keywordSources = $ctx->keywordSourceFields();
        $vueImports = $keywordSources !== []
            ? "import { computed, reactive, ref } from 'vue'"
            : "import { computed, reactive } from 'vue'";

        $imports = [
            "import type { {$ctx->entity}Row } from '@/apis/{$ctx->module}/{$ctx->entityFile}'",
            "import * as entityApi from '@/apis/{$ctx->module}/{$ctx->entityFile}'",
            "import GaSearchBar from '@/components/ga-search-bar/index.vue'",
            "import GaTablePage from '@/components/ga-table-page/index.vue'",
            "import { useConfirmAction } from '@/composables/useConfirmAction'",
            "import { usePaginatedList } from '@/composables/usePaginatedList'",
            "import EditView from '@/views/{$ctx->module}/{$ctx->entityFile}/edit.vue'",
            "import { Delete, Edit, Plus } from '@element-plus/icons-vue'",
            $vueImports,
        ];
        if ($needRemote) {
            $imports[] = "import GaSelectRemote from '@/components/ga-select-remote/index.vue'";
        }
        if ($needStatus) {
            $imports[] = "import GaStatusTag from '@/components/ga-status-tag/index.vue'";
        }
        if ($needFormat) {
            $imports[] = "import { formatUnixTime } from '@/utils/formatUnixTime'";
        }
        if ($needStorage) {
            $imports[] = "import { resolveStorageUrl } from '@/utils/storageUrl'";
        }
        sort($imports);

        $advLines = [];
        $resetLines = [];
        $queryLines = [];
        if ($keywordSources !== []) {
            $queryLines[] = '    keyword: keyword.value.trim() || undefined,';
        }
        foreach ($ctx->advancedFields as $f) {
            $prop = (string) $f['prop'];
            $relation = Context::relationType($f);
            $sr = Context::searchRender($f);
            if ($relation === 'belongs_to_many' || $sr === 'remote_multi_select') {
                $advLines[] = "    {$prop}: [] as number[],";
                $resetLines[] = "    advanced.{$prop} = []";
                $queryLines[] = "    {$prop}: advanced.{$prop}.length > 0 ? advanced.{$prop} : undefined,";
            } elseif ($sr === 'number_range') {
                $advLines[] = "    {$prop}_min: undefined as number | undefined,";
                $advLines[] = "    {$prop}_max: undefined as number | undefined,";
                $resetLines[] = "    advanced.{$prop}_min = undefined";
                $resetLines[] = "    advanced.{$prop}_max = undefined";
                $queryLines[] = "    {$prop}_min: advanced.{$prop}_min,";
                $queryLines[] = "    {$prop}_max: advanced.{$prop}_max,";
            } elseif ($sr === 'date_range') {
                $advLines[] = "    {$prop}: null as [string, string] | null,";
                $resetLines[] = "    advanced.{$prop} = null";
                $queryLines[] = "    {$prop}_start: advanced.{$prop}?.[0] || undefined,";
                $queryLines[] = "    {$prop}_end: advanced.{$prop}?.[1] || undefined,";
            } elseif ($sr === 'datetime_range') {
                // Unix 秒用 number；MySQL datetime/timestamp 用字符串
                $advLines[] = Context::isUnixDatetime($f)
                    ? "    {$prop}: null as [number, number] | null,"
                    : "    {$prop}: null as [string, string] | null,";
                $resetLines[] = "    advanced.{$prop} = null";
                $queryLines[] = "    {$prop}_start: advanced.{$prop}?.[0] || undefined,";
                $queryLines[] = "    {$prop}_end: advanced.{$prop}?.[1] || undefined,";
            } elseif ($relation === 'belongs_to' || $sr === 'remote_select') {
                $advLines[] = "    {$prop}: undefined as number | undefined,";
                $resetLines[] = "    advanced.{$prop} = undefined";
                $queryLines[] = "    {$prop}: advanced.{$prop},";
            } elseif ($sr === 'input') {
                $advLines[] = "    {$prop}: '',";
                $resetLines[] = "    advanced.{$prop} = ''";
                $queryLines[] = "    {$prop}: advanced.{$prop}.trim() || undefined,";
            } elseif ($sr === 'bool' || Context::isBoolField($f)) {
                $advLines[] = "    {$prop}: undefined as number | undefined,";
                $resetLines[] = "    advanced.{$prop} = undefined";
                $queryLines[] = "    {$prop}: advanced.{$prop} === 0 || advanced.{$prop} === 1 ? advanced.{$prop} : undefined,";
            } elseif ($sr === 'select' || $sr === 'radio' || Context::isEnumField($f)) {
                $vals = $ctx->enumValues($f);
                $valsJs = implode(', ', $vals);
                $advLines[] = "    {$prop}: undefined as number | undefined,";
                $resetLines[] = "    advanced.{$prop} = undefined";
                $queryLines[] = $vals === []
                    ? "    {$prop}: advanced.{$prop},"
                    : "    {$prop}: [{$valsJs}].includes(Number(advanced.{$prop})) ? advanced.{$prop} : undefined,";
            }
        }

        $titleProp = Helper::titleProp($ctx);

        $keywordPlaceholder = '搜索';
        if ($keywordSources !== []) {
            $keywordPlaceholder = implode(' / ', array_map(static fn ($f) => (string) $f['label'], $keywordSources));
        }

        $advancedTpl = '';
        foreach ($ctx->advancedFields as $f) {
            $advancedTpl .= $this->renderAdvancedItem($f, $ctx);
        }
        $columns = '';
        foreach ($ctx->listFields as $f) {
            $columns .= $this->renderListColumn($f, $ctx);
        }

        $advancedSlot = $advancedTpl === ''
            ? ''
            : "\n" . Template::render('index/advanced_slot.vue.tpl', ['items' => $advancedTpl]);

        return Template::render('index/index.vue.tpl', [
            'importBlock' => implode("\n", $imports) . "\n" . $constImport,
            'entity' => $ctx->entity,
            'titleProp' => $titleProp,
            'pk' => $ctx->primaryKey(),
            'pkTs' => $ctx->isIntPrimary() ? 'number' : 'string',
            'keywordRef' => $keywordSources !== [] ? "const keyword = ref('')\n\n" : '',
            'advBlock' => $advLines === [] ? '' : "const advanced = reactive({\n" . implode("\n", $advLines) . "\n})\n\n",
            'queryBlock' => "const queryParams = computed(() => ({\n" . implode("\n", $queryLines) . "\n}))\n",
            'resetFn' => $resetLines === []
                ? ''
                : Template::render('index/reset_fn.ts.tpl', [
                    'resetLines' => implode("\n", $resetLines),
                ]),
            'extraLabelFn' => '',
            'searchBarKeyword' => $keywordSources !== [] ? "\n                v-model:keyword=\"keyword\"" : '',
            'searchBarShowKeyword' => $keywordSources !== [] ? '' : "\n                :show-keyword=\"false\"",
            'keywordPlaceholder' => $keywordPlaceholder,
            'searchBarReset' => $resetLines === [] ? '' : "\n                @reset=\"resetAdvanced\"",
            'advancedSlot' => $advancedSlot,
            'columns' => $columns,
        ]);
    }

    /** renderAdvancedItem 高级搜索表单项 */
    private function renderAdvancedItem(array $f, Context $ctx): string
    {
        $prop = (string) $f['prop'];
        $label = (string) $f['label'];
        $sr = Context::searchRender($f);
        $optKey = Context::enumKey($f);
        $url = Context::remoteApi($f);

        if ($sr === 'remote_select' || $sr === 'remote_multi_select') {
            return Template::render('index/search/remote.vue.tpl', [
                'label' => $label,
                'prop' => $prop,
                'url' => $url,
                'valueField' => Context::remoteValueField($f),
                'labelField' => Context::remoteLabelField($f),
                'multiAttrs' => $sr === 'remote_multi_select' ? "\n                            multiple\n                            collapse-tags" : '',
                'placeholder' => Context::searchPlaceholder($f, $sr),
                'className' => $sr === 'remote_multi_select' ? '!w-48' : '!w-40',
            ]) . "\n";
        }
        if ($sr === 'number_range') {
            return Template::render('index/search/number_range.vue.tpl', [
                'label' => $label,
                'prop' => $prop,
            ]) . "\n";
        }
        if ($sr === 'date_range') {
            return Template::render('index/search/date_range.vue.tpl', [
                'label' => $label,
                'prop' => $prop,
            ]) . "\n";
        }
        if ($sr === 'datetime_range') {
            return Template::render('index/search/datetime_range.vue.tpl', [
                'label' => $label,
                'prop' => $prop,
                'valueFormat' => Context::isUnixDatetime($f) ? 'X' : 'YYYY-MM-DD HH:mm:ss',
            ]) . "\n";
        }
        if ($sr === 'input') {
            return Template::render('index/search/input.vue.tpl', [
                'label' => $label,
                'prop' => $prop,
                'placeholder' => Context::searchPlaceholder($f, $sr),
            ]) . "\n";
        }
        if ($sr === 'bool') {
            return Template::render('index/search/bool.vue.tpl', [
                'label' => $label,
                'prop' => $prop,
            ]) . "\n";
        }
        if (($sr === 'select' || $sr === 'radio') && $optKey !== '') {
            return Template::render('index/search/select.vue.tpl', [
                'label' => $label,
                'prop' => $prop,
                'optKey' => $optKey,
            ]) . "\n";
        }

        return '';
    }

    /** renderListColumn 列表列 */
    private function renderListColumn(array $f, Context $ctx): string
    {
        $prop = (string) $f['prop'];
        $label = (string) $f['label'];
        $lr = Context::listRender($f);
        $list = Context::listConfig($f) ?? [];
        $width = isset($list['width']) ? " width=\"{$list['width']}\"" : '';
        $minWidth = isset($list['minWidth']) ? " min-width=\"{$list['minWidth']}\"" : '';
        $relation = Context::relationType($f);
        $optKey = Context::enumKey($f);
        $base = ['label' => $label, 'width' => $width, 'minWidth' => $minWidth, 'prop' => $prop];

        if ($relation === 'belongs_to') {
            return Template::render('index/col/belongs_to.vue.tpl', array_merge($base, [
                'method' => Context::relationMethodName($f),
                'labelField' => Context::remoteLabelField($f),
            ])) . "\n";
        }
        if ($relation === 'belongs_to_many') {
            return Template::render('index/col/belongs_to_many.vue.tpl', array_merge($base, [
                'method' => Context::relationMethodName($f),
                'labelField' => Context::remoteLabelField($f),
            ])) . "\n";
        }

        return match ($lr) {
            'bool' => Template::render('index/col/bool.vue.tpl', $base) . "\n",
            'status', 'tag' => $optKey !== ''
                ? Template::render('index/col/status.vue.tpl', array_merge($base, ['optKey' => $optKey])) . "\n"
                : Template::render('index/col/prop.vue.tpl', array_merge($base, ['overflow' => ''])) . "\n",
            'color' => Template::render('index/col/color.vue.tpl', $base) . "\n",
            'image' => Template::render('index/col/image.vue.tpl', $base) . "\n",
            'datetime' => Context::isUnixDatetime($f)
                ? Template::render('index/col/datetime.vue.tpl', $base) . "\n"
                : Template::render('index/col/prop.vue.tpl', array_merge($base, ['overflow' => ''])) . "\n",
            'tags' => Template::render('index/col/tags.vue.tpl', array_merge($base, ['optKey' => $optKey])) . "\n",
            'number' => Template::render('index/col/number.vue.tpl', $base) . "\n",
            default => Template::render('index/col/prop.vue.tpl', array_merge($base, [
                'overflow' => ((isset($list['minWidth']) || $lr === 'text') && $prop !== 'id')
                    ? ' show-overflow-tooltip'
                    : '',
            ])) . "\n",
        };
    }
}
