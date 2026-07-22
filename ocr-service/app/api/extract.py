"""OCR API route — POST /ocr/extract."""

import logging

from fastapi import APIRouter, File, HTTPException, UploadFile

from app.schemas.ocr import OCRResponse
from app.services.ocr_service import extract_text

logger = logging.getLogger(__name__)

router = APIRouter()

ALLOWED_TYPES = {"image/jpeg", "image/png", "image/webp"}
MAX_FILE_SIZE = 10 * 1024 * 1024  # 10MB


@router.post("/extract", response_model=OCRResponse)
async def extract(file: UploadFile = File(...)):
    """
    Extract text from an image using PaddleOCR.

    Accepts: multipart/form-data with 'image' field.
    Returns: plain text extracted from the image.
    """
    # Validate content type
    if file.content_type not in ALLOWED_TYPES:
        raise HTTPException(
            status_code=400,
            detail=f"Unsupported file type: {file.content_type}. Allowed: {', '.join(ALLOWED_TYPES)}",
        )

    # Read file
    contents = await file.read()

    # Validate size
    if len(contents) > MAX_FILE_SIZE:
        raise HTTPException(
            status_code=400,
            detail=f"File too large: {len(contents)} bytes. Max: {MAX_FILE_SIZE} bytes",
        )

    try:
        result = extract_text(contents)
        return OCRResponse(**result)
    except Exception as e:
        logger.error("OCR extraction failed", exc_info=True)
        raise HTTPException(
            status_code=500,
            detail=f"OCR processing failed: {str(e)}",
        )
