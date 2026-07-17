<?php
declare (strict_types = 1);

/**
 * CLI 独立入口（不依赖 think 控制台）
 * 用法: php extend/crud/cli.php extend/crud/samples/test/text.json [--dry-run] [--force] [--no-auth]
 *
 * 注意：权限写库依赖 ThinkPHP Db，独立入口仅生成文件；写库请用 php think crud:generate
 */

$root = dirname(__DIR__, 2);
spl_autoload_register(static function (string $class) use ($root): void {
    if (!str_starts_with($class, 'crud\\')) {
        return;
    }
    $path = $root . '/extend/' . str_replace('\\', '/', $class) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

$configArg = $argv[1] ?? '';
if ($configArg === '') {
    fwrite(STDERR, "用法: php extend/crud/cli.php <config.json> [--dry-run] [--force] [--no-auth]\n");
    exit(1);
}

$dryRun = in_array('--dry-run', $argv, true) || in_array('-d', $argv, true);
$force = in_array('--force', $argv, true) || in_array('-f', $argv, true);
$noAuth = in_array('--no-auth', $argv, true);

$configPath = $configArg;
if (!preg_match('#^[A-Za-z]:[/\\\\]#', $configPath) && !str_starts_with($configPath, '/')) {
    $configPath = $root . '/' . ltrim(str_replace('\\', '/', $configPath), '/');
}

try {
    $config = \crud\Config::fromFile($configPath);
    if ($force) {
        $config['writeMode'] = 'overwrite';
    }
    if ($noAuth) {
        $config['generateAuth'] = false;
    }
    $ctx = \crud\Context::fromConfig($config);
    $files = (new \crud\Generator())->build($ctx);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

echo "模块 {$ctx->module}.{$ctx->entity}  表 {$ctx->table}\n";
echo '计划文件 ' . count($files) . " 个：\n";
foreach ($files as $file) {
    echo "  - {$file['path']} (" . strlen($file['content']) . " bytes)\n";
}
if ($ctx->generateAuth) {
    echo "提示: 权限写库请使用 php think crud:generate（本独立入口不写库）\n";
}

if ($dryRun) {
    echo "dry-run：未写盘\n";
    exit(0);
}

try {
    $result = (new \crud\Writer())->write($root, $files, (string) $config['writeMode']);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

$written = 0;
$skipped = 0;
foreach ($result as $item) {
    if ($item['status'] === 'written') {
        echo "写入 {$item['path']}\n";
        $written++;
    } else {
        echo "跳过 {$item['path']}\n";
        $skipped++;
    }
}
echo "完成 written={$written} skipped={$skipped}\n";
