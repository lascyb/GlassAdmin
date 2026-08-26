<?php
declare (strict_types = 1);

namespace crud\generator;

use crud\field\FieldMeta;
use crud\Context;
use crud\Template;
use crud\generator\ui\Registry;
use crud\generator\ui\resolve\ListKey;
use crud\generator\ui\resolve\SearchKey;

/**
 * IndexFile 生成 index.vue
 */
class IndexFile
{
    public function render(Context $ctx): string
    {
        $constImports = $ctx->constantNames();
        $constImport = $constImports === []
            ? ''
            : 'import { ' . implode(', ', $constImports) . " } from '@/views/{$ctx->module}/{$ctx->entityFile}/constants'\n";

        $needRemote = $ctx->belongsToFields !== [] || $ctx->belongsToManyFields !== [];
        $needStatus = false;
        $needFormat = false;
        $needStorage = false;
        foreach ($ctx->listFields as $f) {
            $key = ListKey::resolve($f);
            if ($key === 'enum_tag') {
                $needStatus = true;
            }
            if ($key === 'datetime') {
                $needFormat = true;
            }
            if ($key === 'image') {
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
            "import GaPage from '@/components/ga-page/index.vue'",
            "import GaTable from '@/components/ga-table/index.vue'",
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
            $relation = FieldMeta::relationType($f);
            $sr = FieldMeta::searchRender($f);
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
                $advLines[] = FieldMeta::isUnixDatetime($f)
                    ? "    {$prop}: null as [number, number] | null,"
                    : "    {$prop}: null as [string, string] | null,";
                $resetLines[] = "    advanced.{$prop} = null";
                $queryLines[] = "    {$prop}_start: advanced.{$prop}?.[0] || undefined,";
                $queryLines[] = "    {$prop}_end: advanced.{$prop}?.[1] || undefined,";
            } elseif ($relation === 'belongs_to' || $sr === 'remote_select') {
                $advType = Helper::remoteValueIsString($f) ? 'string | undefined' : 'number | undefined';
                $advLines[] = "    {$prop}: undefined as {$advType},";
                $resetLines[] = "    advanced.{$prop} = undefined";
                $queryLines[] = "    {$prop}: advanced.{$prop},";
            } elseif ($sr === 'input') {
                $advLines[] = "    {$prop}: '',";
                $resetLines[] = "    advanced.{$prop} = ''";
                $queryLines[] = "    {$prop}: advanced.{$prop}.trim() || undefined,";
            } elseif ($sr === 'bool' || FieldMeta::isBoolField($f)) {
                $advLines[] = "    {$prop}: undefined as number | undefined,";
                $resetLines[] = "    advanced.{$prop} = undefined";
                $queryLines[] = "    {$prop}: advanced.{$prop} === 0 || advanced.{$prop} === 1 ? advanced.{$prop} : undefined,";
            } elseif ($sr === 'select' || $sr === 'radio' || FieldMeta::isEnumField($f)) {
                $vals = $ctx->enumValues($f);
                $valsJs = Helper::jsLiteralList($vals);
                $isString = Helper::enumIsString($vals);
                $advType = $isString ? 'string | undefined' : 'number | undefined';
                $advLines[] = "    {$prop}: undefined as {$advType},";
                $resetLines[] = "    advanced.{$prop} = undefined";
                $queryLines[] = $vals === []
                    ? "    {$prop}: advanced.{$prop},"
                    : "    {$prop}: [{$valsJs}].includes(advanced.{$prop} as never) ? advanced.{$prop} : undefined,";
            }
        }

        $titleProp = Helper::titleProp($ctx);

        $keywordPlaceholder = '搜索';
        if ($keywordSources !== []) {
            $keywordPlaceholder = implode(' / ', array_map(static fn ($f) => (string) $f['label'], $keywordSources));
        }

        $advancedTpl = '';
        foreach ($ctx->advancedFields as $f) {
            $renderer = Registry::search(SearchKey::resolve($f));
            if ($renderer !== null) {
                $advancedTpl .= $renderer->render($f, $ctx);
            }
        }
        $columns = '';
        foreach ($ctx->listFields as $f) {
            $columns .= Registry::list(ListKey::resolve($f))->render($f, $ctx);
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
}
