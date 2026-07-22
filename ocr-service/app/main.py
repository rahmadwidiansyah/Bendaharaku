"""OCR Microservice — FastAPI application."""

import logging

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from app.api.extract import router as extract_router
from app.schemas.ocr import HealthResponse

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s",
)

app = FastAPI(
    title="Bendaharaku OCR Service",
    description="PaddleOCR microservice for receipt/bukti text extraction",
    version="1.0.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Routes
app.include_router(extract_router, prefix="/ocr", tags=["ocr"])


@app.get("/ready", response_model=HealthResponse)
async def health_check():
    """Health check endpoint for Docker."""
    return HealthResponse(
        status="ok",
        engine="PaddleOCR",
        version="1.0.0",
    )


@app.get("/", tags=["root"])
async def root():
    return {"service": "bendaharaku-ocr", "status": "running"}
