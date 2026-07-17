# -*- coding: utf-8 -*-
"""生成 CRUD 字段类型单元测试样例 JSON（对齐前端 UI assembleConfig 输出）"""
from __future__ import annotations

import json
from pathlib import Path

OUT = Path(__file__).resolve().parent

STATUS_OPTIONS = [
    {"value": 1, "label": "启用", "type": "success"},
    {"value": 0, "label": "禁用", "type": "info"},
]
SELECT_OPTIONS = [
    {"value": 1, "label": "选项一", "type": "info"},
    {"value": 2, "label": "选项二", "type": "warning"},
    {"value": 3, "label": "选项三", "type": "danger"},
]
MULTI_OPTIONS = [
    {"value": "a", "label": "选项 A"},
    {"value": "b", "label": "选项 B"},
    {"value": "c", "label": "选项 C"},
]


def pk_field() -> dict:
    return {
        "prop": "id",
        "label": "ID",
        "uiType": "primary_key",
        "column": {
            "type": "bigint",
            "unsigned": True,
            "nullable": False,
            "autoIncrement": True,
            "primary": True,
            "enabled": True,
        },
        "relation": {"type": "belongs_to", "enabled": False},
        "list": {"render": "number", "width": 80, "enabled": True},
        "search": {"keyword": False, "advanced": False, "enabled": False},
        "form": {"render": "input", "enabled": False},
    }


def disabled_relation() -> dict:
    return {"type": "belongs_to", "enabled": False}


def make_config(
    *,
    slug: str,
    entity: str,
    title: str,
    fields: list[dict],
    options: dict | None = None,
) -> dict:
    """slug: 文件名/表名后缀，如 text、remote_select"""
    file_base = slug  # apis / views 用下划线
    cfg: dict = {
        "table": f"test_ft_{slug}",
        "menus": [
            {
                "type": "menu",
                "path": "test",
                "title": "字段类型测试",
                "icon": "el:Experiment",
                "sort": 90,
                "enabled": True,
            },
            {
                "type": "menu",
                "path": "ft",
                "title": "字段类型",
                "enabled": True,
            },
            {
                "type": "view",
                "path": file_base,
                "title": title,
                "icon": "el:Document",
                "enabled": True,
            },
        ],
        "paths": {
            "controller": f"test/ft/{entity}.php",
            "model": f"test/ft/{entity}.php",
            "validate": f"test/ft/{entity}.php",
            "apis": f"test/ft/{file_base}.ts",
            "viewsDir": f"test/ft/{file_base}/",
        },
        "generateAuth": True,
        "writeMode": "overwrite",
        "fields": fields,
    }
    if options:
        cfg["options"] = options
    return cfg


CASES: list[tuple[str, str, str, list[dict], dict | None]] = []


def add(slug: str, entity: str, title: str, target: dict, options: dict | None = None):
    fields = [pk_field(), target] if target.get("prop") != "id" else [target]
    CASES.append((slug, entity, title, fields, options))


# —— 文本 ——
add(
    "text",
    "Text",
    "单行文本",
    {
        "prop": "title",
        "label": "标题",
        "uiType": "text",
        "column": {"type": "varchar", "length": 255, "nullable": True, "enabled": True},
        "relation": disabled_relation(),
        "list": {"render": "text", "minWidth": 120, "enabled": True},
        "search": {"keyword": True, "advanced": False, "enabled": True, "match": "fuzzy"},
        "form": {"render": "input", "enabled": True},
    },
)
add(
    "textarea",
    "Textarea",
    "多行文本",
    {
        "prop": "content",
        "label": "内容",
        "uiType": "textarea",
        "column": {"type": "text", "nullable": True, "enabled": True},
        "relation": disabled_relation(),
        "list": {"render": "text", "enabled": False},
        "search": {"keyword": False, "advanced": False, "enabled": False},
        "form": {"render": "textarea", "maxLength": 2000, "enabled": True},
    },
)
add(
    "password",
    "Password",
    "密码",
    {
        "prop": "password",
        "label": "密码",
        "uiType": "password",
        "column": {"type": "varchar", "length": 255, "nullable": True, "enabled": True},
        "relation": disabled_relation(),
        "list": {"render": "text", "minWidth": 120, "enabled": True},
        "search": {"keyword": True, "advanced": False, "enabled": True, "match": "fuzzy"},
        "form": {"render": "password", "enabled": True},
    },
)

# —— 数值 ——
add(
    "number",
    "Number",
    "数字",
    {
        "prop": "quantity",
        "label": "数量",
        "uiType": "number",
        "column": {"type": "int", "nullable": False, "default": 0, "enabled": True},
        "relation": disabled_relation(),
        "list": {"render": "number", "width": 80, "enabled": True},
        "search": {"keyword": False, "advanced": True, "enabled": True, "render": "number_range"},
        "form": {"render": "number", "numberMin": 0, "numberMax": 99999, "enabled": True},
    },
)
add(
    "amount",
    "Amount",
    "金额",
    {
        "prop": "amount",
        "label": "金额",
        "uiType": "amount",
        "column": {
            "type": "decimal",
            "precision": 12,
            "scale": 2,
            "nullable": False,
            "default": 0,
            "enabled": True,
        },
        "relation": disabled_relation(),
        "list": {"render": "number", "width": 100, "enabled": True},
        "search": {"keyword": False, "advanced": True, "enabled": True, "render": "number_range"},
        "form": {
            "render": "number",
            "numberMin": 0,
            "numberMax": 9999999,
            "numberStep": 0.01,
            "enabled": True,
        },
    },
)

# —— 选择 ——
add(
    "switch",
    "SwitchField",
    "开关",
    {
        "prop": "is_top",
        "label": "置顶",
        "uiType": "switch",
        "column": {"type": "tinyint", "nullable": False, "default": 0, "enabled": True},
        "relation": disabled_relation(),
        "list": {"render": "bool", "width": 72, "enabled": True},
        "search": {"keyword": False, "advanced": True, "enabled": True, "render": "bool"},
        "form": {"render": "switch", "enabled": True},
    },
)
add(
    "radio",
    "Radio",
    "单选",
    {
        "prop": "status",
        "label": "状态",
        "uiType": "radio",
        "column": {"type": "tinyint", "nullable": False, "default": 1, "enabled": True},
        "relation": disabled_relation(),
        "list": {"render": "status", "width": 88, "enabled": True},
        "search": {"keyword": False, "advanced": True, "enabled": True, "render": "select"},
        "form": {"render": "radio", "enabled": True},
        "ext": {"enum": {"key": "STATUS_MAP"}},
    },
    {"STATUS_MAP": STATUS_OPTIONS},
)
add(
    "checkbox",
    "Checkbox",
    "多选",
    {
        "prop": "flags",
        "label": "标记",
        "uiType": "checkbox",
        "column": {"type": "json", "nullable": True, "enabled": True},
        "relation": disabled_relation(),
        "list": {"render": "tags", "minWidth": 160, "enabled": True},
        "search": {"keyword": False, "advanced": True, "enabled": True},
        "form": {"render": "checkbox", "enabled": True},
        "ext": {"enum": {"key": "FLAGS_OPTIONS"}},
    },
    {"FLAGS_OPTIONS": MULTI_OPTIONS},
)
add(
    "select",
    "Select",
    "下拉单选",
    {
        "prop": "level",
        "label": "等级",
        "uiType": "select",
        "column": {"type": "tinyint", "nullable": False, "default": 1, "enabled": True},
        "relation": disabled_relation(),
        "list": {"render": "tag", "width": 88, "enabled": True},
        "search": {"keyword": False, "advanced": True, "enabled": True, "render": "select"},
        "form": {"render": "select", "enabled": True},
        "ext": {"enum": {"key": "LEVEL_MAP"}},
    },
    {"LEVEL_MAP": SELECT_OPTIONS},
)
add(
    "multi_select",
    "MultiSelect",
    "下拉多选",
    {
        "prop": "extra",
        "label": "附加项",
        "uiType": "multi_select",
        "column": {"type": "json", "nullable": True, "enabled": True},
        "relation": disabled_relation(),
        "list": {"render": "tags", "minWidth": 160, "enabled": True},
        "search": {"keyword": False, "advanced": True, "enabled": True},
        "form": {"render": "multi_select", "enabled": True},
        "ext": {"enum": {"key": "EXTRA_OPTIONS"}},
    },
    {"EXTRA_OPTIONS": MULTI_OPTIONS},
)
add(
    "remote_select",
    "RemoteSelect",
    "远程下拉",
    {
        "prop": "category_id",
        "label": "分类",
        "uiType": "remote_select",
        "column": {
            "type": "bigint",
            "unsigned": True,
            "nullable": True,
            "default": None,
            "enabled": True,
        },
        "relation": {"type": "belongs_to", "enabled": True},
        "list": {"render": "tag", "relationProp": "category.title", "width": 110, "enabled": True},
        "search": {
            "keyword": False,
            "advanced": True,
            "enabled": True,
            "render": "remote_select",
        },
        "form": {"render": "remote_select", "enabled": True},
            "ext": {
            "remote": {
                "api": "test.ft.Text/list",
                "valueField": "id",
                "labelField": "title",
                "table": "test_ft_text",
            }
        },
    },
)
add(
    "remote_multi_select",
    "RemoteMultiSelect",
    "远程多选",
    {
        "prop": "tag_ids",
        "label": "标签",
        "uiType": "remote_multi_select",
        "column": {"type": "varchar", "length": 255, "enabled": False},
        "relation": {
            "type": "belongs_to_many",
            "enabled": True,
            "pivotTable": "test_ft_remote_multi_select_tag",
            "foreignKey": "remote_multi_select_id",
            "relatedKey": "tag_id",
        },
        "list": {"render": "tags", "relationProp": "tags", "minWidth": 160, "enabled": True},
        "search": {
            "keyword": False,
            "advanced": True,
            "enabled": True,
            "render": "remote_multi_select",
        },
        "form": {"render": "remote_multi_select", "enabled": True},
        "ext": {
            "remote": {
                "api": "test.ft.Text/list",
                "valueField": "id",
                "labelField": "title",
                "table": "test_ft_text",
            }
        },
    },
)

# —— 日期时间 ——
add(
    "date",
    "Date",
    "日期",
    {
        "prop": "biz_date",
        "label": "业务日期",
        "uiType": "date",
        "column": {"type": "date", "nullable": True, "default": None, "enabled": True},
        "relation": disabled_relation(),
        "list": {"render": "text", "width": 120, "enabled": True},
        "search": {"keyword": False, "advanced": True, "enabled": True, "render": "date_range"},
        "form": {"render": "date", "enabled": True},
    },
)
add(
    "datetime",
    "Datetime",
    "日期时间",
    {
        "prop": "publish_time",
        "label": "发布时间",
        "uiType": "datetime",
        "column": {"type": "bigint", "nullable": True, "default": None, "enabled": True},
        "relation": disabled_relation(),
        "list": {"render": "datetime", "width": 170, "enabled": True},
        "search": {
            "keyword": False,
            "advanced": True,
            "enabled": True,
            "render": "datetime_range",
        },
        "form": {"render": "datetime", "enabled": True},
    },
)
add(
    "time",
    "Time",
    "时间",
    {
        "prop": "alarm_time",
        "label": "提醒时间",
        "uiType": "time",
        "column": {"type": "bigint", "nullable": True, "default": None, "enabled": True},
        "relation": disabled_relation(),
        "list": {"render": "datetime", "width": 170, "enabled": True},
        "search": {
            "keyword": False,
            "advanced": True,
            "enabled": True,
            "render": "datetime_range",
        },
        "form": {"render": "time", "enabled": True},
    },
)

# —— 媒体与其它 ——
add(
    "image",
    "Image",
    "图片",
    {
        "prop": "cover",
        "label": "封面",
        "uiType": "image",
        "column": {
            "type": "varchar",
            "length": 255,
            "nullable": True,
            "default": None,
            "enabled": True,
        },
        "relation": disabled_relation(),
        "list": {"render": "image", "width": 88, "enabled": True},
        "search": {"keyword": False, "advanced": False, "enabled": False},
        "form": {"render": "image", "enabled": True},
    },
)
add(
    "file",
    "File",
    "文件",
    {
        "prop": "attachment",
        "label": "附件",
        "uiType": "file",
        "column": {
            "type": "varchar",
            "length": 255,
            "nullable": True,
            "default": None,
            "enabled": True,
        },
        "relation": disabled_relation(),
        "list": {"render": "image", "width": 88, "enabled": True},
        "search": {"keyword": False, "advanced": False, "enabled": False},
        "form": {"render": "file", "enabled": True},
    },
)
add(
    "color",
    "Color",
    "颜色",
    {
        "prop": "color",
        "label": "颜色",
        "uiType": "color",
        "column": {"type": "varchar", "length": 255, "nullable": True, "enabled": True},
        "relation": disabled_relation(),
        "list": {"render": "color", "width": 88, "enabled": True},
        "search": {"keyword": False, "advanced": False, "enabled": False},
        "form": {"render": "color", "enabled": True},
    },
)
add(
    "custom",
    "Custom",
    "自定义",
    {
        "prop": "custom_val",
        "label": "自定义值",
        "uiType": "custom",
        "column": {"type": "varchar", "length": 255, "nullable": True, "enabled": True},
        "relation": disabled_relation(),
        "list": {"render": "text", "enabled": False},
        "search": {"keyword": False, "advanced": False, "enabled": False},
        "form": {"render": "custom", "enabled": True},
    },
)

# —— 系统 ——
add(
    "primary_key",
    "PrimaryKey",
    "自增主键",
    {
        "prop": "id",
        "label": "ID",
        "uiType": "primary_key",
        "column": {
            "type": "bigint",
            "unsigned": True,
            "primary": True,
            "autoIncrement": True,
            "nullable": False,
            "enabled": True,
        },
        "relation": disabled_relation(),
        "list": {"render": "number", "width": 80, "enabled": True},
        "search": {"keyword": False, "advanced": False, "enabled": False},
        "form": {"render": "input", "enabled": False},
    },
)
add(
    "uuid",
    "Uuid",
    "UUID",
    {
        "prop": "uuid",
        "label": "UUID",
        "uiType": "uuid",
        "column": {
            "type": "varchar",
            "length": 36,
            "nullable": False,
            "unique": True,
            "enabled": True,
        },
        "relation": disabled_relation(),
        "list": {"render": "text", "minWidth": 280, "enabled": True},
        "search": {"keyword": True, "advanced": False, "enabled": True, "match": "fuzzy"},
        "form": {"render": "input", "enabled": False},
    },
)
add(
    "create_time",
    "CreateTime",
    "创建时间",
    {
        "prop": "create_time",
        "label": "创建时间",
        "uiType": "create_time",
        "column": {"type": "bigint", "nullable": False, "default": None, "enabled": True},
        "relation": disabled_relation(),
        "list": {"render": "datetime", "width": 170, "enabled": True},
        "search": {
            "keyword": False,
            "advanced": True,
            "enabled": True,
            "render": "datetime_range",
        },
        "form": {"render": "input", "enabled": False},
    },
)
add(
    "update_time",
    "UpdateTime",
    "更新时间",
    {
        "prop": "update_time",
        "label": "更新时间",
        "uiType": "update_time",
        "column": {"type": "bigint", "nullable": False, "default": None, "enabled": True},
        "relation": disabled_relation(),
        "list": {"render": "datetime", "width": 170, "enabled": True},
        "search": {
            "keyword": False,
            "advanced": True,
            "enabled": True,
            "render": "datetime_range",
        },
        "form": {"render": "input", "enabled": False},
    },
)


def main() -> None:
    OUT.mkdir(parents=True, exist_ok=True)
    manifest = []
    for slug, entity, title, fields, options in CASES:
        cfg = make_config(slug=slug, entity=entity, title=title, fields=fields, options=options)
        path = OUT / f"{slug}.json"
        path.write_text(
            json.dumps(cfg, ensure_ascii=False, indent=2) + "\n",
            encoding="utf-8",
        )
        manifest.append(
            {
                "file": f"{slug}.json",
                "uiType": slug,
                "table": cfg["table"],
                "title": title,
                "command": f"php think crud:generate extend/crud/samples/test/{slug}.json --dry-run",
            }
        )
        print(f"wrote {path}")

    # 清单：便于批量 dry-run，仍是合法 JSON
    (OUT / "_manifest.json").write_text(
        json.dumps(
            {
                "description": "字段类型单元测试样例清单（对齐前端 UI CrudGenerateConfig）",
                "generateAuth": True,
                "writeMode": "overwrite",
                "cases": manifest,
            },
            ensure_ascii=False,
            indent=2,
        )
        + "\n",
        encoding="utf-8",
    )
    print(f"total {len(CASES)} cases + _manifest.json")


if __name__ == "__main__":
    main()
