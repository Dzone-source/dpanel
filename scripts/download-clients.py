#!/usr/bin/env python3
"""Download recommended client apps into public/clients/ from GitHub releases.

Uses config/clients.json. Safe to re-run; skips clients already at latest tag
when storage/LocalClientVersion.json matches.

Usage:
  python3 scripts/download-clients.py
  # or from repo root after chmod +x
  ./scripts/download-clients.py
"""

from __future__ import annotations

import json
import ssl
import sys
import time
import urllib.request
from pathlib import Path

BASE = Path(__file__).resolve().parents[1]
CLIENTS_JSON = BASE / "config" / "clients.json"
VERSION_PATH = BASE / "storage" / "LocalClientVersion.json"
UA = {
    "User-Agent": "DPanel-ClientDownload",
    "Accept": "application/vnd.github+json",
}


def http_get(url: str, dest: Path | None = None) -> bytes | int:
    req = urllib.request.Request(url, headers=UA)
    ctx = ssl.create_default_context()
    with urllib.request.urlopen(req, context=ctx, timeout=600) as resp:
        data = resp.read()
    if dest is not None:
        dest.parent.mkdir(parents=True, exist_ok=True)
        dest.write_bytes(data)
        return len(data)
    return data


def latest_tag(repo: str) -> str:
    payload = http_get(f"https://api.github.com/repos/{repo}/releases/latest")
    assert isinstance(payload, bytes)
    return json.loads(payload)["tag_name"]


def render_name(template: str, tag: str) -> str:
    tag1 = tag[1:] if tag.startswith("v") else tag
    return (
        template.replace("%tagName%", tag)
        .replace("%tagName1%", tag1)
        .strip()
    )


def load_versions() -> dict:
    VERSION_PATH.parent.mkdir(parents=True, exist_ok=True)
    if VERSION_PATH.exists():
        try:
            data = json.loads(VERSION_PATH.read_text())
            if isinstance(data, dict):
                return data
        except json.JSONDecodeError:
            pass
    return {"createTime": int(time.time())}


def main() -> int:
    if not CLIENTS_JSON.is_file():
        print("config/clients.json not found", file=sys.stderr)
        return 1

    clients = json.loads(CLIENTS_JSON.read_text())["clients"]
    versions = load_versions()
    ok = fail = 0

    for client in clients:
        name = client["name"]
        print(f"====== {name} ======")
        try:
            tag = latest_tag(client["gitRepo"])
        except Exception as exc:  # noqa: BLE001
            print(f"- API fail: {exc}")
            fail += 1
            continue

        if versions.get(name) == tag:
            save_path = BASE / client["savePath"]
            expected = [
                render_name(d.get("saveName") or d["sourceName"], tag)
                for d in client["downloads"]
            ]
            if all((save_path / f).is_file() for f in expected):
                print(f"- already up to date ({tag}), skip")
                print()
                continue

        print(f"- latest: {tag}")
        save_path = BASE / client["savePath"]
        save_path.mkdir(parents=True, exist_ok=True)
        all_ok = True

        for dl in client["downloads"]:
            source = render_name(dl["sourceName"], tag)
            save = render_name(dl.get("saveName") or dl["sourceName"], tag)
            url = (
                f"https://github.com/{client['gitRepo']}"
                f"/releases/download/{tag}/{source}"
            )
            dest = save_path / save
            print(f"- downloading {save} ...")
            try:
                size = http_get(url, dest)
                assert isinstance(size, int)
                print(f"  OK {size / 1024 / 1024:.1f} MB -> {dest}")
                ok += 1
            except Exception as exc:  # noqa: BLE001
                print(f"  FAIL: {exc}")
                fail += 1
                all_ok = False
                if dest.exists():
                    dest.unlink()

        if all_ok:
            versions[name] = tag
            VERSION_PATH.write_text(json.dumps(versions, indent=2) + "\n")
        print()

    print(f"Done. ok={ok} fail={fail}")
    return 1 if fail else 0


if __name__ == "__main__":
    raise SystemExit(main())
