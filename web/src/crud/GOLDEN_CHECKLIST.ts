/**
 * CRUD 金样自检清单（P0-5）
 *
 * 金样路径：example/data
 * 输入契约：web/src/crud/schemas/example-data.ts
 * 检查日期：以仓库当前实现为准
 *
 * ## 主实体产物（须齐全）
 * [x] app/admin/model/example/Data.php
 * [x] app/admin/controller/example/Data.php  — list/read/create/update/delete + uploadCover
 * [x] app/admin/validate/example/Data.php    — sceneCreate / sceneUpdate
 * [x] web/src/apis/example/data.ts          — list/read/create/update/delete
 * [x] web/src/views/example/data/index.vue
 * [x] web/src/views/example/data/edit.vue
 * [x] web/src/views/example/data/constants.ts — STATUS_MAP / LEVEL_MAP / EXTRA_OPTIONS
 *
 * ## API 动作名
 * [x] create / read / list / update / delete（前后端一致）
 * [x] image 扩展：uploadCover（有 cover 字段时）
 *
 * ## 字段覆盖（EXAMPLE_DATA_SCHEMA，分层：column/relation/list/search/form）
 * [x] column.type(MySQL): bigint/int/tinyint/decimal/varchar/text/date/json
 * [x] column 参数: length/precision/scale/unsigned/nullable/default/autoIncrement/primary/unique
 * [x] primary 与 autoIncrement 分离（id）
 * [x] FORM/LIST_RENDER_FIELD_TYPE + FORM_TO_SEARCH_RENDER 渲染→类型/搜索映射
 * [x] form.render(FormRenderType): input/textarea/number/switch/radio/select/remote_select/
 *            remote_multi_select/multi_select/date/datetime/color/image/readonly/hidden
 * [x] search.render(SearchRenderType): 与表单分离；可省略，按 FORM_TO_SEARCH_RENDER 推断
 *            input/number_range/select/radio/remote_select/remote_multi_select/date_range/datetime_range/bool
 * [x] list.render: text/number/status/tag/tags/color/image/datetime/bool
 * [x] search.mode: keyword(title,remark) + advanced(code,category,tag,amount,status,level,is_top,publish_time,expire_date)
 * [x] relation: belongs_to(category) / belongs_to_many(tags，无主表物理列)
 *
 * ## 关联依赖（不随主实体生成，须已存在）
 * [x] model/controller/apis: example.Category — list
 * [x] model/controller/apis: example.Tag — list
 * [x] 中间表 example_data_tag + Model::syncTags / tagIdsFor
 * [x] 前端 ga-select-remote + CATEGORY_LIST_URL / TAG_LIST_URL
 *
 * ## 权限（glass_admin.sql）
 * [x] example.Data/{list,read,create,update,delete,uploadCover}
 * [x] example.Category/list 、 example.Tag/list
 * [x] frontend menu/view/btn + frontend_api 绑定
 *
 * ## 本轮已修缺口
 * - Controller 去掉未在 schema/apis 中的独立 title 筛选（改由 keyword 覆盖）
 * - Validate 补齐 extra 的 array 规则
 * - Controller 头注释与 API 约定对齐
 *
 * ## 生成器注意（非金样缺陷）
 * - delete 在 TS 中为保留字：apis 用 `export { remove as delete }`
 * - constants 仅在有 enum / 静态 options 时生成
 * - 关联目标 CRUD 不生成，只引用其 list
 */
export { };

