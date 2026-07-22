import uvicorn
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from app.config import APP_HOST, APP_PORT, APP_DEBUG
from app.routers import analyze
from app.schemas import HealthResponse, LivenessResponse, ReadinessResponse

app = FastAPI(
    title="AI Parser",
    description="FastAPI-based transaction parser for Bendaharaku.",
    version="2.0.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.get("/health", response_model=HealthResponse, tags=["health"])
async def health():
    return HealthResponse()


@app.get("/live", response_model=LivenessResponse, tags=["health"])
async def liveness():
    return LivenessResponse()


@app.get("/ready", response_model=ReadinessResponse, tags=["health"])
async def readiness():
    return ReadinessResponse()


app.include_router(analyze.router, tags=["analyze"])

if __name__ == "__main__":
    uvicorn.run(
        "app.main:app",
        host=APP_HOST,
        port=APP_PORT,
        reload=APP_DEBUG,
    )
