<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$app = new think\App();
$app->initialize();

use app\admin\service\SystemConfigService;

SystemConfigService::forgetAllConfigCache();

$checks = [
    ['cast switch 0', SystemConfigService::castValueByType('switch', '0') === false],
    ['cast switch 1', SystemConfigService::castValueByType('switch', '1') === true],
    ['cast number', SystemConfigService::castValueByType('number', '220') === 220],
    ['norm switch', SystemConfigService::normalizeValueByType('switch', false) === '0'],
    ['norm number', SystemConfigService::normalizeValueByType('number', 220) === '220'],
    ['get layout width', SystemConfigService::getConfig('layout_aside_width') === 220],
    ['get show menus', SystemConfigService::getConfig('layout_show_all_menus') === false],
];

foreach ($checks as [$label, $ok]) {
    echo ($ok ? 'OK' : 'FAIL') . " {$label}\n";
    if (!$ok) {
        exit(1);
    }
}

echo "ALL OK\n";

$row = \app\admin\model\system\Config::find(8);
if (!$row) {
    echo "SKIP row cast: id 8 missing\n";
    exit(0);
}
$apiRow = SystemConfigService::castRowForApi($row->toArray());
if (!is_bool($apiRow['value'])) {
    echo "FAIL read row switch not bool\n";
    exit(1);
}
echo "OK castRowForApi switch bool\n";
