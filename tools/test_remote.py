import subprocess

ssh_key = r"C:\Users\Greg\.ssh\id_ed25519_wpe"
host = "varnerequipdev@varnerequipdev.ssh.wpengine.net"

php_script = """<?php
echo "CHECKING_ROUTES:\n";
echo "varner_get_brand_exists=" . (function_exists('varner_get_brand') ? 'YES' : 'NO') . "\n";

$test_url = 'https://varnerequipment.com/inventory/in-stock-inventory/';
$req = '/inventory/in-stock-inventory/';
$path = strtolower(trim(parse_url($req, PHP_URL_PATH), '/'));
echo "path_parsed=" . var_export($path, true) . "\n";

$legacy_paths = array('inventory/in-stock-inventory', 'inventory/showroom-inventory');
echo "in_array=" . (in_array($path, $legacy_paths, true) ? 'YES' : 'NO') . "\n";
"""

# Upload test script
upload_cmd = ["ssh", "-o", "StrictHostKeyChecking=no", "-i", ssh_key, host, "cat > /sites/varnerequipdev/test_diag.php"]
subprocess.run(upload_cmd, input=php_script, text=True)

# Execute test script via wp eval-file
exec_cmd = ["ssh", "-o", "StrictHostKeyChecking=no", "-i", ssh_key, host, "wp eval-file /sites/varnerequipdev/test_diag.php --path=/sites/varnerequipdev && rm /sites/varnerequipdev/test_diag.php"]
res = subprocess.run(exec_cmd, capture_output=True, text=True)

print("STDOUT:")
print(res.stdout)
print("STDERR:")
print(res.stderr)
