#!/usr/bin/env bash
set -euo pipefail
# Generate Android release keystore for TWA signing
# Usage: ./scripts/generate-keystore.sh
# Output: release.keystore (DO NOT COMMIT) + base64 for GitHub Secret

KEYSTORE="release.keystore"
ALIAS="bendaharaku"
VALIDITY=10000

if [[ -f "$KEYSTORE" ]]; then
  echo "⚠️  $KEYSTORE already exists — remove first if you want to regenerate: rm $KEYSTORE"
  exit 1
fi

echo "🔐 Generating $KEYSTORE (RSA 2048, 10k days)..."
read -s -p "Enter keystore password: " STOREPASS; echo
read -s -p "Confirm password: " STOREPASS2; echo
if [[ "$STOREPASS" != "$STOREPASS2" ]]; then echo "❌ Password mismatch"; exit 1; fi
read -p "Enter key alias [bendaharaku]: " IN_ALIAS
ALIAS=${IN_ALIAS:-$ALIAS}

keytool -genkey -v \
  -keystore "$KEYSTORE" \
  -alias "$ALIAS" \
  -keyalg RSA -keysize 2048 -validity $VALIDITY \
  -storepass "$STOREPASS" -keypass "$STOREPASS" \
  -dname "CN=Bendaharaku, OU=App, O=Bendaharaku, L=Jakarta, S=DKI Jakarta, C=ID"

echo ""
echo "✅ Keystore created: $KEYSTORE"
echo ""
echo "📋 SHA256 fingerprint (for assetlinks.json & GitHub Secret):"
keytool -list -v -keystore "$KEYSTORE" -storepass "$STOREPASS" | grep -A1 "SHA256" || true

echo ""
echo "📦 Base64 for GitHub Secret ANDROID_KEYSTORE_BASE64:"
base64 -w0 "$KEYSTORE" | head -c 80; echo "..."
echo ""
echo "Full base64 length: $(base64 -w0 "$KEYSTORE" | wc -c) chars"
echo ""
echo "➡️  Next steps:"
echo "   1. gh secret set ANDROID_KEYSTORE_BASE64 < <(base64 -w0 $KEYSTORE)"
echo "   2. gh secret set ANDROID_KEYSTORE_PASSWORD --body \"$STOREPASS\""
echo "   3. gh secret set ANDROID_KEY_ALIAS --body \"$ALIAS\""
echo "   4. gh secret set ANDROID_KEY_PASSWORD --body \"$STOREPASS\""
echo "   5. Copy SHA256 above to public/.well-known/assetlinks.json and twa-manifest signing"
echo "   ⚠️  NEVER commit $KEYSTORE — already gitignored via /release.keystore"
