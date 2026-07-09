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

### 3. Install via WP CLI

```bash
ssh -i ~/.ssh/id_ed25519_wpe varnerequipdev@varnerequipdev.ssh.wpengine.net "wp plugin install /sites/varnerequipdev/varner-os-plugin-v23.zip --force --path=/sites/varnerequipdev && rm /sites/varnerequipdev/varner-os-plugin-v23.zip"
```

Theme slug on WPE is **`varner-equipment-theme-v23-lite-4`** (verify with `wp theme list`). Remote zip filename must match.

### 4. Flush cache

```bash
ssh -i ~/.ssh/id_ed25519_wpe varnerequipdev@varnerequipdev.ssh.wpengine.net "wp page-cache flush --path=/sites/varnerequipdev"
```

---

## Pitfalls Encountered

| Mistake | Why | Fix |
|---------|-----|-----|
| Didn't read DEPLOY.md first | Assumed generic WP deploy | Read project `.md` files before any action |
| SCP/SFTP attempts | WPE gateway has them disabled | Use SSH stdin redirect with `< file` |
| PowerShell binary pipe | `Get-Content -Raw` converts encoding, corrupts zips | Use Git Bash `< file` redirect |
| `tar -a -cf` for zips | Creates POSIX TAR, not ZIP | Use Python zipfile (zipfile.ZIP_DEFLATED) |
| `-i C:\Users\Greg\.ssh\...` | Backslash path mangled by shell | Use `~/.ssh/` or forward slashes |
| Wrong zip name (missing `-4`) | Theme slug has `-4` suffix | Verify active slug with `wp theme list` |
