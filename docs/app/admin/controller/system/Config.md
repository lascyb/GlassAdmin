# Config

源码：[app/admin/controller/system/Config.php](../../../../../app/admin/controller/system/Config.php)  
服务：[app/admin/service/SystemConfigService.php](../../../../../app/admin/service/SystemConfigService.php)  
前端 API：[web/src/apis/system/config.ts](../../../../../web/src/apis/system/config.ts)

## 作用

系统配置 CRUD + 两档只读：未登录 `public`、已登录 `runtime`。值类型由 `SystemConfigService::castValueByType` 处理。

## 推荐使用场景

- 站点标题、布局默认值等键值。
- `status=2` 内置项只能改 `value`/`description`/`sort`，不可删。
- 布局首次默认：[useLayoutSetting.applySystemDefaults](../../../../../web/src/pinia/layout/setting.ts)。

## 要点

| 方法 | 门闩 | 说明 |
|---|---|---|
| `public` | `$noLoginActions` | `site_title`、`site_description`；HTTP 缓存 1h |
| `runtime` | `$loginOnlyActions` | 含 `layout_*`、`site_title`、`admin_default_login_methods`；缓存 60s |

内置项 `admin_default_login_methods`（`multi_select`，`status=2`）：账号未单独配置登录方式时的默认集合。
| `list` / `read` / `groups` / `save` / `update` / `delete` / `batchSave` | 登录 + RBAC | 管理页 |

## 已使用场景

- [web/src/views/system/config/index.vue](../../../../../web/src/views/system/config/index.vue)、[edit.vue](../../../../../web/src/views/system/config/edit.vue)
- [web/src/pinia/system/config.ts](../../../../../web/src/pinia/system/config.ts)（`public` / `runtime`）
- [web/src/layouts/index.vue](../../../../../web/src/layouts/index.vue)

## 逃生舱

`.env` / 硬编码，或 `json()` 自己吐配置。见 [escape-hatches.md](../../../../escape-hatches.md)。
