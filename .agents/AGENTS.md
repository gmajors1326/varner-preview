# Varner Equipment -- Project Rules

## WP Engine Plugin Deployment -- Cache Fix

After every plugin deploy (wp plugin install --force), inventory and analytics may appear blank due to Memcached + Varnish caching stale HTML or the varner_assets_ transient. Always run this sequence after deploying the plugin:

### One-liner (preferred):
```powershell
ssh -i ~/.ssh/id_ed25519_wpe varnerequipdev@varnerequipdev.ssh.wpengine.net "wp plugin deactivate varner-os-plugin-v23 --path=/sites/varnerequipdev && wp cache flush --path=/sites/varnerequipdev && wp plugin activate varner-os-plugin-v23 --path=/sites/varnerequipdev && wp cache flush --path=/sites/varnerequipdev && wp page-cache flush --path=/sites/varnerequipdev"
```

After running the above, tell the user to hard refresh their browser (Ctrl+Shift+R on Windows / Cmd+Shift+R on Mac) or open in a private/incognito window.

### Why this happens
- WP Engine uses Memcached (unix:///tmp/memcached.sock) for the WordPress object cache, including transients.
- The plugin stores enqueued asset filenames in a transient keyed by varner_assets_{filemtime}.
- WP Engine also uses Varnish (wpe_varnish_servers: 127.0.0.1) to cache full HTML pages.
- After a deploy, the old HTML (with old asset filenames) may still be in Varnish, and old transients may still be in Memcached.
- wp transient delete --all only deletes DB-stored transients -- it does NOT clear Memcached transients.
- Only wp cache flush clears Memcached.
