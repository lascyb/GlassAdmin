# -*- coding: utf-8 -*-
"""批量执行 samples/test 字段类型用例（默认 dry-run；加 --write 实写；加 --no-auth 跳过权限）"""
from __future__ import annotations

import json
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[4]  # GlassAdmin/
MANIFEST = Path(__file__).resolve().parent / "_manifest.json"


def main() -> int:
    write = "--write" in sys.argv
    no_auth = "--no-auth" in sys.argv
    manifest = json.loads(MANIFEST.read_text(encoding="utf-8"))
    ok: list[str] = []
    fail: list[str] = []
    for case in manifest["cases"]:
        name = case["file"]
        cmd = [
            "php",
            "think",
            "crud:generate",
            f"extend/crud/samples/test/{name}",
        ]
        if no_auth:
            cmd.append("--no-auth")
        if not write:
            cmd.append("--dry-run")
        p = subprocess.run(cmd, cwd=ROOT, capture_output=True, text=True)
        out = (p.stdout or "") + (p.stderr or "")
        status = "OK" if p.returncode == 0 else "FAIL"
        (ok if p.returncode == 0 else fail).append(name)
        print("=" * 60)
        print(f"[{status}] {name}  exit={p.returncode}")
        lines = [ln for ln in out.splitlines() if ln.strip()]
        for ln in lines[-30:]:
            print(ln)
    total = len(manifest["cases"])
    print("=" * 60)
    mode = "write" if write else "dry-run"
    auth = "no-auth" if no_auth else "auth"
    print(f"SUMMARY mode={mode} {auth} ok={len(ok)} fail={len(fail)} total={total}")
    if fail:
        print("FAILED:", ", ".join(fail))
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
