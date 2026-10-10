# curd.ts（生成器前端 API）

源码：[web/src/apis/dev/curd.ts](../../../../../web/src/apis/dev/curd.ts)

薄封装 `get`/`post`，前缀：

- `admin/dev.curd.Index` — 连接、表、schema、`generate`
- `admin/dev.curd.History` — 历史
- `admin/dev.curd.Remote` — 远程下拉数据源

## 作用

给 CRUD 工作台调生成/预览接口。`generate` 使用 `silent: true`、`timeout: 0`。

## 推荐使用场景

- 只在 [views/dev/curd](../../views/dev/curd/index.md) / [pinia/dev/curd.ts](../../../../../web/src/pinia/dev/curd.ts) 使用。
- 其它业务 API 放 `web/src/apis/system/`。

## 要点

`generate(config, { dryRun, force, noAuth, saveHistory })`。列连接、反推表、远程控制器/字段/中间表见源码导出函数。

## 已使用场景

- [web/src/pinia/dev/curd.ts](../../../../../web/src/pinia/dev/curd.ts)
- [web/src/views/dev/curd/index.vue](../../../../../web/src/views/dev/curd/index.vue)

## 逃生舱

直接 `post('admin/dev.curd.Index/generate', ...)` 或 CLI。见 [extend/crud](../../../../extend/crud/index.md)、[escape-hatches.md](../../../../escape-hatches.md)。
