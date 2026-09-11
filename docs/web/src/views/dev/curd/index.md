# CRUD 工作台

源码：[web/src/views/dev/curd/index.vue](../../../../../../web/src/views/dev/curd/index.vue)  
[EntryPage.vue](../../../../../../web/src/views/dev/curd/EntryPage.vue) / [BuildPage.vue](../../../../../../web/src/views/dev/curd/BuildPage.vue)

## 作用

开发态界面：选历史/表/空配置 → 填字段 → 调用 [curd.ts](../../../apis/dev/curd.md) 预览或生成。外壳是 `el-card`，不用 `ga-page`。

## 推荐使用场景

- 本机/内网给开发者生成模块。
- 不要把它当最终业务页模板；生成结果在 `web/src/views/<模块>/`。

## 要点

状态在 [pinia/dev/curd.ts](../../../../../../web/src/pinia/dev/curd.ts)。`step === 'entry' | …` 切换入口与工作台。

## 已使用场景

本文件即入口：[index.vue](../../../../../../web/src/views/dev/curd/index.vue)。菜单由权限规则指向该 component。

## 逃生舱

用 `php think crud:generate` / `php extend/crud/cli.php`，不打开本页。见 [extend/crud](../../../../../extend/crud/index.md)。
