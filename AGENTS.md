# Deployment Notes

## WPEngine — Dev Site
- **Host:** varnerequipdev@varnerequipdev.ssh.wpengine.net
- **SSH Key:** `~/.ssh/id_ed25519_wpe`
- **WP Path:** `/sites/varnerequipdev`

## Active Theme
- **Slug:** `varner-equipment-theme-v23-lite-4` (NOT `varner-lite`)
- Always verify active theme slug before deploying (`wp theme list`)

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
