# HARD RULES

- **Read ALL `.md` files in project root before ANY action** — AGENTS.md, DEPLOY.md, SKILL.md, etc. Failure to do this wasted ~15 tool calls on one deploy.
- Deploy procedure is in AGENTS-DEPLOY-CHECKLIST.md. Read it before every deploy.

# Deployment Notes

## WPEngine — Production Site (varnerequipment.com)
- **Host:** varnerequipdev@varnerequipdev.ssh.wpengine.net
- **SSH Key:** `~/.ssh/id_ed25519_wpe`
- **WP Path:** `/sites/varnerequipdev`
- **Note:** `varnerequipdev` is the single WPEngine install; `varnerequipment.com` is the domain pointed at it.

## Active Theme
- **Slug:** `varner-equipment-theme-v23-lite-4` (NOT `varner-lite`)
- Always verify active theme slug before deploying (`wp theme list`)

## ZIP Packaging Rule

**NEVER use `Compress-Archive` or `tar`.** Always use Python `zipfile` (via `tools/zip_helper.py` or the inline one-liner in AGENTS-DEPLOY-CHECKLIST.md). PowerShell zips use backslashes that Linux `unzip` mishandles, causing missing subdirectories on the server.

## Deploy Commands

### Plugin
```powershell
ssh -i ~/.ssh/id_ed25519_wpe varnerequipdev@varnerequipdev.ssh.wpengine.net "cat > /sites/varnerequipdev/varner-os-plugin-v23.zip" < varner-os-plugin-v23.zip
ssh -i ~/.ssh/id_ed25519_wpe varnerequipdev@varnerequipdev.ssh.wpengine.net "wp plugin install /sites/varnerequipdev/varner-os-plugin-v23.zip --force --path=/sites/varnerequipdev && rm /sites/varnerequipdev/varner-os-plugin-v23.zip"
```

### Theme — deploy to ACTIVE slug
```powershell
ssh -i ~/.ssh/id_ed25519_wpe varnerequipdev@varnerequipdev.ssh.wpengine.net "cat > /sites/varnerequipdev/varner-equipment-theme-v23-lite-4.zip" < varner-equipment-theme-v23-lite.zip
ssh -i ~/.ssh/id_ed25519_wpe varnerequipdev@varnerequipdev.ssh.wpengine.net "wp theme install /sites/varnerequipdev/varner-equipment-theme-v23-lite-4.zip --force --path=/sites/varnerequipdev && rm /sites/varnerequipdev/varner-equipment-theme-v23-lite-4.zip"
```

### Cache
```powershell
ssh -i ~/.ssh/id_ed25519_wpe varnerequipdev@varnerequipdev.ssh.wpengine.net "wp page-cache flush --path=/sites/varnerequipdev"
```
