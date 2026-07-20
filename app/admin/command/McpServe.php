<?php
declare (strict_types = 1);

namespace app\admin\command;

use mcp\ServerFactory;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * McpServe 启动 GlassAdmin MCP Server（stdio）
 */
class McpServe extends Command
{
    protected function configure(): void
    {
        $this->setName('mcp:serve')
            ->setDescription('启动 GlassAdmin MCP Server（stdio，供 Cursor 等客户端连接）');
    }

    protected function execute(Input $input, Output $output): int
    {
        // MCP 协议占用 stdout，禁止向 stdout 打印日志
        return ServerFactory::runStdio();
    }
}
