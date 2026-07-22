TRANSFER_KEYWORDS = ['transfer', 'pindah', 'mutasi', 'tf']
DEBT_KEYWORDS = ['ngutang', 'pinjam', 'kasbon', 'cicil']
RECEIVABLE_KEYWORDS = ['piutang', 'dipinjam', 'pinjemin']
INCOME_KEYWORDS = ['gaji', 'dikasih', 'pemasukan', 'cair', 'nemu']

DEBT_CATEGORY_KEYWORDS = ['hutang']
RECEIVABLE_CATEGORY_KEYWORDS = ['piutang']
INCOME_CATEGORY_KEYWORDS = ['pendapatan', 'gaji']


def guess_transaction_intent(text: str, category_name: str) -> str:
    text_lower = text.lower()
    cat_lower = category_name.lower() if category_name else ""

    if any(w in text_lower for w in TRANSFER_KEYWORDS) or 'transfer' in cat_lower:
        return 'transfer'

    if any(w in text_lower for w in DEBT_KEYWORDS) or any(w in cat_lower for w in DEBT_CATEGORY_KEYWORDS):
        return 'debt'

    if any(w in text_lower for w in RECEIVABLE_KEYWORDS) or any(w in cat_lower for w in RECEIVABLE_CATEGORY_KEYWORDS):
        return 'receivable'

    if any(w in text_lower for w in INCOME_KEYWORDS) or any(w in cat_lower for w in INCOME_CATEGORY_KEYWORDS):
        return 'income'

    return 'expense'
