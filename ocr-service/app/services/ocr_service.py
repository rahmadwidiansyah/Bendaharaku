"""OCR Service — PaddleOCR wrapper for text extraction."""

import time
import logging
from typing import Optional

import numpy as np
from PIL import Image
from paddleocr import PaddleOCR

from app.utils.image import preprocess_image

logger = logging.getLogger(__name__)

# Lazy-loaded PaddleOCR instance (heavy init)
_ocr_instance: Optional[PaddleOCR] = None


def _get_ocr() -> PaddleOCR:
    """Get or initialize PaddleOCR singleton."""
    global _ocr_instance
    if _ocr_instance is None:
        logger.info("Initializing PaddleOCR engine...")
        _ocr_instance = PaddleOCR(
            use_angle_cls=True,
            lang="id",
            use_gpu=False,
            show_log=False,
        )
        logger.info("PaddleOCR engine initialized.")
    return _ocr_instance


def extract_text(image_bytes: bytes) -> dict:
    """
    Extract text from image using PaddleOCR.

    Returns:
        dict with keys: text, processing_time_ms, engine
    """
    start = time.time()

    # Preprocess
    img = preprocess_image(image_bytes)

    # Convert PIL Image to numpy array for PaddleOCR
    img_array = np.array(img)

    # Run OCR
    ocr = _get_ocr()
    result = ocr.ocr(img_array, cls=True)

    # Extract plain text
    lines = []
    if result and result[0]:
        for line in result[0]:
            if line and len(line) >= 2:
                text = line[1][0]
                confidence = line[1][1]
                # Only include lines with reasonable confidence
                if confidence > 0.3:
                    lines.append(text)

    plain_text = "\n".join(lines)
    elapsed_ms = int((time.time() - start) * 1000)

    logger.info(
        "OCR completed",
        extra={
            "engine": "PaddleOCR",
            "duration_ms": elapsed_ms,
            "line_count": len(lines),
        },
    )

    return {
        "success": True,
        "text": plain_text,
        "processing_time_ms": elapsed_ms,
        "engine": "PaddleOCR",
    }
