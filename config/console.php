<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'rsa:generate'   => \app\admin\command\GenerateRsaKeys::class,
        'crud:generate'  => \app\admin\command\CrudGenerate::class,
        'mcp:serve'      => \app\admin\command\McpServe::class,
        'version:create' => \app\common\command\version\Create::class,
        'version:update' => \app\common\command\version\Update::class,
    ],
];
