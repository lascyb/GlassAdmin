# 新增渲染模块指引

按 **surface（form / list / search / filter）× 规范键** 增删控件。同一业务字段可在不同 surface 使用不同键，分别加文件。

## 通用步骤

1. 定规范键 `{key}`（与 tpl 文件名、`key()`、Registry 一致）
2. 加模板 `extend/crud/templates/{tplDir}/{key}.*`
3. 加渲染类 `extend/crud/generator/ui/{surface}/{Class}.php`：
   - 声明本控件 **全部** 实例属性
   - 实现 `fromField` / `validate` / `render`（后两者只用属性；不使用 traits）
4. 在 [`Registry.php`](Registry.php) 对应 MAP 注册一行
5. 若配置可能写旧名：在 [`resolve/`](resolve/) 对应 `*Key.php` 加 alias
6. 前端常量补选项（见下表）
7. **前端入参组件**：加 `web/src/views/dev/curd/components/render-params/{surface}/{Key}.vue`，并在 [`registry.ts`](../../../../web/src/views/dev/curd/components/render-params/registry.ts) 注册；别名选项可映射到同一规范键组件（不删下拉 alias）
8. 建议增加 `extend/crud/samples/test/{场景}.json` 并 `php extend/crud/cli.php … --dry-run`
9. 类注释写清：`tpl` / `params` / `alias`（见现有 `ui/form/*.php`）

生成前由 [`FieldUiValidator`](FieldUiValidator.php) 在 `Generator::build` 开头调用各 `Render::validate`。

字段配置 UI：[`FormConfigCard`](../../../../web/src/views/dev/curd/components/field-detail/FormConfigCard.vue) / [`ListConfigCard`](../../../../web/src/views/dev/curd/components/field-detail/ListConfigCard.vue) 通过 `<component :is>` 按 render 切换入参组件。

## Surface 对照表

| Surface | 规范键来源 | PHP 控制文件 | 模板 | 参数定义（前端） | 其它 |
|---------|------------|--------------|------|------------------|------|
| **form** | `form.render` → [`FormKey`](resolve/FormKey.php) | `ui/form/{Key}.php` | `templates/edit/field/{key}.vue.tpl` | [`form-render.ts`](../../../../web/src/views/dev/curd/constants/form-render.ts) + [`render-params/form`](../../../../web/src/views/dev/curd/components/render-params/form/) + `types/form-field-params.ts` | 默认搜索映射 → [`SearchInference`](../../field/SearchInference.php)；上传类进 `imageFields` → [`Context::fromConfig`](../../Context.php) |
| **list** | `list.render` → [`ListKey`](resolve/ListKey.php) | `ui/list/{Key}.php` | `templates/index/col/{key}.vue.tpl` | [`list-render.ts`](../../../../web/src/views/dev/curd/constants/list-render.ts) + [`render-params/list`](../../../../web/src/views/dev/curd/components/render-params/list/) | 关联列优先 relation，逻辑在 ListKey |
| **search** | `search.render` → [`SearchKey`](resolve/SearchKey.php) | `ui/search/{Key}.php` | `templates/index/search/{key}.vue.tpl` | [`search-render.ts`](../../../../web/src/views/dev/curd/constants/search-render.ts) + [`render-params/search`](../../../../web/src/views/dev/curd/components/render-params/search/) | 新形态若要带 advanced 状态/query，还需改 [`IndexFile`](../IndexFile.php) 中 advanced 组装段 |
| **filter** | [`FilterKey`](resolve/FilterKey.php)（多由 search 推导） | `ui/filter/{Key}.php` | `templates/controller/filter/{key}.php.tpl` | 通常无独立前端选项；与 search 键对齐 | `render()` 返回 `{params, filter}` |

字段模板默认值（选字段类型时自动带出各层）：[`web/.../constants/field.ts`](../../../../web/src/views/dev/curd/constants/field.ts)。

## 示例：新增 form=`slider`

1. `form-render.ts`：`FORM_RENDER_DEFS` 增加 `{ key: 'SLIDER', value: 'slider', label: '滑块', formDefaults: { numberMin: 0, numberMax: 100 } }`
2. `templates/edit/field/slider.vue.tpl`：写 Vue 片段（占位符 `<%prop%>` 等）
3. `ui/form/Slider.php`：声明属性 + `fromField` / `validate` / `render`；`key() => 'slider'`
4. `Registry.php` FORM MAP：`'slider' => FormSlider::class`
5. `render-params/form/Slider.vue` + `registry.ts` 的 `FORM_PARAM_COMPONENTS`
6. 若无需默认搜索：跳过；若需要：`SearchInference::formToSearchRender` 增加 `'slider' => 'number_range'`
7. （可选）`field.ts` inference 与 sample json
8. dry-run 验证

## 示例：新增 list=`progress`

1. `list-render.ts` 增加选项
2. `templates/index/col/progress.vue.tpl`
3. `ui/list/Progress.php`（属性 + fromField/validate/render）+ Registry LIST
4. `render-params/list/Progress.vue` + `LIST_PARAM_COMPONENTS`
5. ListKey 一般无需改（未知键会落到 `text`；显式 `progress` 需在 ListKey 白名单或直接返回 `$lr`）

## 示例：新增 search + filter=`keyword_tag`

1. `search-render.ts` + `templates/index/search/keyword_tag.vue.tpl` + `ui/search/KeywordTag.php` + Registry SEARCH
2. `SearchKey` 识别该键
3. `render-params/search/KeywordTag.vue` + `SEARCH_PARAM_COMPONENTS`
4. `templates/controller/filter/keyword_tag.php.tpl` + `ui/filter/KeywordTag.php` + Registry FILTER
5. `FilterKey` 与 search 对齐
6. **同步** `IndexFile` advanced 的 `advLines` / `queryLines`（新 query 形态时）

## 相关工具类（一般不必为新控件改）

| 类 | 何时改 |
|----|--------|
| [`FieldMeta`](../../field/FieldMeta.php) | 新配置键 / 新判定（新 `ext.*`、新 column 语义） |
| [`Naming`](../../field/Naming.php) | 新命名规则 |
| [`SearchInference`](../../field/SearchInference.php) | form → 默认 search |
| [`Context`](../../Context.php) | 新字段分组桶（如再加 `videoFields`） |
| [`FieldUiValidator`](FieldUiValidator.php) | 一般不用改（走 Registry） |
| [`render-params/registry.ts`](../../../../web/src/views/dev/curd/components/render-params/registry.ts) | 新规范键入参组件注册 / alias 映射 |

## 类注释约定

```php
/**
 * Slider 表单 UI：slider
 *
 * tpl: edit/field/slider.vue.tpl
 * params: form.required, form.numberMin, form.numberMax, label, prop
 * alias: (FormKey) 无
 */
```
