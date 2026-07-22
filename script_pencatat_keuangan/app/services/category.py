import re
from typing import List, Optional
from thefuzz import process, fuzz

from app.schemas import CategoryItem


MIN_CATEGORY_SCORE = 60


def match_best_category(text: str, categories: List[CategoryItem]) -> Optional[str]:
    text_lower = text.lower()
    best_name = None
    best_score = 0

    for c in categories:
        raw_kws = c.keyword if c.keyword and c.keyword.strip() not in ('-', '') else c.category_name
        kws = [k.strip().lower() for k in raw_kws.split(',')]

        escaped = [re.escape(k) for k in kws if k]
        if escaped:
            pattern = re.compile(r'\b(?:' + "|".join(escaped) + r')\b', re.IGNORECASE)
            matches = pattern.findall(text_lower)
            if matches:
                score = len(matches) * 100
                if score > best_score:
                    best_score, best_name = score, c.category_name

        if best_score == 0:
            match = process.extractOne(text_lower, kws, scorer=fuzz.token_set_ratio)
            if match and match[1] > best_score:
                best_score, best_name = match[1], c.category_name

    return best_name if best_score >= MIN_CATEGORY_SCORE else None
