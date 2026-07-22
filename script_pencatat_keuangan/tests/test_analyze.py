from app.main import app
from app.schemas import AnalyzeRequest, AnalyzeResponse
from app.services.amount import get_nominal_smart
from app.services.intent import guess_transaction_intent
from app.services.subject import extract_subject
from app.services.wallet import match_wallets
from app.services.category import match_best_category
from app.services.confidence import calculate_confidence, is_cleared
from fastapi.testclient import TestClient

client = TestClient(app)


class TestHealth:
    def test_health_endpoint(self):
        r = client.get("/health")
        assert r.status_code == 200
        assert r.json()["status"] == "ok"
        assert r.json()["service"] == "ai-parser"

    def test_liveness_endpoint(self):
        r = client.get("/live")
        assert r.status_code == 200
        assert r.json()["status"] == "alive"

    def test_readiness_endpoint(self):
        r = client.get("/ready")
        assert r.status_code == 200
        assert r.json()["status"] == "ready"
        assert r.json()["service"] == "ai-parser"


class TestAmountParser:
    def test_suffix_rb(self):
        assert get_nominal_smart("beli nasi 25rb") == 25000.0

    def test_suffix_ribu(self):
        assert get_nominal_smart("bayar 50 ribu") == 50000.0

    def test_suffix_k(self):
        assert get_nominal_smart("transfer 100k") == 100000.0

    def test_suffix_jt(self):
        assert get_nominal_smart("gaji 5jt") == 5_000_000.0

    def test_suffix_juta(self):
        assert get_nominal_smart("beli rumah 2 juta") == 2_000_000.0

    def test_decimal_suffix(self):
        assert get_nominal_smart("beli 1.5jt") == 1_500_000.0

    def test_bare_number(self):
        assert get_nominal_smart("beli 50000") == 50000.0

    def test_no_amount(self):
        assert get_nominal_smart("halo apa kabar") is None

    def test_small_number_ignored(self):
        assert get_nominal_smart("beli 500") is None


class TestSubjectExtractor:
    def test_hashtag(self):
        assert extract_subject("pinjam 100rb #budi") == "budi"

    def test_no_hashtag(self):
        assert extract_subject("beli nasi goreng") is None

    def test_underscore_hashtag(self):
        assert extract_subject("bayar #tagihan_Listrik") == "tagihan_Listrik"


class TestIntentGuesser:
    def test_transfer(self):
        assert guess_transaction_intent("transfer 50rb", "") == "transfer"

    def test_debt(self):
        assert guess_transaction_intent("pinjam 100rb", "") == "debt"

    def test_receivable(self):
        assert guess_transaction_intent("piutang 200rb", "") == "receivable"

    def test_income(self):
        assert guess_transaction_intent("gaji 5jt", "") == "income"

    def test_expense_fallback(self):
        assert guess_transaction_intent("beli nasi goreng", "") == "expense"

    def test_intent_from_category(self):
        assert guess_transaction_intent("beli rumah", "hutang") == "debt"


class TestConfidence:
    def test_full_confidence(self):
        c = calculate_confidence(True, True, True, True)
        import math
        assert math.isclose(c, 1.0, rel_tol=1e-9)

    def test_no_amount(self):
        c = calculate_confidence(False, True, True, False)
        assert c == 0.6

    def test_minimal(self):
        c = calculate_confidence(False, False, False, False)
        assert c == 0.0

    def test_cleared_threshold(self):
        assert is_cleared(True, True, True, 0.85) is True
        assert is_cleared(True, True, True, 0.79) is False
        assert is_cleared(False, True, True, 0.9) is False


class TestAnalyzeEndpoint:
    def test_expense(self):
        r = client.post("/analyze", json={
            "text": "beli nasi goreng 25rb",
            "wallets": [],
            "categories": [{"category_name": "Makanan", "keyword": "makan,nasi,goreng"}],
        })
        assert r.status_code == 200
        data = r.json()
        assert data["success"] is True
        assert data["amount"] == 25000.0
        assert data["transaction_type"] == "expense"
        assert data["category"] == "Makanan"
        assert data["is_cleared"] is False

    def test_transfer(self):
        r = client.post("/analyze", json={
            "text": "transfer 50rb dari bca ke gopay",
            "wallets": [
                {"name": "BCA", "group_type": "Liquid", "keyword": "bca"},
                {"name": "GoPay", "group_type": "Liquid", "keyword": "gopay"},
            ],
            "categories": [{"category_name": "Transfer", "keyword": "transfer"}],
        })
        assert r.status_code == 200
        data = r.json()
        assert data["amount"] == 50000.0
        assert data["transaction_type"] == "transfer"
        assert data["source_wallet"] == "BCA"
        assert data["destination_wallet"] == "GoPay"
        assert data["is_cleared"] is True

    def test_debt(self):
        r = client.post("/analyze", json={
            "text": "pinjam 100rb dari budi #hutang",
            "wallets": [{"name": "Cash", "group_type": "Liquid", "keyword": "cash"}],
            "categories": [{"category_name": "Hutang", "keyword": "hutang"}],
        })
        assert r.status_code == 200
        data = r.json()
        assert data["transaction_type"] == "debt"
        assert data["subject"] == "hutang"

    def test_income(self):
        r = client.post("/analyze", json={
            "text": "gaji 5jt",
            "wallets": [{"name": "BCA", "group_type": "Liquid", "keyword": "bca"}],
            "categories": [{"category_name": "Gaji", "keyword": "gaji"}],
        })
        assert r.status_code == 200
        data = r.json()
        assert data["transaction_type"] == "income"
        assert data["amount"] == 5_000_000.0


class TestNoAuth:
    def test_no_api_key_required(self):
        r = client.post("/analyze", json={
            "text": "beli kopi 10rb",
            "wallets": [],
            "categories": [],
        })
        assert r.status_code == 200
        data = r.json()
        assert data["success"] is True
        assert data["amount"] == 10000.0
