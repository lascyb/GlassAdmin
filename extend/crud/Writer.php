<?php
declare (strict_types = 1);

namespace crud;

/**
 * Writer 按 writeMode 将生成结果写入项目根目录
 */
class Writer
{
    /**
     * write 写盘，返回带 status 的文件计划
     *
     * @param list<array{path: string, content: string}> $files
     * @return list<array{path: string, bytes: int, status: string}>
     */
    public function write(string $root, array $files, string $writeMode): array
    {
        $root = rtrim(str_replace('\\', '/', $root), '/');
        $out = [];

        foreach ($files as $file) {
            $rel = ltrim(str_replace('\\', '/', $file['path']), '/');
            $bytes = strlen($file['content']);
            $abs = $root . '/' . $rel;
            if (is_file($abs) && $writeMode === 'skip') {
                $out[] = [
                    'path'   => $rel,
                    'bytes'  => $bytes,
                    'status' => 'skipped',
                ];
                continue;
            }
            $dir = dirname($abs);
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new \RuntimeException('无法创建目录: ' . $dir);
            }
            if (file_put_contents($abs, $file['content']) === false) {
                throw new \RuntimeException('写入失败: ' . $rel);
            }
            $out[] = [
                'path'   => $rel,
                'bytes'  => $bytes,
                'status' => 'written',
            ];
        }

        return $out;
    }
}
