from PIL import Image, ImageFilter
import os

SRC = r"varner-equipment-theme-lite/varner-lite/assets/VE_Tractor_Icon.png"
OUT = r"varner-os-plugin-v23-unpacked/varner-os-plugin-v23/assets/icons"
BG = "#0f172a"

os.makedirs(OUT, exist_ok=True)

tractor = Image.open(SRC).convert("RGBA")
tw, th = tractor.size


def make_icon(size, scale, smooth=True):
    canvas = Image.new("RGBA", (size, size), BG)
    target = int(size * scale)
    if smooth:
        resized = tractor.resize((target, target), Image.LANCZOS)
    else:
        resized = tractor.resize((target, target), Image.NEAREST)
    x = (size - target) // 2
    y = (size - target) // 2
    # Use alpha from the source to blend cleanly on the solid background
    canvas.paste(resized, (x, y), resized)
    return canvas.convert("RGB")


# any purpose — tractor ~85% fill
make_icon(192, 0.85).save(os.path.join(OUT, "icon-192.png"))
make_icon(512, 0.85).save(os.path.join(OUT, "icon-512.png"))

# maskable purpose — tractor ~60% within safe zone (inner 80% circle)
make_icon(192, 0.60).save(os.path.join(OUT, "icon-192-maskable.png"))
make_icon(512, 0.60).save(os.path.join(OUT, "icon-512-maskable.png"))

# apple-touch-icon 180x180
make_icon(180, 0.85).save(os.path.join(OUT, "apple-touch-icon-180.png"))

# shortcut icon 96x96
make_icon(96, 0.85).save(os.path.join(OUT, "icon-96.png"))

for f in os.listdir(OUT):
    path = os.path.join(OUT, f)
    img = Image.open(path)
    print(f"{f:30s} {img.size[0]}x{img.size[1]}  {os.path.getsize(path):,} bytes")
