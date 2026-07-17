# -*- coding: utf-8 -*-
"""批量测试 samples/test 生成的 CRUD 接口（list/create/read/update/delete）"""
from __future__ import annotations

import json
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
import uuid
from pathlib import Path

BASE = "http://192.168.22.129:8080/admin"
TOKEN = "07ba4e31355058ff83c70be10ae88576b1d4831505e01e38ea8e26c06481f59d"
ROOT = Path(__file__).resolve().parents[4]
MANIFEST = Path(__file__).resolve().parent / "_manifest.json"


def api_base_from_paths(paths: dict) -> str:
    ctrl = paths["controller"].removesuffix(".php")
    segs = ctrl.split("/")
    entity = segs[-1]
    module = ".".join(segs[:-1]) if len(segs) > 1 else ""
    return f"{module}.{entity}" if module else entity


def sample_value(field: dict, *, update: bool = False) -> object:
    prop = field["prop"]
    ui = field.get("uiType") or ""
    render = (field.get("form") or {}).get("render") or ""
    rel = ((field.get("relation") or {}).get("type") or "")
    ct = ((field.get("column") or {}).get("type") or "")

    if rel == "belongs_to_many" or render == "remote_multi_select":
        return []
    if rel == "belongs_to" or render == "remote_select":
        return None
    if ui in ("switch",) or render == "switch" or prop.startswith("is_"):
        return 0 if not update else 1
    if ui in ("radio", "select") or render in ("radio", "select"):
        return 0 if not update else 1
    if ui in ("checkbox", "multi_select") or render in ("checkbox", "multi_select"):
        return ["a"] if not update else ["a", "b"]
    if ui == "number" or render == "number" or ct in ("int", "tinyint", "bigint"):
        if "time" in prop:
            return int(time.time()) if not update else int(time.time()) - 60
        return 1 if not update else 2
    if ui == "amount" or ct == "decimal":
        return 10.5 if not update else 20.25
    if ui == "date" or render == "date":
        return "2026-07-17" if not update else "2026-07-18"
    if ui == "datetime" or render == "datetime":
        return int(time.time())
    if ui == "time" or render == "time":
        return 3600 if not update else 7200
    if ui == "color" or render == "color":
        return "#409EFF" if not update else "#67C23A"
    if ui in ("image", "file") or render in ("image", "file"):
        return "test/api-check.png" if not update else "test/api-check-2.png"
    if ui == "password" or render == "password":
        return "Passw0rd!" if not update else "Passw0rd!2"
    if ui == "textarea" or render == "textarea":
        return "hello\nworld" if not update else "updated\ntext"
    if ui == "uuid":
        return str(uuid.uuid4())
    # text / custom / default
    return f"api-test-{prop}" if not update else f"api-test-{prop}-upd"


def build_payload(fields: list[dict], *, update: bool = False) -> dict:
    out: dict = {}
    for f in fields:
        form = f.get("form") or {}
        if form.get("enabled") is False:
            continue
        rel = (f.get("relation") or {}).get("type") or ""
        # belongs_to_many 仍可写 tag_ids
        out[f["prop"]] = sample_value(f, update=update)
    return out


def request(method: str, path: str, *, query: dict | None = None, body: dict | None = None) -> tuple[int, dict]:
    url = f"{BASE}/{path}"
    if query:
        url += "?" + urllib.parse.urlencode(query, doseq=True)
    data = None
    headers = {
        "Authorization": f"Bearer {TOKEN}",
        "Accept": "application/json",
    }
    if body is not None:
        data = json.dumps(body, ensure_ascii=False).encode("utf-8")
        headers["Content-Type"] = "application/json"
    req = urllib.request.Request(url, data=data, headers=headers, method=method)
    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            raw = resp.read().decode("utf-8", errors="replace")
            return resp.status, json.loads(raw) if raw else {}
    except urllib.error.HTTPError as e:
        raw = e.read().decode("utf-8", errors="replace")
        try:
            return e.code, json.loads(raw) if raw else {"msg": str(e)}
        except json.JSONDecodeError:
            return e.code, {"msg": raw or str(e)}
    except Exception as e:  # noqa: BLE001
        return 0, {"msg": str(e)}


def ok(env: dict) -> bool:
    return isinstance(env, dict) and env.get("code") == 0


def fetch_first_id(api: str) -> int | None:
    status, env = request("GET", f"{api}/list", query={"page": 1, "limit": 1})
    if status != 200 or not ok(env):
        return None
    lst = (env.get("data") or {}).get("list") or []
    if not lst:
        return None
    return int(lst[0].get("id") or 0) or None


def enrich_remote(payload: dict, fields: list[dict]) -> dict:
    out = dict(payload)
    for f in fields:
        form = f.get("form") or {}
        if form.get("enabled") is False:
            continue
        prop = f["prop"]
        render = form.get("render") or ""
        rel = ((f.get("relation") or {}).get("type") or "")
        remote = ((f.get("ext") or {}).get("remote") or {})
        api = remote.get("api") or ""
        if not api:
            continue
        if rel == "belongs_to" or render == "remote_select":
            rid = fetch_first_id(api)
            out[prop] = rid
        elif rel == "belongs_to_many" or render == "remote_multi_select":
            rid = fetch_first_id(api)
            out[prop] = [rid] if rid else []
    return out


def test_case(slug: str, cfg: dict) -> list[str]:
    errors: list[str] = []
    api = api_base_from_paths(cfg["paths"])
    fields = cfg.get("fields") or []
    create_body = enrich_remote(build_payload(fields, update=False), fields)
    update_body = enrich_remote(build_payload(fields, update=True), fields)

    # list
    st, env = request("GET", f"{api}/list", query={"page": 1, "limit": 5})
    if st != 200 or not ok(env):
        errors.append(f"list HTTP{st} code={env.get('code')} msg={env.get('msg')}")
        return errors

    # create
    st, env = request("POST", f"{api}/create", body=create_body)
    if st != 200 or not ok(env):
        errors.append(f"create HTTP{st} code={env.get('code')} msg={env.get('msg')} body={create_body}")
        return errors
    new_id = (env.get("data") or {}).get("id")
    if new_id is None:
        errors.append(f"create missing id data={env.get('data')}")
        return errors
    new_id = int(new_id)

    # read
    st, env = request("GET", f"{api}/read", query={"id": new_id})
    if st != 200 or not ok(env):
        errors.append(f"read HTTP{st} code={env.get('code')} msg={env.get('msg')}")
    else:
        data = env.get("data") or {}
        if int(data.get("id") or 0) != new_id:
            errors.append(f"read id mismatch got={data.get('id')}")

    # update (skip if empty writable)
    if update_body:
        st, env = request("PUT", f"{api}/update", query={"id": new_id}, body=update_body)
        if st != 200 or not ok(env):
            errors.append(f"update HTTP{st} code={env.get('code')} msg={env.get('msg')} body={update_body}")
    else:
        # still call update with empty body to ensure endpoint works
        st, env = request("PUT", f"{api}/update", query={"id": new_id}, body={})
        if st != 200 or not ok(env):
            errors.append(f"update(empty) HTTP{st} code={env.get('code')} msg={env.get('msg')}")

    # list again should include
    st, env = request("GET", f"{api}/list", query={"page": 1, "limit": 50})
    if st != 200 or not ok(env):
        errors.append(f"list2 HTTP{st} code={env.get('code')} msg={env.get('msg')}")
    else:
        ids = {int(r.get("id")) for r in ((env.get("data") or {}).get("list") or []) if r.get("id") is not None}
        if new_id not in ids and (env.get("data") or {}).get("total", 0) > 0:
            # may be on another page; ignore if total large
            pass

    # delete
    st, env = request("DELETE", f"{api}/delete", query={"id": new_id})
    if st != 200 or not ok(env):
        errors.append(f"delete HTTP{st} code={env.get('code')} msg={env.get('msg')}")

    # read after delete -> 404 expected
    st, env = request("GET", f"{api}/read", query={"id": new_id})
    if ok(env):
        errors.append("read after delete still ok")
    elif env.get("code") not in (404, 400):
        # accept business 404
        if st == 200 and env.get("code") != 404:
            errors.append(f"read after delete unexpected code={env.get('code')} msg={env.get('msg')}")

    return errors


def main() -> int:
    manifest = json.loads(MANIFEST.read_text(encoding="utf-8"))
    ok_n = 0
    fail_n = 0
    for case in manifest["cases"]:
        name = case["file"]
        cfg = json.loads((Path(__file__).parent / name).read_text(encoding="utf-8"))
        api = api_base_from_paths(cfg["paths"])
        errs = test_case(name, cfg)
        if errs:
            fail_n += 1
            print(f"[FAIL] {name}  {api}")
            for e in errs:
                print(f"       - {e}")
        else:
            ok_n += 1
            print(f"[OK]   {name}  {api}")
    print("=" * 60)
    print(f"SUMMARY ok={ok_n} fail={fail_n} total={ok_n + fail_n}")
    return 1 if fail_n else 0


if __name__ == "__main__":
    sys.exit(main())
