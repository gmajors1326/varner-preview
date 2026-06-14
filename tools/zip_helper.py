import os
import sys
import zipfile

def zip_dir(zip_path, dir_to_zip):
    """
    Creates a zip archive of the specified directory.
    The archive structure will preserve the directory name at the root.
    Uses forward slashes for path entries to ensure cross-platform compatibility.
    Excludes build-time files, documentation markdown files, and hidden OS files.
    """
    abs_dir_to_zip = os.path.abspath(dir_to_zip)
    base_dir = os.path.dirname(abs_dir_to_zip)
    
    with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as z:
        for root, dirs, files in os.walk(abs_dir_to_zip):
            # Prune build-time source/config directories to keep ZIP clean and secure
            if 'src' in dirs:
                dirs.remove('src')
            if '.git' in dirs:
                dirs.remove('.git')
                
            # Sort directories and files for deterministic ZIP creation
            dirs.sort()
            files.sort()
            
            for directory in dirs:
                dir_path = os.path.join(root, directory)
                arcname = os.path.relpath(dir_path, base_dir)
                arcname = arcname.replace(os.sep, '/') + '/'
                z.write(dir_path, arcname)
                
            for file in files:
                # Exclude build configuration files, system/hidden files, and internal MD documentation
                if (file.startswith('.git') or 
                    file == '.DS_Store' or 
                    file.endswith('.md') or 
                    file == 'tailwind.config.js'):
                    continue
                file_path = os.path.join(root, file)
                arcname = os.path.relpath(file_path, base_dir)
                arcname = arcname.replace(os.sep, '/')
                z.write(file_path, arcname)

if __name__ == '__main__':
    if len(sys.argv) < 3:
        print("Usage: python zip_helper.py <zip_path> <dir_to_zip>")
        sys.exit(1)
    
    zip_path_arg = sys.argv[1]
    dir_to_zip_arg = sys.argv[2]
    
    print(f"Zipping '{dir_to_zip_arg}' to '{zip_path_arg}'...")
    zip_dir(zip_path_arg, dir_to_zip_arg)
    print("Zipping complete.")
