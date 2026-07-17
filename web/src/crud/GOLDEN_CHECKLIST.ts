/**
 * CRUD 生成自检清单（P0）
 *
 * 字段类型样例：extend/crud/samples/test/（php think crud:generate …）
 * 检查日期：以仓库当前实现为准
 *
 * ## 主实体产物（须齐全）
 * [x] Model / Validate / Controller
 * [x] web apis + views（index / edit，枚举含 constants）
 *
 * ## API 动作名
 * [x] create / read / list / update / delete（前后端一致）
 * [x] image/file 扩展：uploadXxx
 *
 * ## 字段启用范围
 * [x] list/read 查询按 list.enabled / form.enabled 裁剪 field()
 * [x] create/update 仅写入 form.enabled（多对多走 sync）
 *
 * ## 公共助手
 * [x] app/common/helper/CrudHelper.php
 *
 * ## 远程关联
 * [x] belongs_to / belongs_to_many + with / sync
 * [x] 样例远程源：test.ft.Text/list（valueField=id, labelField=title）
 */
