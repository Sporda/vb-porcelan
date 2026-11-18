#!/usr/bin/env python3
"""HISTORICKÉ – už se nepoužívá. Od 18. 11. 2026 běží na vb-porcelan.webtitan.cz e-shop (eshop/, nasazení
z GitHubu přes panel, viz README.md). Návrhy v design/ zůstávají jen jako historie vývoje.

Nahraje návrhy (složku design/) na https://vb-porcelan.webtitan.cz/design/

Použití:  python3 scripts/deploy-design.py [--dry-run]

- Hosting: panel.webtitan.cz, účet `vbporcelan` (web = account), API token v ~/.config/hosting-panel/token
  (token se předává curlu přes stdin, nikdy není v argumentech ani ve výstupu).
- Na serveru:  public_html/public/          kořen webu (docRootSubdir = "public")
               public_html/public/design/   návrhy + api/likes.php + previews/
               public_html/data/            SQLite s lajky (mimo web, vytvoří ji PHP / tento skript)
- Soubory se přepisují (overwrite), skript lze pouštět opakovaně. Nic na serveru nemaže.
"""
import base64, json, os, subprocess, sys, tempfile, time

PANEL = "https://panel.webtitan.cz"
SITE = "https://vb-porcelan.webtitan.cz"
ACCOUNT = "vbporcelan"
DOCROOT_SUBDIR = "public"
REMOTE_WEB = "public_html/" + DOCROOT_SUBDIR
REMOTE_DESIGN = REMOTE_WEB + "/design"
REMOTE_DATA = "public_html/data"
TOKEN_FILE = os.path.expanduser("~/.config/hosting-panel/token")
LOCAL_DESIGN = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "design")
SKIP = {".DS_Store"}

# drobnosti v kořeni webu, dokud tu není e-shop
ROOT_FILES = {
    "index.html": """<!doctype html>
<html lang="cs"><head><meta charset="utf-8"><meta name="robots" content="noindex, nofollow">
<meta http-equiv="refresh" content="0; url=/design/"><title>Bílé zlato – připravujeme</title></head>
<body style="font-family:system-ui,sans-serif;padding:40px">Připravujeme e-shop. <a href="/design/">Návrhy designu</a></body></html>
""",
    "robots.txt": "User-agent: *\nDisallow: /\n",
}

DRY = "--dry-run" in sys.argv


def api(method, path, body=None):
    token = open(TOKEN_FILE).read().strip()
    args = ["curl", "-sS", "-K", "-", "-X", method, "-w", "\n__HTTP__%{http_code}", PANEL + path]
    tmp = None
    if body is not None:
        fd, tmp = tempfile.mkstemp(suffix=".json")
        with os.fdopen(fd, "w") as f:
            json.dump(body, f)
        args += ["-H", "Content-Type: application/json", "--data-binary", "@" + tmp]
    try:
        r = subprocess.run(args, input=f'header = "Authorization: Bearer {token}"\n',
                           capture_output=True, text=True, timeout=180)
    finally:
        if tmp:
            os.remove(tmp)
    text, _, code = r.stdout.rpartition("\n__HTTP__")
    try:
        data = json.loads(text) if text.strip() else None
    except ValueError:
        data = text[:500]
    return int(code or 0), data


def wait_job(job_id, timeout=600):
    t0 = time.time()
    while time.time() - t0 < timeout:
        _, j = api("GET", "/api/jobs/" + job_id)
        j = (j or {}).get("job", j) or {}
        if j.get("status") in ("succeeded", "failed", "cancelled"):
            return j
        time.sleep(4)
    return {"status": "timeout"}


def main():
    code, accounts = api("GET", "/api/accounts")
    acc = next((a for a in (accounts or []) if a.get("username") == ACCOUNT), None)
    if code != 200 or not acc:
        sys.exit(f"účet {ACCOUNT} nenalezen (HTTP {code})")
    aid = acc["id"]
    print(f"účet {ACCOUNT} ({aid}), stav {acc.get('status')}")

    # 1) složky (po jedné úrovni; 409 = už existuje)
    dirs = [REMOTE_WEB, REMOTE_DESIGN, REMOTE_DATA]
    for root, subdirs, _ in os.walk(LOCAL_DESIGN):
        subdirs[:] = [s for s in subdirs if not s.startswith(".")]  # .taste-skill/ apod. jen lokálně
        for s in sorted(subdirs):
            rel = os.path.relpath(os.path.join(root, s), LOCAL_DESIGN)
            dirs.append(REMOTE_DESIGN + "/" + rel.replace(os.sep, "/"))
    for d in dirs:
        if DRY:
            print("mkdir", d); continue
        c, res = api("POST", f"/api/accounts/{aid}/files/directory", {"path": d})
        if c not in (200, 201, 204, 409):
            sys.exit(f"mkdir {d}: HTTP {c} {res}")
        print(("vytvořeno " if c != 409 else "existuje  ") + d)

    # 2) kořen webu = public_html/public
    site = acc.get("site_settings") or {}
    if site.get("docRootSubdir") != DOCROOT_SUBDIR:
        print(f"nastavuji kořen webu na public_html/{DOCROOT_SUBDIR}")
        if not DRY:
            c, res = api("PATCH", f"/api/accounts/{aid}/settings", {"site": {"docRootSubdir": DOCROOT_SUBDIR}})
            if c not in (200, 202):
                sys.exit(f"settings: HTTP {c} {res}")
            job = (res or {}).get("job")
            if job:
                j = wait_job(job["id"])
                print("  úloha", j.get("kind"), j.get("status"), (j.get("last_error") or "")[:200])
                if j.get("status") != "succeeded":
                    sys.exit("nastavení kořene webu selhalo")

    # 3) soubory
    uploads = []
    for name, content in ROOT_FILES.items():
        uploads.append((REMOTE_WEB + "/" + name, content.encode()))
    for root, subdirs, files in os.walk(LOCAL_DESIGN):
        subdirs[:] = [s for s in subdirs if not s.startswith(".")]
        for f in sorted(files):
            if f in SKIP or f.startswith("."):
                continue
            local = os.path.join(root, f)
            rel = os.path.relpath(local, LOCAL_DESIGN).replace(os.sep, "/")
            uploads.append((REMOTE_DESIGN + "/" + rel, open(local, "rb").read()))
    total = 0
    for path, data in uploads:
        total += len(data)
        if DRY:
            print(f"put {path} ({len(data) // 1024} kB)"); continue
        c, res = api("PUT", f"/api/accounts/{aid}/files/content",
                     {"path": path, "contentBase64": base64.b64encode(data).decode(), "overwrite": True})
        if c not in (200, 201, 204):
            sys.exit(f"upload {path}: HTTP {c} {res}")
        print(f"nahráno {path} ({len(data) // 1024} kB)")
    print(f"hotovo: {len(uploads)} souborů, {total // 1024} kB → {SITE}/design/")


if __name__ == "__main__":
    main()
