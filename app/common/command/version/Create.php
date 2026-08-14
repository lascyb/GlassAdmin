<?php

declare(strict_types=1);

namespace app\common\command\version;

use phinx\Runner;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

/**
 * Create 在 database/migrations 生成数据库升级脚本
 */
class Create extends Command
{
    protected function configure(): void
    {
        $this->setName('version:create')
            ->setDescription('在 database/migrations 生成数据库升级脚本')
            ->addArgument('name', Argument::REQUIRED, '迁移类名，PascalCase，如 AddFooColumn');
    }

    protected function execute(Input $input, Output $output): int
    {
        $name = (string) $input->getArgument('name');
        try {
            $path = Runner::create($name);
        } catch (\Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return 1;
        }

        $output->writeln('<info>已生成</info> ' . $path);

        return 0;
    }
}
