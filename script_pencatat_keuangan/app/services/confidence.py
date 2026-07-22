CONFIDENCE_WEIGHT_AMOUNT = 0.3
CONFIDENCE_WEIGHT_CATEGORY = 0.3
CONFIDENCE_WEIGHT_WALLET = 0.3
CONFIDENCE_WEIGHT_SUBJECT = 0.1
CONFIDENCE_CLEARED_THRESHOLD = 0.8


def calculate_confidence(
    has_amount: bool,
    has_category: bool,
    has_wallet: bool,
    has_subject: bool,
) -> float:
    confidence = 0.0
    if has_amount: confidence += CONFIDENCE_WEIGHT_AMOUNT
    if has_category: confidence += CONFIDENCE_WEIGHT_CATEGORY
    if has_wallet: confidence += CONFIDENCE_WEIGHT_WALLET
    if has_subject: confidence += CONFIDENCE_WEIGHT_SUBJECT
    return round(min(1.0, confidence), 10)


def is_cleared(has_amount: bool, has_category: bool, has_wallet: bool, confidence: float) -> bool:
    return bool(has_amount and has_category and has_wallet and confidence >= CONFIDENCE_CLEARED_THRESHOLD)
