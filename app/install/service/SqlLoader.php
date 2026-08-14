<?php
declare(strict_types=1);

namespace app\install\service;

use PDO;

/**
 * SqlLoader 执行 database/install 下的初始化 SQL（按表单前缀替换逻辑表名）
 */
final class SqlLoader
{
    /**
     * run 依次执行 schema.sql、data.sql
     *
     * @return list<string>
     */
    public static function run(PDO $pdo, string $prefix): array
    {
        $dir = rtrim(root_path(), '/\\') . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'install';
        $schemaFile = $dir . DIRECTORY_SEPARATOR . 'schema.sql';
        $dataFile = $dir . DIRECTORY_SEPARATOR . 'data.sql';
        if (!is_file($schemaFile) || !is_file($dataFile)) {
            throw new \RuntimeException('缺少 database/install/schema.sql 或 data.sql');
        }

        $schema = self::readSql($schemaFile);
        $tables = self::tableNames($schema);
        $log = [];
        $log[] = self::execFile($pdo, $schema, $prefix, $tables, 'schema.sql');
        $log[] = self::execFile($pdo, self::readSql($dataFile), $prefix, $tables, 'data.sql');

        return $log;
    }

    /**
     * @param list<string> $tables
     */
    private static function execFile(PDO $pdo, string $sql, string $prefix, array $tables, string $label): string
    {
        $n = 0;
        foreach (self::statements($sql) as $stmt) {
            $pdo->exec(self::prefixSql($stmt, $prefix, $tables));
            $n++;
        }

        return $label . ': ' . $n . ' statements';
    }

    private static function readSql(string $path): string
    {
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new \RuntimeException('无法读取 ' . $path);
        }

        return $raw;
    }

    /**
     * @return list<string>
     */
    private static function tableNames(string $schema): array
    {
        preg_match_all('/CREATE TABLE(?: IF NOT EXISTS)? `([a-zA-Z][a-zA-Z0-9_]*)`/', $schema, $m);

        return array_values(array_unique($m[1]));
    }

    /**
     * @return list<string>
     */
    private static function statements(string $sql): array
    {
        $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
        $parts = preg_split('/;\s*(?:\r\n|\n|\r|$)/', $sql) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $out[] = $part;
        }

        return $out;
    }

    /**
     * @param list<string> $tables
     */
    private static function prefixSql(string $sql, string $prefix, array $tables): string
    {
        if ($prefix === '' || $tables === []) {
            return $sql;
        }
        $set = array_fill_keys($tables, true);

        return (string) preg_replace_callback('/`([a-zA-Z][a-zA-Z0-9_]*)`/', static function (array $m) use ($prefix, $set): string {
            $name = $m[1];
            if (!isset($set[$name])) {
                return $m[0];
            }

            return '`' . $prefix . $name . '`';
        }, $sql);
    }
}
