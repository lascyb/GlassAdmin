# CRUD 生成器

源码目录：[extend/crud/](../../../extend/crud/)  
编排：[Generator.php](../../../extend/crud/Generator.php)（Model / Validate / Controller / Apis / Index / Edit，可选 Constants）。  
CLI：[cli.php](../../../extend/crud/cli.php)（只写文件）；写库用 `php think crud:generate`（[app/admin/command/CrudGenerate.php](../../../app/admin/command/CrudGenerate.php)）。

前端 API：[web/src/apis/dev/curd.ts](../../web/src/apis/dev/curd.md)；界面：[web/src/views/dev/curd/index.md](../../web/src/views/dev/curd/index.md)。

## 作用

按 JSON 配置生成主实体 CRUD（PHP + Vue），可选建表、写权限/菜单。模板会引用 `ga-page` / `ga-table` / `ga-search-bar` / `ga-status-tag` / `ga-select-remote`。

## 推荐使用场景

- 标准列表+表单模块。
- 复杂页手写 `web/src/views/`，对照 [config/index.vue](../../../web/src/views/system/config/index.vue)。

## 要点

独立入口：`php extend/crud/cli.php <config.json> [--dry-run] [--force] [--no-auth]`，**不写权限库**。  
Think 命令：`php think crud:generate <config.json>` 可写盘 + Schema + AuthInstaller。

UI 字段渲染扩展：[extend/crud/generator/ui/ADDING.md](../../../extend/crud/generator/ui/ADDING.md)。

## 已使用场景

- 后台页 [web/src/views/dev/curd/index.vue](../../../web/src/views/dev/curd/index.vue)
- 控制器 [app/admin/controller/dev/curd/Index.php](../../../app/admin/controller/dev/curd/Index.php)

## 逃生舱

手写 ThinkPHP 控制器与 Vue 页，不跑生成器。见 [escape-hatches.md](../../escape-hatches.md)。
