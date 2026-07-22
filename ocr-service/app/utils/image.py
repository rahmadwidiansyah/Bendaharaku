"""Utility helpers for image preprocessing."""

from io import BytesIO

from PIL import Image


def preprocess_image(image_bytes: bytes, max_size: int = 4096) -> Image.Image:
    """
    Preprocess image for OCR:
    - Convert to RGB
    - Auto-rotate based on EXIF
    - Resize if too large (preserve aspect ratio)
    """
    img = Image.open(BytesIO(image_bytes))

    # Convert to RGB (handle RGBA, palette, etc.)
    if img.mode not in ("RGB", "L"):
        img = img.convert("RGB")
    elif img.mode == "L":
        img = img.convert("RGB")

    # Auto-rotate from EXIF
    try:
        from PIL import ExifTags

        exif = img._getexif()
        if exif:
            orientation_key = next(
                k for k, v in ExifTags.TAGS.items() if v == "Orientation"
            )
            orientation = exif.get(orientation_key)
            if orientation == 3:
                img = img.rotate(180, expand=True)
            elif orientation == 6:
                img = img.rotate(270, expand=True)
            elif orientation == 8:
                img = img.rotate(90, expand=True)
    except (AttributeError, KeyError, StopIteration):
        pass

    # Resize if too large
    width, height = img.size
    if max(width, height) > max_size:
        ratio = max_size / max(width, height)
        new_size = (int(width * ratio), int(height * ratio))
        img = img.resize(new_size, Image.LANCZOS)

    return img
