# -*- coding: utf-8 -*-
"""实测 SchemaInstaller：已存在表的字段同步（add / update / delete / unchanged）"""
from __future__ import annotations

import json
import time
import urllib.error
import urllib.request
from pathlib import Path

import pymysql

ROOT = Path(r'e:\www\github\lascyb\GlassAdmin')
BASE = 'http://192.168.22.129:8080/admin'
TOKEN = '07ba4e31355058ff83c70be10ae88576b1d4831505e01e38ea8e26c06481f59d'
PROBE = 'z_crud_schema_probe'


def load_env():
    env = {}
    for line in (ROOT / '.env').read_text(encoding='utf-8').splitlines():
        line = line.strip()
        if not line or line.startswith('#') or '=' not in line:
            continue
        k, v = line.split('=', 1)
        env[k.strip()] = v.strip().strip('"').strip("'")
    return env


def db():
    env = load_env()
    return pymysql.connect(
        host=env['DB_HOST'],
        port=int(env.get('DB_PORT', 3306)),
        user=env['DB_USER'],
        password=env['DB_PASS'],
        database=env['DB_NAME'],
        charset='utf8mb4',
        autocommit=True,
        cursorclass=pymysql.cursors.DictCursor,
    )


def api(method: str, path: str, body: dict | None = None):
    data = None
    headers = {
        'Authorization': 'Bearer ' + TOKEN,
        'Accept': 'application/json',
    }
    if body is not None:
        data = json.dumps(body, ensure_ascii=False).encode('utf-8')
        headers['Content-Type'] = 'application/json'
    req = urllib.request.Request(BASE + '/' + path, data=data, headers=headers, method=method)
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            return json.loads(r.read().decode())
    except urllib.error.HTTPError as e:
        raw = e.read().decode('utf-8', errors='replace')
        try:
            return json.loads(raw)
        except Exception:
            raise RuntimeError(f'HTTP {e.code}: {raw[:500]}') from e


def base_field(prop, label, col, **layers):
    f = {
        'prop': prop,
        'label': label,
        'column': col,
        'relation': {'type': 'belongs_to', 'enabled': False},
        'list': layers.get('list') or {'enabled': True, 'render': 'text', 'minWidth': 120},
        'search': layers.get('search') or {'enabled': False, 'keyword': False, 'advanced': False},
        'form': layers.get('form') or {'enabled': True, 'render': 'input'},
    }
    return f


def make_config(fields: list[dict]) -> dict:
    return {
        'table': PROBE,
        'menus': [{'type': 'view', 'path': 'probe', 'title': 'SchemaProbe', 'enabled': True, 'icon': 'el:Document'}],
        'paths': {
            'controller': 'test/z/Probe.php',
            'model': 'test/z/Probe.php',
            'validate': 'test/z/Probe.php',
            'apis': 'test/z/probe.ts',
            'viewsDir': 'test/z/probe/',
        },
        'generateAuth': False,
        'writeMode': 'skip',
        'fields': fields,
    }


PK = base_field(
    'id',
    'ID',
    {
        'type': 'bigint',
        'unsigned': True,
        'nullable': False,
        'autoIncrement': True,
        'primary': True,
        'enabled': True,
        'comment': 'ID',
    },
    list={'enabled': True, 'render': 'text', 'width': 72},
    form={'enabled': False, 'render': 'input'},
)


def columns(conn):
    with conn.cursor() as cur:
        cur.execute(f'SHOW FULL COLUMNS FROM `{PROBE}`')
        return {r['Field']: r for r in cur.fetchall()}


def reset_table(conn):
    with conn.cursor() as cur:
        cur.execute(f'DROP TABLE IF EXISTS `{PROBE}`')
        cur.execute(
            f"""
            CREATE TABLE `{PROBE}` (
              `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
              `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '标题',
              `old_col` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '待删',
              PRIMARY KEY (`id`) USING BTREE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            """
        )


def preview(config: dict):
    return api(
        'POST',
        'dev.curd.Index/generate',
        {'config': config, 'dryRun': True, 'noAuth': True, 'saveHistory': False, 'force': False},
    )


def install(config: dict):
    return api(
        'POST',
        'dev.curd.Index/generate',
        {'config': config, 'dryRun': False, 'noAuth': True, 'saveHistory': False, 'force': True},
    )


def schema_of(res: dict):
    data = res.get('data') or {}
    rows = data.get('schema') or []
    for s in rows:
        if s.get('table') == PROBE:
            return s
    return rows[0] if rows else {}


def assert_true(cond, msg):
    if not cond:
        raise AssertionError(msg)


def main():
    conn = db()
    try:
        print('=== 0) 检查代码是否已生效（旧状态 exists/skipped 应消失）===')
        reset_table(conn)
        cfg_same = make_config(
            [
                PK,
                base_field(
                    'title',
                    '标题',
                    {'type': 'varchar', 'length': 100, 'nullable': True, 'enabled': True, 'comment': '标题'},
                ),
                base_field(
                    'old_col',
                    '待删',
                    {'type': 'varchar', 'length': 50, 'nullable': True, 'enabled': True, 'comment': '待删'},
                ),
            ]
        )
        res = preview(cfg_same)
        assert_true(res.get('code') == 0, f'preview same failed: {res}')
        s = schema_of(res)
        print(' unchanged preview:', s.get('status'), '| sql:', (s.get('sql') or '')[:80])
        assert_true(
            s.get('status') in ('unchanged', 'will_alter'),
            f'unexpected status (code not deployed?): {s.get("status")}',
        )
        if s.get('status') == 'will_alter':
            print(' NOTE: fingerprint 与库不完全一致，将作为变更路径继续测')
        elif s.get('status') in ('exists', 'skipped'):
            raise AssertionError('远端仍是旧 SchemaInstaller（表存在即跳过），请确认代码已部署到 API 服务器')

        print('\n=== 1) dry-run：变更 title 长度 + 新增 new_col + 删除 old_col ===')
        cfg_diff = make_config(
            [
                PK,
                base_field(
                    'title',
                    '标题',
                    {'type': 'varchar', 'length': 255, 'nullable': True, 'enabled': True, 'comment': '标题'},
                ),
                base_field(
                    'new_col',
                    '新列',
                    {'type': 'varchar', 'length': 80, 'nullable': True, 'enabled': True, 'comment': '新列'},
                ),
            ]
        )
        res = preview(cfg_diff)
        assert_true(res.get('code') == 0, f'preview diff failed: {res}')
        s = schema_of(res)
        print(' status:', s.get('status'))
        print(' sql:\n', s.get('sql'))
        assert_true(s.get('status') == 'will_alter', f'expect will_alter, got {s.get("status")}')
        sql = s.get('sql') or ''
        assert_true('new_col' in sql and 'ADD COLUMN' in sql, 'missing ADD new_col')
        assert_true('_update_' in sql and 'title' in sql, 'missing title _update_ rename')
        assert_true('_delete_' in sql and 'old_col' in sql, 'missing old_col _delete_ rename')

        print('\n=== 2) 真实执行同步 ===')
        res = install(cfg_diff)
        assert_true(res.get('code') == 0, f'install failed: {res}')
        s = schema_of(res)
        print(' status:', s.get('status'))
        print(' sql:\n', s.get('sql'))
        assert_true(s.get('status') == 'altered', f'expect altered, got {s.get("status")} / {res}')

        cols = columns(conn)
        names = sorted(cols.keys())
        print(' columns after:', names)
        assert_true('title' in cols, 'title missing')
        assert_true('new_col' in cols, 'new_col missing')
        assert_true('old_col' not in cols, 'old_col should be renamed')
        assert_true(any(n.startswith('title_update_') for n in names), 'title_update_* missing')
        assert_true(any(n.startswith('old_col_delete_') for n in names), 'old_col_delete_* missing')
        title_type = (cols['title']['Type'] or '').lower()
        assert_true('varchar(255)' in title_type, f'title type should be varchar(255), got {title_type}')

        print('\n=== 3) 再次 dry-run 同配置 → unchanged ===')
        res = preview(cfg_diff)
        s = schema_of(res)
        print(' status:', s.get('status'), 'sql:', repr(s.get('sql')))
        assert_true(s.get('status') == 'unchanged', f'expect unchanged, got {s.get("status")} sql={s.get("sql")}')

        print('\n=== 4) 清理探针表 ===')
        with conn.cursor() as cur:
            cur.execute(f'DROP TABLE IF EXISTS `{PROBE}`')
        print('OK all schema sync tests passed')
    finally:
        conn.close()


if __name__ == '__main__':
    main()
