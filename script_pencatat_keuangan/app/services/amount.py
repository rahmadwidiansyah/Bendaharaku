import re
from typing import Optional


def get_nominal_smart(text: str) -> Optional[float]:
    text_lower = text.lower()

    match_suffix = re.search(r'\b(\d+[.,]?\d*)\s*(rb|ribu|rbu|k|jt|juta|jtr|m)\b', text_lower)
    if match_suffix:
        angka = float(match_suffix.group(1).replace(",", "."))
        suffix = match_suffix.group(2)
        if suffix in ('rb', 'ribu', 'rbu', 'k'):
            return angka * 1000
        if suffix in ('jt', 'juta', 'jtr', 'm'):
            return angka * 1_000_000

    nums = re.findall(r'\b\d+\b', text_lower.replace(".", "").replace(",", ""))
    valid = [float(n) for n in nums if float(n) >= 1000]

    return max(valid) if valid else None
