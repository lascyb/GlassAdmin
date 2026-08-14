<?php
declare(strict_types=1);

namespace app\install\service;

/**
 * EnvWriter 按模板或现有 .env 写入 DB_* 等键
 */
final class EnvWriter
{
    /**
     * write 更新或创建项目根 .env
     *
     * @param array<string, string> $values
     */
    public function write(string $envPath, string $examplePath, array $values): void
    {
        if (is_file($envPath)) {
            $src = (string) file_get_contents($envPath);
        } elseif (is_file($examplePath)) {
            $src = (string) file_get_contents($examplePath);
        } else {
            $src = '';
        }
        foreach ($values as $key => $value) {
            $src = $this->upsertKey($src, $key, $value);
        }
        if (@file_put_contents($envPath, $src) === false) {
            throw new \RuntimeException('.env 写入失败，请检查项目根目录权限');
        }
    }

    /**
     * writeViteDevelopment 按当前站点地址生成 web/.env.development
     */
    public function writeViteDevelopment(string $path, string $apiBaseUrl): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            throw new \RuntimeException('缺少 web 目录，无法写入 .env.development');
        }
        $body = "# API 基础地址\n"
            . 'VITE_API_BASE_URL=' . $this->encodeValue($apiBaseUrl) . "\n"
            . "# Pinia 持久化 key 前缀（与 storeId 拼接，默认 __GA__）\n"
            . "VITE_PERSIST_STORAGE_PREFIX=__GA__\n";
        if (@file_put_contents($path, $body) === false) {
            throw new \RuntimeException('web/.env.development 写入失败，请检查 web 目录权限');
        }
    }

    /**
     * upsertKey 替换已有键或追加
     */
    private function upsertKey(string $src, string $key, string $value): string
    {
        $line = $key . '=' . $this->encodeValue($value);
        $pattern = '/^' . preg_quote($key, '/') . '\s*=.*$/m';
        if (preg_match($pattern, $src) === 1) {
            return (string) preg_replace($pattern, $line, $src, 1);
        }
        $src = rtrim($src) . "\n";

        return $src . $line . "\n";
    }

    /** encodeValue 含空格或 # 时加引号 */
    private function encodeValue(string $value): string
    {
        if ($value === '') {
            return '';
        }
        if (preg_match('/[\s#"\'\\\\]/', $value) === 1) {
            return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
        }

        return $value;
    }
}
