#!/usr/bin/env node
// Validate twa-manifest.json URLs before Bubblewrap init/build
// Ensures absolute URLs where required, and that host/startUrl are valid.
// Exits non-zero on invalid URL so workflow stops (set -e).
import fs from 'fs'

const manifestPath = process.argv[2] || 'twa-manifest.json'

function fail(msg) {
  console.error(`❌ ${msg}`)
  process.exit(1)
}

function ok(msg) {
  console.log(`✅ ${msg}`)
}

if (!fs.existsSync(manifestPath)) {
  fail(`twa-manifest.json not found at ${manifestPath}`)
}

const raw = fs.readFileSync(manifestPath, 'utf8')
let m
try {
  m = JSON.parse(raw)
} catch (e) {
  fail(`Invalid JSON in ${manifestPath}: ${e.message}`)
}

// Helper to check absolute URL (without external valid-url dep)
function isAbsoluteHttpsUrl(value) {
  if (!value || typeof value !== 'string') return false
  try {
    const u = new URL(value)
    return u.protocol === 'https:' && !!u.host
  } catch {
    return false
  }
}

function checkAbsolute(field, value, required = true) {
  if (!value) {
    if (required) fail(`${field} is missing or empty`)
    else {
      console.log(`⚠️  ${field} is empty (optional)`)
      return
    }
  }
  if (!isAbsoluteHttpsUrl(value)) {
    fail(`${field} must be absolute URL (https://...), got: "${value}"`)
  }
  ok(`${field} = ${value}`)
}

function checkHost(host) {
  if (!host) fail('host is missing')
  // host should be bare hostname, not URL
  if (host.includes('://')) {
    fail(`host must be bare hostname (no scheme), got: "${host}" — should be like "example.com"`)
  }
  if (host.includes('/')) {
    fail(`host must not contain path, got: "${host}"`)
  }
  try {
    new URL('https://' + host)
  } catch {
    fail(`host is not a valid hostname: "${host}"`)
  }
  ok(`host = ${host}`)
}

function checkStartUrl(startUrl) {
  if (!startUrl) fail('startUrl is missing')
  // Bubblewrap expects startUrl to be a path like "/" or "/chat" OR absolute URL with same origin.
  // Accept either path (starts with "/") or absolute https URL whose host matches manifest host.
  if (startUrl.startsWith('/')) {
    ok(`startUrl = ${startUrl} (relative path, valid)`)
    return
  }
  // If absolute, must be valid https and share host
  if (!isAbsoluteHttpsUrl(startUrl)) {
    fail(`startUrl must be either path "/..." or absolute https URL, got: "${startUrl}"`)
  }
  try {
    const u = new URL(startUrl)
    if (u.protocol !== 'https:') fail(`startUrl must be https or path, got: "${startUrl}"`)
    if (u.host !== m.host) {
      console.log(`⚠️  startUrl host (${u.host}) differs from manifest host (${m.host})`)
    }
  } catch {
    fail(`startUrl invalid: "${startUrl}"`)
  }
  ok(`startUrl = ${startUrl} (absolute, valid)`)
}

console.log(`🔍 Validating ${manifestPath}...`)
console.log(` packageId: ${m.packageId}`)
console.log(` host: ${m.host}`)
console.log(` startUrl: ${m.startUrl}`)
console.log(` webManifestUrl: ${m.webManifestUrl}`)
console.log(` fullScopeUrl: ${m.fullScopeUrl}`)
console.log(` iconUrl: ${m.iconUrl}`)
console.log('---')

checkHost(m.host)
checkStartUrl(m.startUrl)
checkAbsolute('webManifestUrl', m.webManifestUrl, true)
checkAbsolute('fullScopeUrl', m.fullScopeUrl, false)
checkAbsolute('iconUrl', m.iconUrl, true)
if (m.maskableIconUrl) checkAbsolute('maskableIconUrl', m.maskableIconUrl, false)
if (m.monochromeIconUrl) checkAbsolute('monochromeIconUrl', m.monochromeIconUrl, false)

// Validate URLs are not placeholder/example
const placeholders = ['example.com', 'YOUR_HOST', 'localhost']
for (const f of ['webManifestUrl', 'fullScopeUrl', 'iconUrl']) {
  const v = m[f]
  if (v) {
    for (const p of placeholders) {
      if (v.includes(p) && !v.includes('bendaharaku')) {
        fail(`${f} looks like placeholder (${p}): "${v}"`)
      }
    }
  }
}

// Cross-check: webManifestUrl host should match manifest host
if (m.webManifestUrl) {
  try {
    const u = new URL(m.webManifestUrl)
    if (u.host !== m.host) {
      fail(`webManifestUrl host (${u.host}) must match manifest host (${m.host})`)
    }
  } catch {}
}
if (m.fullScopeUrl) {
  try {
    const u = new URL(m.fullScopeUrl)
    if (u.host !== m.host) {
      fail(`fullScopeUrl host (${u.host}) must match manifest host (${m.host})`)
    }
  } catch {}
}

console.log('---')
console.log('✅ twa-manifest.json validation passed')
