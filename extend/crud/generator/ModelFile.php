<?php
declare (strict_types = 1);

namespace crud\generator;

use crud\Context;
use crud\Template;

/**
 * ModelFile 生成 Model.php
 */
class ModelFile
{
    /** render 渲染 Model 内容 */
    public function render(Context $ctx): string
    {
        $typeLines = [];
        foreach ($ctx->fields as $f) {
            $prop = (string) $f['prop'];
            if (Context::relationType($f) === 'belongs_to_many') {
                continue;
            }
            $col = Context::columnConfig($f);
            if ($col === null) {
                continue;
            }
            $ct = (string) ($col['type'] ?? '');
            $phpType = match (true) {
                in_array($ct, ['bigint', 'int', 'tinyint'], true) => 'integer',
                $ct === 'decimal' => 'float',
                $ct === 'json' => 'json',
                default => null,
            };
            if ($phpType !== null) {
                $typeLines[] = "        '{$prop}' => '{$phpType}',";
            }
        }

        $uses = ['use think\\Model;'];
        $rels = '';
        $helpers = '';

        if ($ctx->belongsToFields !== []) {
            $uses[] = 'use think\\model\\relation\\BelongsTo;';
        }
        if ($ctx->belongsToManyFields !== []) {
            $uses[] = 'use think\\facade\\Db;';
            $uses[] = 'use think\\model\\relation\\BelongsToMany;';
        }

        foreach ($ctx->belongsToFields as $f) {
            $method = Context::relationMethodName($f);
            $remote = Context::parseRemoteApi(Context::remoteApi($f) ?: null);
            $relatedEntity = $remote['entity'] ?? ucfirst($method);
            $relatedModule = $remote['module'] ?? $ctx->module;
            if ($relatedModule !== $ctx->module) {
                $uses[] = 'use ' . Helper::modelNamespace($relatedModule) . "\\{$relatedEntity};";
            }
            $rels .= "\n" . Template::render('model/belongs_to.php.tpl', [
                'method' => $method,
                'label' => (string) $f['label'],
                'relatedClass' => $relatedEntity,
                'fk' => (string) $f['prop'],
            ]);
        }

        foreach ($ctx->belongsToManyFields as $f) {
            $method = Context::relationMethodName($f);
            $remote = Context::parseRemoteApi(Context::remoteApi($f) ?: null);
            $relatedEntity = $remote['entity'] ?? ucfirst(rtrim($method, 's'));
            $relatedModule = $remote['module'] ?? $ctx->module;
            $rel = Context::relationConfig($f) ?? [];
            $pivot = (string) ($rel['pivotTable'] ?? ($ctx->table . '_' . rtrim($method, 's')));
            $fk = (string) ($rel['foreignKey'] ?? (strtolower($ctx->entity) . '_id'));
            $rk = (string) ($rel['relatedKey'] ?? (strtolower($relatedEntity) . '_id'));
            if ($relatedModule !== $ctx->module) {
                $uses[] = 'use ' . Helper::modelNamespace($relatedModule) . "\\{$relatedEntity};";
            }
            $rels .= "\n" . Template::render('model/belongs_to_many.php.tpl', [
                'method' => $method,
                'label' => (string) $f['label'],
                'relatedClass' => $relatedEntity,
                'pivot' => $pivot,
                'fk' => $fk,
                'rk' => $rk,
            ]);
            $helpers .= "\n" . Template::render('model/sync_helpers.php.tpl', [
                'sync' => 'sync' . ucfirst($method),
                'idsFor' => Helper::idsForMethodName((string) $f['prop']),
                'label' => (string) $f['label'],
                'pivot' => $pivot,
                'fk' => $fk,
                'rk' => $rk,
            ]);
        }

        $uses = array_unique($uses);
        sort($uses);

        $pk = $ctx->primaryKey();
        $pkBlock = $pk === 'id'
            ? ''
            : "\n    /** @var string 主键 */\n    protected \$pk = '{$pk}';\n";
        $autoWriteTimestamp = $ctx->hasTimestampFields() ? 'true' : 'false';

        return Template::render('model/model.php.tpl', [
            'nsModel' => $ctx->nsModel,
            'useBlock' => implode("\n", $uses),
            'entity' => $ctx->entity,
            'title' => $ctx->title,
            'table' => $ctx->table,
            'pkBlock' => $pkBlock,
            'autoWriteTimestamp' => $autoWriteTimestamp,
            'typeBlock' => implode("\n", $typeLines),
            'rels' => $rels,
            'helpers' => $helpers,
        ]);
    }
}
