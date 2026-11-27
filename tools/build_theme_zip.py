import os
import zipfile

src = os.path.abspath('varner-equipment-theme-lite/varner-lite')
zip_name = 'varner-equipment-theme-v23-lite-4.zip'
exclude_dirs = {'src', '.git', '__pycache__'}
exclude_files = {'.DS_Store', 'tailwind.config.js', 'nul', 'package.json'}

print(f"Building theme ZIP '{zip_name}' from '{src}'...")
with zipfile.ZipFile(zip_name, 'w', zipfile.ZIP_DEFLATED) as z:
    for root, dirs, files in os.walk(src):
        dirs[:] = [d for d in dirs if d not in exclude_dirs]
        dirs.sort()
        files.sort()
        for f in files:
            if f.startswith('.git') or f in exclude_files or f.endswith('.md'):
                continue
            fp = os.path.join(root, f)
            arcname = os.path.relpath(fp, src).replace(os.sep, '/')
            z.write(fp, arcname)

print("Theme ZIP build successful.")
