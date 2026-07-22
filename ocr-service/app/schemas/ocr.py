from pydantic import BaseModel


class OCRResponse(BaseModel):
    success: bool
    text: str
    processing_time_ms: int
    engine: str


class HealthResponse(BaseModel):
    status: str
    engine: str
    version: str
