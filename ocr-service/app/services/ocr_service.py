"""OCR Service — RapidOCR wrapper for text extraction (fallback for Tesseract)."""

import time
import logging
from typing import Optional

import numpy as np
from PIL import Image

from app.utils.image import preprocess_image

logger = logging.getLogger(__name__)

# Lazy-loaded RapidOCR instance (lightweight, ~20MB model)
_ocr_instance = None


def _get_ocr():
    """Get or initialize RapidOCR singleton."""
    global _ocr_instance
    if _ocr_instance is None:
        try:
            from rapidocr_onnxruntime import RapidOCR

            logger.info("Initializing RapidOCR engine...")
            _ocr_instance = RapidOCR(
                # Use mobile lite models, no angle classifier for speed
                use_angle_cls=False,
                lang="id",
            )
            logger.info("RapidOCR engine initialized.")
        except ImportError as e:
            logger.error(f"RapidOCR import failed: {e}")
            raise
    return _ocr_instance


def extract_text(image_bytes: bytes) -> dict:
    """
    Extract text from image using RapidOCR.

    Returns:
        dict with keys: text, processing_time_ms, engine
    """
    start = time.time()

    # Preprocess (resize to max 2048 for speed on 2GB server)
    img = preprocess_image(image_bytes)
    img_array = np.array(img)

    # Run OCR
    ocr = _get_ocr()
    # RapidOCR returns list of [box, text, score]
    result, _ = ocr(img_array)

    lines = []
    if result:
        for line in result:
            if line and len(line) >= 3:
                # RapidOCR format: [box, text, score]
                text = line[1] if len(line) > 1 else ""
                score = line[2] if len(line) > 2 else 0.5
                if score > 0.3 and text:
                    lines.append(text)
            elif line and len(line) >= 2:
                # Fallback for Paddle-like format
                text = line[1][0] if isinstance(line[1], (list, tuple)) else line[1]
                if text:
                    lines.append(str(text))

    plain_text = "\n".join(lines)
    elapsed_ms = int((time.time() - start) * 1000)

    logger.info(
        "OCR completed",
        extra={
            "engine": "RapidOCR",
            "duration_ms": elapsed_ms,
            "line_count": len(lines),
        },
    )

    return {
        "success": True,
        "text": plain_text,
        "processing_time_ms": elapsed_ms,
        "engine": "RapidOCR",
    }
