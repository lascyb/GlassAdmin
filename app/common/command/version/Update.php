<?php

declare(strict_types=1);

namespace app\common\command\version;

use phinx\InstallState;
use phinx\Runner;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * Update 数据库版本升级（Phinx migrate）
 */
class Update extends Command
{
    protected function configure(): void
    {
        $this->setName('version:update')
            ->setDescription('执行 database/migrations 中未应用的数据库升级');
    }

    protected function execute(Input $input, Output $output): int
    {
        if (!InstallState::isInstalled()) {
            $output->writeln('<error>系统尚未安装，请先打开 /install 完成初始化</error>');

            return 1;
        }

        try {
            $log = Runner::migrateFromApp();
        } catch (\Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return 1;
        }

        $log = trim($log);
        if ($log === '') {
            $output->writeln('<info>没有待执行的升级脚本</info>');
        } else {
            $output->writeln($log);
        }

        return 0;
    }
}
