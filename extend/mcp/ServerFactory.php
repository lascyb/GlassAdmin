<?php
declare (strict_types = 1);

namespace mcp;

use Mcp\Server;
use Mcp\Server\Transport\StdioTransport;
use Psr\Log\NullLogger;

/**
 * ServerFactory 组装 GlassAdmin MCP Server（stdio）
 */
class ServerFactory
{
    /**
     * create 构建 MCP Server
     */
    public static function create(): Server
    {
        $base = rtrim(str_replace('\\', '/', root_path()), '/') . '/extend';

        return Server::builder()
            ->setServerInfo(
                'GlassAdmin MCP',
                '1.0.0',
                'GlassAdmin CRUD 开发助手：表结构、样例、dry-run 与受控生成',
            )
            ->setInstructions(
                '优先使用只读 Tools / Resources 了解表与样例；'
                . '生成前必须先 crud_generate_dry_run；'
                . '真实写盘使用 crud_generate 且 confirm=true。'
                . '禁止操作系统表与鉴权密钥相关能力。',
            )
            ->setLogger(new NullLogger())
            ->setDiscovery($base, ['mcp'])
            ->build();
    }

    /**
     * runStdio 以 STDIO 运行（供 Cursor 等客户端拉起）
     */
    public static function runStdio(): int
    {
        $server = self::create();
        $transport = new StdioTransport(STDIN, STDOUT, new NullLogger());

        return (int) $server->run($transport);
    }
}
