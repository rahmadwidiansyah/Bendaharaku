import re
from typing import Optional


def get_nominal_smart(text: str) -> Optional[float]:
    text_lower = text.lower()

    match_suffix = re.search(r'(\d+[.,]?\d*)\s*(rb|ribu|k|jt|juta)\b', text_lower)
    if match_suffix:
        angka = float(match_suffix.group(1).replace(",", "."))
        suffix = match_suffix.group(2)
        if suffix in ('rb', 'ribu', 'k'):
            return angka * 1000
        if suffix in ('jt', 'juta'):
            return angka * 1_000_000

    nums = re.findall(r'\b\d+\b', text_lower.replace(".", "").replace(",", ""))
    valid = [float(n) for n in nums if float(n) >= 1000]

    return max(valid) if valid else None
