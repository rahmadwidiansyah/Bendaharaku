FROM python:3.11-slim

LABEL maintainer="Bendaharaku Team"
LABEL description="OCR Microservice — FastAPI + PaddleOCR (CPU)"

WORKDIR /app

# System deps for OpenCV + PaddleOCR (headless)
RUN apt-get update && apt-get install -y --no-install-recommends \
    libgl1 \
    libglib2.0-0 \
    libsm6 \
    libxext6 \
    libxrender-dev \
    libgomp1 \
    && rm -rf /var/lib/apt/lists/*

# Upgrade pip first (faster installs, better resolver)
RUN pip install --no-cache-dir --upgrade pip setuptools wheel

# Install Python dependencies (cache layer: only rebuilds when requirements.txt changes)
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy application source
COPY . .

EXPOSE 8000

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000"]
