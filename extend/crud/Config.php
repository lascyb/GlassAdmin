<?php
declare (strict_types = 1);

namespace crud;

/**
 * Config 读取并校验 CRUD 生成配置 JSON
 */
class Config
{
    /**
     * fromFile 从 JSON 文件加载配置
     *
     * @return array<string, mixed>
     */
    public static function fromFile(string $path): array
    {
        if (!is_file($path)) {
            throw new \InvalidArgumentException('配置文件不存在: ' . $path);
        }
        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            throw new \InvalidArgumentException('配置文件为空: ' . $path);
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException('配置 JSON 无效: ' . $path);
        }

        return self::normalize($data);
    }

    /**
     * normalize 校验必填并规范化命名
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        foreach (['table', 'fields', 'menus', 'paths'] as $key) {
            if (!isset($data[$key]) || $data[$key] === '' || $data[$key] === []) {
                throw new \InvalidArgumentException("配置缺少必填项: {$key}");
            }
        }
        if (!is_array($data['fields'])) {
            throw new \InvalidArgumentException('fields 必须为数组');
        }
        if (!is_array($data['paths'])) {
            throw new \InvalidArgumentException('paths 必须为对象');
        }

        $paths = self::normalizePaths($data['paths']);
        $parsed = self::parseControllerPath($paths['controller']);
        $module = $parsed['module'];
        $entity = $parsed['entity'];

        if ($module !== '' && !preg_match('/^[a-z][a-z0-9_\/]*$/', $module)) {
            throw new \InvalidArgumentException('paths.controller 目录段无效');
        }
        if (!preg_match('/^[A-Z][A-Za-z0-9]*$/', $entity)) {
            throw new \InvalidArgumentException('paths.controller 类名须为 PascalCase');
        }

        $writeMode = $data['writeMode'] ?? 'skip';
        if (!in_array($writeMode, ['skip', 'overwrite'], true)) {
            throw new \InvalidArgumentException('writeMode 仅支持 skip|overwrite');
        }

        $menus = self::normalizeMenus($data['menus']);
        $leaf = $menus[count($menus) - 1];
        $title = (string) $leaf['title'];

        $primary = self::firstEnabled($menus);
        $moduleTitle = (string) ($primary['title'] ?? $title);
        $menuIcon = trim((string) ($primary['icon'] ?? ($data['menuIcon'] ?? 'el:Document')));
        if ($menuIcon === '') {
            $menuIcon = 'el:Document';
        }
        $menuSort = isset($primary['sort']) ? (int) $primary['sort'] : (int) ($data['menuSort'] ?? 0);

        $fields = [];
        foreach (array_values($data['fields']) as $i => $f) {
            if (!is_array($f)) {
                throw new \InvalidArgumentException("fields[{$i}] 必须为对象");
            }
            $prop = trim((string) ($f['prop'] ?? ''));
            $label = trim((string) ($f['label'] ?? ''));
            if ($prop === '' || $label === '') {
                throw new \InvalidArgumentException("fields[{$i}] 缺少 prop/label");
            }
            $col = $f['column'] ?? null;
            $rel = $f['relation'] ?? null;
            if ($col === null && !(is_array($rel) && ($rel['type'] ?? '') === 'belongs_to_many')) {
                throw new \InvalidArgumentException("fields[{$prop}] 缺少 column（belongs_to_many 可省略）");
            }
            if (is_array($col) && empty($col['type'])) {
                throw new \InvalidArgumentException("fields[{$prop}] 缺少 column.type");
            }
            $fields[] = $f;
        }

        return [
            'module'       => $module,
            'entity'       => $entity,
            'table'        => trim((string) $data['table']),
            'title'        => $title,
            'moduleTitle'  => $moduleTitle,
            'fields'       => $fields,
            'maps'         => is_array($data['maps'] ?? null) ? $data['maps'] : [],
            'options'      => is_array($data['options'] ?? null) ? $data['options'] : [],
            'writeMode'    => $writeMode,
            'generateAuth' => !array_key_exists('generateAuth', $data) || (bool)$data['generateAuth'],
            'menuIcon'     => $menuIcon,
            'menuSort'     => $menuSort,
            'menus'        => $menus,
            'paths'        => $paths,
        ];
    }

    /**
     * normalizePaths 规范化相对路径
     *
     * @param array<string, mixed> $raw
     * @return array{controller: string, model: string, validate: string, apis: string, viewsDir: string}
     */
    private static function normalizePaths(array $raw): array
    {
        $keys = ['controller', 'model', 'validate', 'apis', 'viewsDir'];
        $out = [];
        foreach ($keys as $key) {
            $v = trim(str_replace('\\', '/', (string) ($raw[$key] ?? '')));
            $v = ltrim($v, '/');
            if ($v === '' || str_contains($v, '..')) {
                throw new \InvalidArgumentException("paths.{$key} 无效");
            }
            if (in_array($key, ['controller', 'model', 'validate'], true) && !preg_match('/\.php$/i', $v)) {
                throw new \InvalidArgumentException("paths.{$key} 须以 .php 结尾");
            }
            if ($key === 'apis' && !preg_match('/\.ts$/i', $v)) {
                throw new \InvalidArgumentException('paths.apis 须以 .ts 结尾');
            }
            if ($key === 'viewsDir') {
                $v = rtrim($v, '/') . '/';
            }
            $out[$key] = $v;
        }

        return $out;
    }

    /**
     * parseControllerPath 从相对路径解析 module + entity
     *
     * @return array{module: string, entity: string}
     */
    public static function parseControllerPath(string $controller): array
    {
        $cleaned = preg_replace('/\.php$/i', '', ltrim(str_replace('\\', '/', $controller), '/')) ?? '';
        $segs = array_values(array_filter(explode('/', $cleaned), static fn ($s) => $s !== ''));
        if ($segs === []) {
            return ['module' => '', 'entity' => 'Item'];
        }
        if (count($segs) === 1) {
            return ['module' => '', 'entity' => $segs[0]];
        }
        $entity = $segs[count($segs) - 1];
        $module = implode('/', array_slice($segs, 0, -1));

        return ['module' => $module, 'entity' => $entity];
    }

    /**
     * normalizeMenus 规范化 menus；末项必须为 view
     *
     * @param mixed $raw
     * @return list<array{type: string, path: string, title: string, icon?: string, sort?: int, enabled: bool}>
     */
    private static function normalizeMenus(mixed $raw): array
    {
        if (!is_array($raw) || $raw === []) {
            throw new \InvalidArgumentException('menus 不能为空，且末项须为页面(view)');
        }

        $menus = [];
        $list = array_values($raw);
        $last = count($list) - 1;
        foreach ($list as $i => $item) {
            if (!is_array($item)) {
                throw new \InvalidArgumentException("menus[{$i}] 必须为对象");
            }
            $type = strtolower(trim((string) ($item['type'] ?? ($i === $last ? 'view' : 'menu'))));
            if ($i === $last) {
                $type = 'view';
            } elseif ($type !== 'menu') {
                $type = 'menu';
            }
            if (!in_array($type, ['menu', 'view'], true)) {
                throw new \InvalidArgumentException("menus[{$i}].type 无效");
            }
            $path = strtolower(trim((string) ($item['path'] ?? '')));
            $title = trim((string) ($item['title'] ?? ''));
            if ($path === '' || !preg_match('/^[a-z][a-z0-9_-]*$/', $path)) {
                throw new \InvalidArgumentException("menus[{$i}].path 无效");
            }
            if ($title === '') {
                throw new \InvalidArgumentException("menus[{$i}].title 不能为空");
            }
            $enabled = !array_key_exists('enabled', $item) || (bool) $item['enabled'];
            $row = [
                'type'    => $type,
                'path'    => $path,
                'title'   => $title,
                'enabled' => $enabled,
            ];
            $icon = trim((string) ($item['icon'] ?? ''));
            if ($icon !== '') {
                $row['icon'] = $icon;
            }
            if (array_key_exists('sort', $item) && $item['sort'] !== null && $item['sort'] !== '') {
                $row['sort'] = (int) $item['sort'];
            }
            $menus[] = $row;
        }

        return $menus;
    }

    /**
     * firstEnabled 取首个 enabled 项
     *
     * @param list<array{type: string, path: string, title: string, icon?: string, sort?: int, enabled: bool}> $menus
     * @return array{type: string, path: string, title: string, icon?: string, sort?: int, enabled: bool}|null
     */
    private static function firstEnabled(array $menus): ?array
    {
        foreach ($menus as $m) {
            if (!empty($m['enabled'])) {
                return $m;
            }
        }

        return $menus[0] ?? null;
    }
}
