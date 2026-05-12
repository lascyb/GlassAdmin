<?php
declare (strict_types = 1);

namespace app\command;

use app\service\RsaKeyService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Option;

class GenerateRsaKeys extends Command
{
    protected function configure(): void
    {
        $this->setName('rsa:generate')
            ->setDescription('生成 RSA 密钥对')
            ->addOption('count', 'c', Option::VALUE_OPTIONAL, '生成数量', 1)
            ->addOption('expire', 'e', Option::VALUE_OPTIONAL, '过期秒数', 7200);
    }

    protected function execute(Input $input, Output $output): int
    {
        $count = max(1, (int) $input->getOption('count'));
        $expireSeconds = max(3600, (int) $input->getOption('expire'));

        $generated = (new RsaKeyService())->generateDailyKeys($count, $expireSeconds);
        $output->writeln("计划生成: {$count}，实际生成: {$generated}");

        return 0;
    }
}
