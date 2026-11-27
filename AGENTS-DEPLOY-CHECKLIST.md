# Agent Deploy Checklist — Do Not Skip

## Golden Rule

**Read `.md` files in project root FIRST.** Every time. Before touching any tool.

Checklist before any deploy:
1. Read AGENTS.md — deploy commands, slugs, SSH key path
2. Read DEPLOY.md — deployment *runbook*, warnings about SCP/SFTP, zip format, build process
3. Read build.ps1 — understands how zips are actually built

Failure to do step 2 caused ~15 wasted tool calls trying SCP/SFTP that were explicitly documented as disabled.

---

## Deploy Pipeline (Correct)

### 1. Build zips

Use `build.ps1` (or Python zipfile directly for quick fixes).

**NEVER** use `tar -a -cf` for zips — produces POSIX TAR, not valid ZIP. WP rejects with "PCLZIP_ERR_BAD_FORMAT".

```bash
# Plugin
python tools/zip_helper.py varner-os-plugin-v23.zip varner-os-plugin-v23-unpacked/varner-os-plugin-v23

# Theme (files at root level, no parent dir)
python -c "
import zipfile, os
src = os.path.abspath('varner-equipment-theme-lite/varner-lite')
exclude_dirs = {'src', '.git', '__pycache__'}
exclude_files = {'.DS_Store', 'tailwind.config.js', 'nul', 'package.json'}
with zipfile.ZipFile('varner-equipment-theme-v23-lite-4.zip', 'w', zipfile.ZIP_DEFLATED) as z:
    for root, dirs, files in os.walk(src):
        dirs[:] = [d for d in dirs if d not in exclude_dirs]
        dirs.sort(); files.sort()
        for f in files:
            if f.startswith('.git') or f in exclude_files or f.endswith('.md'): continue
            fp = os.path.join(root, f)
            arcname = os.path.relpath(fp, src).replace(os.sep, '/')
            z.write(fp, arcname)
"
```

### 2. Stream via SSH (NOT SCP/SFTP)

WPE gateway has SCP/SFTP **disabled** (DEPLOY.md §75-79). Use `< file` redirect in Git Bash:

```bash
ssh -i ~/.ssh/id_ed25519_wpe varnerequipdev@varnerequipdev.ssh.wpengine.net "cat > /sites/varnerequipdev/varner-os-plugin-v23.zip" < varner-os-plugin-v23.zip
```

**Do NOT pipe via PowerShell** (`Get-Content -Raw | ssh ...`) — it corrupts binary data via Unicode encoding conversion. Git Bash `< file` redirect passes raw bytes.

### 3. Install via direct unzip (NOT WP-CLI)

**WP-CLI `wp plugin install` / `wp theme install` will FAIL** on this WPEngine install due to the `/sites/` vs `/nas/content/live/` mount-path mismatch. Use `unzip` directly instead:

```bash
# Plugin
ssh -i ~/.ssh/id_ed25519_wpe varnerequipdev@varnerequipdev.ssh.wpengine.net "cd /nas/content/live/varnerequipdev/wp-content/plugins && unzip -o /sites/varnerequipdev/varner-os-plugin-v23.zip && rm /sites/varnerequipdev/varner-os-plugin-v23.zip"

# Theme (extract INTO the theme dir, not alongside it)
ssh -i ~/.ssh/id_ed25519_wpe varnerequipdev@varnerequipdev.ssh.wpengine.net "cd /nas/content/live/varnerequipdev/wp-content/themes/varner-equipment-theme-v23-lite-4 && unzip -o /sites/varnerequipdev/varner-equipment-theme-v23-lite-4.zip && rm /sites/varnerequipdev/varner-equipment-theme-v23-lite-4.zip"
```

Theme zip has files at root level (no parent dir). Plugin zip has `varner-os-plugin-v23/` as top-level directory.

### 4. Flush cache

**Preferred:** Use the WPEngine portal → Tools → Cache → Clear All Caches. Avoids the NAS mount-path confusion entirely.

**WP-CLI note:** `wp cache flush --path=/sites/varnerequipdev` will throw a fatal PHP error if the active theme uses `get_template_directory()` — because WP-CLI resolves that to `/sites/varnerequipdev/...` while the actual files live at `/nas/content/live/varnerequipdev/...`. This is a **WP-CLI path artifact only** — the live site is unaffected. The portal flush is always safer.

---

## Pitfalls Encountered

| Mistake | Why | Fix |
|---------|-----|-----|
| Didn't read DEPLOY.md first | Assumed generic WP deploy | Read project `.md` files before any action |
| SCP/SFTP attempts | WPE gateway has them disabled | Use SSH stdin redirect with `< file` |
| PowerShell binary pipe | `Get-Content -Raw` converts encoding, corrupts zips | Use Git Bash `< file` redirect |
| `tar -a -cf` for zips | Creates POSIX TAR, not ZIP | Use Python zipfile (zipfile.ZIP_DEFLATED) |
| `Compress-Archive` for zips | Stores paths with Windows backslashes; Linux `unzip` skips subdirectories silently — `inc/`, `partials/`, etc. vanish on the server | Use Python `zipfile` via `tools/zip_helper.py` or the inline one-liner above |
| `-i C:\Users\Greg\.ssh\...` | Backslash path mangled by shell | Use `~/.ssh/` or forward slashes |
| Wrong zip name (missing `-4`) | Theme slug has `-4` suffix | Verify active slug with `wp theme list` |
| Calling "Dev Site" | `varnerequipdev` IS the production install — `varnerequipment.com` points to it | Label it Production; same SSH host/path |
