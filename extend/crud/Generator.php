<?php
declare (strict_types = 1);

namespace crud;

use crud\generator\ApisFile;
use crud\generator\ConstantsFile;
use crud\generator\ControllerFile;
use crud\generator\EditFile;
use crud\generator\IndexFile;
use crud\generator\ModelFile;
use crud\generator\ValidateFile;
use crud\generator\ui\FieldUiValidator;

/**
 * Generator 编排各文件生成器，组装主实体产物列表
 */
class Generator
{
    /**
     * build 返回待写文件列表
     *
     * @return list<array{path: string, content: string}>
     */
    public function build(Context $ctx): array
    {
        FieldUiValidator::validateContext($ctx);

        $files = [
            ['path' => $ctx->pathModel(), 'content' => (new ModelFile())->render($ctx)],
            ['path' => $ctx->pathValidate(), 'content' => (new ValidateFile())->render($ctx)],
            ['path' => $ctx->pathController(), 'content' => (new ControllerFile())->render($ctx)],
            ['path' => $ctx->pathApis(), 'content' => (new ApisFile())->render($ctx)],
            ['path' => $ctx->pathIndex(), 'content' => (new IndexFile())->render($ctx)],
            ['path' => $ctx->pathEdit(), 'content' => (new EditFile())->render($ctx)],
        ];
        if ($ctx->needConstants()) {
            $files[] = ['path' => $ctx->pathConstants(), 'content' => (new ConstantsFile())->render($ctx)];
        }

        return $files;
    }
}
