<?php
declare(strict_types=1);

namespace phinx;

use Phinx\Migration\AbstractMigration;

/**
 * Migration GlassAdmin 系统表迁移基类（表前缀 + 显式 SQL）
 */
abstract class Migration extends AbstractMigration
{
    /** prefix 当前环境表前缀 */
    protected function prefix(): string
    {
        return (string) ($this->getAdapter()->getOption('table_prefix') ?? '');
    }

    /**
     * q 带前缀并加反引号的表名
     */
    protected function q(string $table): string
    {
        $name = $this->prefix() . $table;

        return '`' . str_replace('`', '', $name) . '`';
    }

    /**
     * prefixDdl 将 CREATE/ALTER 中的逻辑表名替换为带前缀名
     *
     * @param list<string> $tables
     */
    protected function prefixDdl(string $sql, array $tables): string
    {
        $prefix = $this->prefix();
        if ($prefix === '') {
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

    /**
     * insertRow 插入一行
     *
     * @param array<string, mixed> $row
     */
    protected function insertRow(string $table, array $row): void
    {
        $cols = array_keys($row);
        $quoted = implode(',', array_map(static fn (string $c): string => '`' . str_replace('`', '', $c) . '`', $cols));
        $placeholders = implode(',', array_fill(0, count($cols), '?'));
        $this->execute(
            'INSERT INTO ' . $this->q($table) . ' (' . $quoted . ') VALUES (' . $placeholders . ')',
            array_values($row),
        );
    }

    /**
     * archiveColumn 旧列归档（不 DROP）
     */
    protected function archiveColumn(string $table, string $col, string $kind): void
    {
        $to = $col . '_' . $kind . '_' . time();
        $this->execute(sprintf(
            'ALTER TABLE %s RENAME COLUMN `%s` TO `%s`',
            $this->q($table),
            str_replace('`', '', $col),
            str_replace('`', '', $to),
        ));
    }
}
