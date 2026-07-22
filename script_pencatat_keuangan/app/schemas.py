from pydantic import BaseModel
from typing import List, Optional


class WalletItem(BaseModel):
    name: str
    group_type: Optional[str] = None
    keyword: Optional[str] = None


class CategoryItem(BaseModel):
    category_name: str
    keyword: Optional[str] = None


class AnalyzeRequest(BaseModel):
    text: str
    wallets: List[WalletItem] = []
    categories: List[CategoryItem] = []


class AnalyzeResponse(BaseModel):
    success: bool
    amount: Optional[float] = None
    transaction_type: str = "expense"
    category: Optional[str] = None
    source_wallet: Optional[str] = None
    destination_wallet: Optional[str] = None
    subject: Optional[str] = None
    notes: Optional[str] = None
    is_cleared: bool = False
    confidence: float = 0.0


class HealthResponse(BaseModel):
    status: str = "ok"
    service: str = "ai-parser"

class LivenessResponse(BaseModel):
    status: str = "alive"

class ReadinessResponse(BaseModel):
    status: str = "ready"
    service: str = "ai-parser"
