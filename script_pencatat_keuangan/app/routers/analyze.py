from fastapi import APIRouter

from app.schemas import AnalyzeRequest, AnalyzeResponse
from app.services.amount import get_nominal_smart
from app.services.intent import guess_transaction_intent
from app.services.subject import extract_subject
from app.services.wallet import match_wallets
from app.services.category import match_best_category
from app.services.confidence import calculate_confidence, is_cleared

router = APIRouter()


@router.post("/analyze", response_model=AnalyzeResponse)
async def analyze_transaction(req: AnalyzeRequest):
    nominal = get_nominal_smart(req.text)
    subject = extract_subject(req.text)
    ordered_wallet_names = match_wallets(req.text, req.wallets)
    best_cat_name = match_best_category(req.text, req.categories)

    intent = guess_transaction_intent(req.text, best_cat_name or "")

    source_wallet = ordered_wallet_names[0] if len(ordered_wallet_names) > 0 else None
    dest_wallet = ordered_wallet_names[1] if len(ordered_wallet_names) > 1 else None

    if intent == 'income' and source_wallet and not dest_wallet:
        dest_wallet = source_wallet
        source_wallet = None

    has_amount = nominal is not None
    has_category = best_cat_name is not None
    has_wallet = source_wallet is not None or dest_wallet is not None
    has_subject = subject is not None

    confidence = calculate_confidence(has_amount, has_category, has_wallet, has_subject)
    cleared = is_cleared(has_amount, has_category, has_wallet, confidence)

    return AnalyzeResponse(
        success=True,
        amount=nominal,
        transaction_type=intent,
        category=best_cat_name,
        source_wallet=source_wallet,
        destination_wallet=dest_wallet,
        subject=subject,
        notes=req.text,
        is_cleared=cleared,
        confidence=confidence,
    )
