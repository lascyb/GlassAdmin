<?php
declare (strict_types = 1);

namespace app\admin\controller\dashboard;

use app\common\controller\AdminController;
use think\facade\Cache;
use think\Request;
use think\Response;

/**
 * Server 服务器性能仪表盘
 */
class Server extends AdminController
{
    /** cpuCacheKey CPU 采样缓存键（跨请求计算占用率） */
    private const string CPU_CACHE_KEY = 'dashboard:server:cpu_sample';

    /**
     * index 服务器性能快照
     */
    public function index(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }

        $cpu    = self::collectCpu();
        $memory = self::collectMemory();
        $disks  = self::collectDisks();
        $phpMem = memory_get_usage(true);
        $phpPeak = memory_get_peak_usage(true);

        return $this->ok([
            'timestamp' => time(),
            'cpu'       => $cpu,
            'memory'    => $memory,
            'disks'     => $disks,
            'runtime'   => [
                'php_version'     => PHP_VERSION,
                'sapi'            => PHP_SAPI,
                'os'              => PHP_OS_FAMILY,
                'uname'           => php_uname('s') . ' ' . php_uname('r'),
                'hostname'        => gethostname() ?: '',
                'uptime_seconds'  => self::collectUptimeSeconds(),
                'php_memory'      => $phpMem,
                'php_memory_peak' => $phpPeak,
                'php_memory_limit'=> self::parseBytes((string) ini_get('memory_limit')),
                'server_software' => (string) ($_SERVER['SERVER_SOFTWARE'] ?? ''),
            ],
        ]);
    }

    /**
     * collectCpu 采集 CPU 占用与负载
     *
     * @return array{usage_percent: float|null, load_1: float|null, load_5: float|null, load_15: float|null, cores: int}
     */
    private static function collectCpu(): array
    {
        $cores = (int) (self::detectCpuCores() ?: 1);
        $load1 = $load5 = $load15 = null;
        if (function_exists('sys_getloadavg')) {
            $load = @sys_getloadavg();
            if (is_array($load) && count($load) >= 3) {
                $load1  = round((float) $load[0], 2);
                $load5  = round((float) $load[1], 2);
                $load15 = round((float) $load[2], 2);
            }
        }

        $usage = null;
        if (PHP_OS_FAMILY === 'Windows') {
            $usage = self::windowsCpuUsage();
        } else {
            $usage = self::linuxCpuUsage();
        }

        return [
            'usage_percent' => $usage,
            'load_1'        => $load1,
            'load_5'        => $load5,
            'load_15'       => $load15,
            'cores'         => $cores,
        ];
    }

    /**
     * linuxCpuUsage 基于 /proc/stat 跨请求差分计算占用率
     */
    private static function linuxCpuUsage(): ?float
    {
        $sample = self::readLinuxCpuSample();
        if ($sample === null) {
            return null;
        }

        $prev = Cache::get(self::CPU_CACHE_KEY);
        Cache::set(self::CPU_CACHE_KEY, $sample, 120);
        if (!is_array($prev) || !isset($prev['idle'], $prev['total'])) {
            return null;
        }

        $totalDelta = $sample['total'] - (float) $prev['total'];
        $idleDelta  = $sample['idle'] - (float) $prev['idle'];
        if ($totalDelta <= 0) {
            return null;
        }

        return round(max(0.0, min(100.0, (1 - $idleDelta / $totalDelta) * 100)), 1);
    }

    /**
     * readLinuxCpuSample 读取 /proc/stat 汇总
     *
     * @return array{idle: float, total: float}|null
     */
    private static function readLinuxCpuSample(): ?array
    {
        if (!is_readable('/proc/stat')) {
            return null;
        }
        $fh = fopen('/proc/stat', 'r');
        if ($fh === false) {
            return null;
        }
        $line = fgets($fh);
        fclose($fh);
        if ($line === false || !str_starts_with($line, 'cpu ')) {
            return null;
        }
        $parts = preg_split('/\s+/', trim($line)) ?: [];
        // cpu user nice system idle iowait irq softirq steal ...
        $nums = array_map('floatval', array_slice($parts, 1));
        if (count($nums) < 4) {
            return null;
        }
        $idle  = $nums[3] + ($nums[4] ?? 0); // idle + iowait
        $total = array_sum($nums);

        return ['idle' => $idle, 'total' => $total];
    }

    /**
     * windowsCpuUsage 通过 WMI 读取 CPU 占用
     */
    private static function windowsCpuUsage(): ?float
    {
        if (class_exists('COM', false)) {
            try {
                $wmi  = new \COM('WinMgmts:\\\\.\\root\\CIMV2');
                $cpus = $wmi->ExecQuery('SELECT LoadPercentage FROM Win32_Processor');
                $sum  = 0;
                $n    = 0;
                foreach ($cpus as $cpu) {
                    $sum += (float) $cpu->LoadPercentage;
                    $n++;
                }
                if ($n > 0) {
                    return round($sum / $n, 1);
                }
            } catch (\Throwable) {
                // fall through
            }
        }

        $out = [];
        @exec('powershell -NoProfile -Command "(Get-CimInstance Win32_Processor | Measure-Object -Property LoadPercentage -Average).Average"', $out);
        if ($out !== [] && is_numeric(trim($out[0]))) {
            return round((float) trim($out[0]), 1);
        }

        return null;
    }

    /**
     * detectCpuCores 检测逻辑 CPU 核数
     */
    private static function detectCpuCores(): int
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $n = (int) getenv('NUMBER_OF_PROCESSORS');
            return $n > 0 ? $n : 1;
        }
        if (is_readable('/proc/cpuinfo')) {
            $content = (string) file_get_contents('/proc/cpuinfo');
            $count   = preg_match_all('/^processor\s*:/m', $content);
            if ($count > 0) {
                return $count;
            }
        }
        $n = (int) @shell_exec('nproc 2>/dev/null');
        return $n > 0 ? $n : 1;
    }

    /**
     * collectMemory 采集系统内存
     *
     * @return array{total: int, used: int, free: int, available: int, usage_percent: float|null}
     */
    private static function collectMemory(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return self::windowsMemory();
        }
        return self::linuxMemory();
    }

    /**
     * linuxMemory 从 /proc/meminfo 读取
     *
     * @return array{total: int, used: int, free: int, available: int, usage_percent: float|null}
     */
    private static function linuxMemory(): array
    {
        $total = $free = $available = $buffers = $cached = 0;
        if (is_readable('/proc/meminfo')) {
            foreach (file('/proc/meminfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                if (!preg_match('/^(\w+):\s+(\d+)/', $line, $m)) {
                    continue;
                }
                $kb = (int) $m[2] * 1024;
                match ($m[1]) {
                    'MemTotal'     => $total = $kb,
                    'MemFree'      => $free = $kb,
                    'MemAvailable' => $available = $kb,
                    'Buffers'      => $buffers = $kb,
                    'Cached'       => $cached = $kb,
                    default        => null,
                };
            }
        }
        if ($available <= 0) {
            $available = $free + $buffers + $cached;
        }
        $used = max(0, $total - $available);
        $pct  = $total > 0 ? round($used / $total * 100, 1) : null;

        return [
            'total'         => $total,
            'used'          => $used,
            'free'          => $free,
            'available'     => $available,
            'usage_percent' => $pct,
        ];
    }

    /**
     * windowsMemory 通过 WMI / PowerShell 读取物理内存
     *
     * @return array{total: int, used: int, free: int, available: int, usage_percent: float|null}
     */
    private static function windowsMemory(): array
    {
        $total = $free = 0;
        if (class_exists('COM', false)) {
            try {
                $wmi = new \COM('WinMgmts:\\\\.\\root\\CIMV2');
                foreach ($wmi->ExecQuery('SELECT TotalVisibleMemorySize,FreePhysicalMemory FROM Win32_OperatingSystem') as $os) {
                    $total = (int) $os->TotalVisibleMemorySize * 1024;
                    $free  = (int) $os->FreePhysicalMemory * 1024;
                    break;
                }
            } catch (\Throwable) {
                // fall through
            }
        }
        if ($total <= 0) {
            $out = [];
            @exec('powershell -NoProfile -Command "$o=Get-CimInstance Win32_OperatingSystem; Write-Output ($o.TotalVisibleMemorySize); Write-Output ($o.FreePhysicalMemory)"', $out);
            if (count($out) >= 2) {
                $total = (int) trim($out[0]) * 1024;
                $free  = (int) trim($out[1]) * 1024;
            }
        }
        $used = max(0, $total - $free);
        $pct  = $total > 0 ? round($used / $total * 100, 1) : null;

        return [
            'total'         => $total,
            'used'          => $used,
            'free'          => $free,
            'available'     => $free,
            'usage_percent' => $pct,
        ];
    }

    /**
     * collectDisks 采集主要磁盘分区用量
     *
     * @return list<array{mount: string, total: int, used: int, free: int, usage_percent: float|null}>
     */
    private static function collectDisks(): array
    {
        $mounts = [];
        if (PHP_OS_FAMILY === 'Windows') {
            foreach (range('C', 'Z') as $letter) {
                $path = $letter . ':\\';
                if (is_dir($path)) {
                    $mounts[] = $path;
                }
            }
        } else {
            $mounts = ['/'];
            if (is_readable('/proc/mounts')) {
                foreach (file('/proc/mounts', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                    $parts = preg_split('/\s+/', trim($line)) ?: [];
                    if (count($parts) < 3) {
                        continue;
                    }
                    [$device, $mount, $fs] = $parts;
                    if (!str_starts_with($device, '/') || !str_starts_with($mount, '/')) {
                        continue;
                    }
                    if (in_array($fs, ['ext4', 'ext3', 'xfs', 'btrfs', 'vfat', 'ntfs', 'fuseblk'], true)
                        && !in_array($mount, $mounts, true)) {
                        $mounts[] = $mount;
                    }
                }
            }
        }

        $result = [];
        foreach ($mounts as $mount) {
            $total = @disk_total_space($mount);
            $free  = @disk_free_space($mount);
            if ($total === false || $free === false || $total <= 0) {
                continue;
            }
            $total = (int) $total;
            $free  = (int) $free;
            $used  = max(0, $total - $free);
            $result[] = [
                'mount'         => rtrim(str_replace('\\', '/', $mount), '/') ?: $mount,
                'total'         => $total,
                'used'          => $used,
                'free'          => $free,
                'usage_percent' => round($used / $total * 100, 1),
            ];
            if (count($result) >= 8) {
                break;
            }
        }

        return $result;
    }

    /**
     * collectUptimeSeconds 系统运行时长（秒）
     */
    private static function collectUptimeSeconds(): ?int
    {
        if (PHP_OS_FAMILY !== 'Windows' && is_readable('/proc/uptime')) {
            $raw = trim((string) file_get_contents('/proc/uptime'));
            $sec = (float) explode(' ', $raw)[0];
            return (int) $sec;
        }
        if (PHP_OS_FAMILY === 'Windows' && class_exists('COM', false)) {
            try {
                $wmi = new \COM('WinMgmts:\\\\.\\root\\CIMV2');
                foreach ($wmi->ExecQuery('SELECT LastBootUpTime FROM Win32_OperatingSystem') as $os) {
                    $boot = (string) $os->LastBootUpTime; // yyyymmddHHMMSS.ffffff+UUU
                    if (preg_match('/^(\d{14})/', $boot, $m)) {
                        $ts = strtotime(
                            substr($m[1], 0, 4) . '-' . substr($m[1], 4, 2) . '-' . substr($m[1], 6, 2) . ' '
                            . substr($m[1], 8, 2) . ':' . substr($m[1], 10, 2) . ':' . substr($m[1], 12, 2)
                        );
                        if ($ts !== false) {
                            return max(0, time() - $ts);
                        }
                    }
                    break;
                }
            } catch (\Throwable) {
                // ignore
            }
        }

        return null;
    }

    /**
     * parseBytes 解析 PHP ini 内存大小（如 128M）
     */
    private static function parseBytes(string $val): int
    {
        $val = trim($val);
        if ($val === '' || $val === '-1') {
            return -1;
        }
        if (!preg_match('/^(\d+)\s*([KMGT])?B?$/i', $val, $m)) {
            return (int) $val;
        }
        $n = (int) $m[1];
        $u = strtoupper($m[2] ?? '');
        return match ($u) {
            'K' => $n * 1024,
            'M' => $n * 1024 ** 2,
            'G' => $n * 1024 ** 3,
            'T' => $n * 1024 ** 4,
            default => $n,
        };
    }
}
