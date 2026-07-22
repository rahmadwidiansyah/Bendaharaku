import re
from typing import Optional


def extract_subject(text: str) -> Optional[str]:
    match = re.search(r'#([a-zA-Z0-9_]+)', text)
    return match.group(1) if match else None
