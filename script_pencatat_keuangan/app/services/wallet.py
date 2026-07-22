import re
from typing import List, Tuple, Optional
from thefuzz import process, fuzz

from app.schemas import WalletItem


FUZZY_THRESHOLD = 85
SYSTEM_GROUP_TYPE = 'System'


def match_wallets(text: str, wallets: List[WalletItem]) -> List[str]:
    text_lower = text.lower()
    matched: List[Tuple[int, str]] = []

    for w in wallets:
        if w.group_type == SYSTEM_GROUP_TYPE:
            continue

        raw_kws = w.keyword if w.keyword and w.keyword.strip() not in ('-', '') else w.name
        kws = [k.strip().lower() for k in raw_kws.split(',')]

        found = False
        for kw in kws:
            if kw and re.search(r'\b' + re.escape(kw) + r'\b', text_lower):
                matched.append((text_lower.find(kw), w.name))
                found = True
                break

        if not found:
            match = process.extractOne(text_lower, kws, scorer=fuzz.token_set_ratio)
            if match and match[1] >= FUZZY_THRESHOLD:
                matched.append((999, w.name))

    matched.sort(key=lambda x: x[0])
    return list(dict.fromkeys([w[1] for w in matched]))
