import urllib.request
import re
import json
import random

urls = [
    "https://varnerequipment.com/",
    "https://varnerequipment.com/inventory/2026-tym-t574hc/",
    "https://varnerequipment.com/inventory/all-units/",
    "https://varnerequipment.com/product-videos/",
    "https://varnerequipment.com/dealer-info/contact/"
]

req_headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
}

for base_url in urls:
    print("=" * 80)
    print(f"TESTING URL: {base_url}")
    print("=" * 80)
    cb_url = f"{base_url}?cb={random.randint(100000, 999999)}"
    req = urllib.request.Request(cb_url, headers=req_headers)
    try:
        with urllib.request.urlopen(req) as resp:
            html = resp.read().decode('utf-8')
            blocks = re.findall(r'<script type=["\']application/ld\+json["\']>(.*?)</script>', html, re.DOTALL)
            print(f"FOUND {len(blocks)} JSON-LD BLOCK(S):")
            for idx, b in enumerate(blocks, 1):
                print(f"\n--- BLOCK {idx} ---")
                try:
                    data = json.loads(b.strip())
                    print(json.dumps(data, indent=2))
                except Exception as e:
                    print(f"Raw string (failed JSON parse: {e}):")
                    print(b[:300])
    except Exception as err:
        print(f"HTTP Request Failed: {err}")
    print("\n")
