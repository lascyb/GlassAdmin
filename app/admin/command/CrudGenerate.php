<?php
declare (strict_types = 1);

namespace app\admin\command;

use crud\AuthInstaller;
use crud\Config;
use crud\Context;
use crud\Generator;
use crud\SchemaInstaller;
use crud\Writer;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

/**
 * CrudGenerate 根据 JSON 配置生成主实体 CRUD 文件，并创建数据表、可选写入权限/菜单
 */
class CrudGenerate extends Command
{
    protected function configure(): void
    {
        $this->setName('crud:generate')
            ->setDescription('根据 JSON 配置生成 CRUD 主实体文件')
            ->addArgument('config', Argument::REQUIRED, '配置 JSON 路径（相对项目根或绝对路径）')
            ->addOption('dry-run', 'd', Option::VALUE_NONE, '只预览不写盘/不写库')
            ->addOption('force', 'f', Option::VALUE_NONE, '覆盖已存在文件（writeMode=overwrite）')
            ->addOption('no-auth', null, Option::VALUE_NONE, '不写入权限/菜单到数据库');
    }

    protected function execute(Input $input, Output $output): int
    {
        $configArg = (string) $input->getArgument('config');
        $root = rtrim(str_replace('\\', '/', root_path()), '/');
        $configPath = $this->resolvePath($root, $configArg);
        $dryRun = (bool) $input->getOption('dry-run');

        try {
            $config = Config::fromFile($configPath);
            if ($input->getOption('force')) {
                $config['writeMode'] = 'overwrite';
            }
            if ($input->getOption('no-auth')) {
                $config['generateAuth'] = false;
            }
            $ctx = Context::fromConfig($config);
            $files = (new Generator())->build($ctx);
        } catch (\Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return 1;
        }

        $output->writeln("<info>模块</info> {$ctx->module}.{$ctx->entity}  <info>表</info> {$ctx->table}");
        $output->writeln('<info>计划文件 ' . count($files) . ' 个：</info>');
        foreach ($files as $file) {
            $bytes = strlen($file['content']);
            $output->writeln("  - {$file['path']} ({$bytes} bytes)");
        }

        $schemaPreview = (new SchemaInstaller())->preview($ctx);
        $output->writeln('<info>计划建表 ' . count($schemaPreview) . ' 张（已存在则跳过）：</info>');
        foreach ($schemaPreview as $item) {
            $state = $item['exists'] ? '已存在→跳过' : '将创建';
            $output->writeln("  - {$item['table']} ({$state})");
            if ($dryRun) {
                $output->writeln($item['sql']);
            }
        }

        if ($ctx->generateAuth) {
            $preview = (new AuthInstaller())->preview($ctx);
            $output->writeln('<info>计划写入权限/菜单 ' . count($preview) . ' 项（不挂角色）：</info>');
            foreach ($preview as $item) {
                $output->writeln("  - {$item}");
            }
        }

        if ($dryRun) {
            $output->writeln('<comment>dry-run：未写盘、未写库</comment>');

            return 0;
        }

        try {
            $result = (new Writer())->write($root, $files, (string) $config['writeMode']);
        } catch (\Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return 1;
        }

        foreach ($result['written'] as $path) {
            $output->writeln("<info>写入</info> {$path}");
        }
        foreach ($result['skipped'] as $path) {
            $output->writeln("<comment>跳过</comment> {$path}");
        }

        try {
            $schema = (new SchemaInstaller())->install($ctx);
        } catch (\Throwable $e) {
            $output->writeln('<error>建表失败: ' . $e->getMessage() . '</error>');

            return 1;
        }
        foreach ($schema['created'] as $table) {
            $output->writeln("<info>建表</info> {$table}");
        }
        foreach ($schema['skipped'] as $table) {
            $output->writeln("<comment>表已存在，跳过</comment> {$table}");
        }

        if ($ctx->generateAuth) {
            try {
                $auth = (new AuthInstaller())->install($ctx);
            } catch (\Throwable $e) {
                $output->writeln('<error>权限写入失败: ' . $e->getMessage() . '</error>');

                return 1;
            }
            foreach ($auth['created'] as $item) {
                $output->writeln("<info>权限新建</info> {$item}");
            }
            foreach ($auth['updated'] as $item) {
                $output->writeln("<info>权限更新</info> {$item}");
            }
            foreach ($auth['reused'] as $item) {
                $output->writeln("<comment>权限复用</comment> {$item}");
            }
            if (!empty($auth['viewOverwritten'])) {
                $output->writeln('<info>已覆盖 view 权限绑定</info> cleared=' . $auth['bindingsCleared'] . ' written=' . $auth['bindings']);
            } else {
                $output->writeln('<info>frontend_api 新增绑定</info> ' . $auth['bindings']);
            }
        }

        $output->writeln('<info>完成</info> written=' . count($result['written']) . ' skipped=' . count($result['skipped'])
            . ' tablesCreated=' . count($schema['created']) . ' tablesSkipped=' . count($schema['skipped']));

        return 0;
    }

    /** resolvePath 解析配置路径 */
    private function resolvePath(string $root, string $path): string
    {
        $path = str_replace('\\', '/', $path);
        if (preg_match('/^[A-Za-z]:\//', $path) || str_starts_with($path, '/')) {
            return $path;
        }

        return $root . '/' . ltrim($path, '/');
    }
}
