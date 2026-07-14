<?php
declare (strict_types = 1);

namespace crud;

/**
 * Template 从 templates/ 加载 tpl，用 <%name%> 占位符替换
 */
class Template
{
    /**
     * render 渲染模板
     *
     * @param array<string, string|int|float> $vars
     */
    public static function render(string $name, array $vars = []): string
    {
        $path = __DIR__ . '/templates/' . ltrim(str_replace('\\', '/', $name), '/');
        if (!is_file($path)) {
            throw new \RuntimeException('模板不存在: ' . $name);
        }
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException('无法读取模板: ' . $name);
        }
        foreach ($vars as $key => $value) {
            $content = str_replace('<%' . $key . '%>', (string) $value, $content);
        }

        return $content;
    }
}
