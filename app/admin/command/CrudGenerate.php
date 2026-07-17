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
            if ($ctx->generateAuth) {
                (new AuthInstaller())->validate($ctx);
            }
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
            $state = $item['status'] === 'exists' ? '已存在→跳过' : '将创建';
            $output->writeln("  - {$item['table']} ({$state})");
            if ($dryRun) {
                $output->writeln($item['sql']);
            }
        }

        $authInstaller = new AuthInstaller();
        $authPreview = [];
        if ($ctx->generateAuth) {
            $authPreview = $authInstaller->preview($ctx);
            $output->writeln('<info>计划写入权限/菜单 ' . count($authPreview) . ' 项（不挂角色）：</info>');
            foreach ($authPreview as $item) {
                $output->writeln("  - {$item['name']}");
            }
        }

        if ($dryRun) {
            $output->writeln('<comment>dry-run：未写盘、未写库</comment>');

            return 0;
        }

        try {
            $fileResult = (new Writer())->write($root, $files, (string) $config['writeMode']);
        } catch (\Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return 1;
        }

        $written = 0;
        $skipped = 0;
        foreach ($fileResult as $item) {
            if ($item['status'] === 'written') {
                $output->writeln("<info>写入</info> {$item['path']}");
                $written++;
            } else {
                $output->writeln("<comment>跳过</comment> {$item['path']}");
                $skipped++;
            }
        }

        try {
            $schema = (new SchemaInstaller())->install($ctx);
        } catch (\Throwable $e) {
            $output->writeln('<error>建表失败: ' . $e->getMessage() . '</error>');

            return 1;
        }
        $tablesCreated = 0;
        $tablesSkipped = 0;
        foreach ($schema as $item) {
            if ($item['status'] === 'created') {
                $output->writeln("<info>建表</info> {$item['table']}");
                $tablesCreated++;
            } else {
                $output->writeln("<comment>表已存在，跳过</comment> {$item['table']}");
                $tablesSkipped++;
            }
        }

        if ($ctx->generateAuth) {
            try {
                $auth = $authInstaller->install($ctx);
            } catch (\Throwable $e) {
                $output->writeln('<error>权限写入失败: ' . $e->getMessage() . '</error>');

                return 1;
            }
            $authItems = $authInstaller->applyInstallStatus($authPreview, $auth);
            foreach ($authItems as $item) {
                $label = match ($item['status']) {
                    'created' => '权限新建',
                    'updated' => '权限更新',
                    'reused' => '权限复用',
                    default => '权限',
                };
                $tag = $item['status'] === 'reused' ? 'comment' : 'info';
                $output->writeln("<{$tag}>{$label}</{$tag}> {$item['name']}");
            }
            if (!empty($auth['viewOverwritten'])) {
                $output->writeln('<info>已覆盖 view 权限绑定</info> cleared=' . $auth['bindingsCleared'] . ' written=' . $auth['bindings']);
            } else {
                $output->writeln('<info>frontend_api 新增绑定</info> ' . $auth['bindings']);
            }
        }

        $output->writeln("<info>完成</info> written={$written} skipped={$skipped}"
            . " tablesCreated={$tablesCreated} tablesSkipped={$tablesSkipped}");

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
