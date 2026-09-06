#!/usr/bin/env node
// Bump TWA manifest versionCode (monotonic) and versionName from git tag / env
// Usage: node scripts/bump-twa-version.js --code 123 --name 1.2.0
import fs from 'fs'
const path = 'twa-manifest.json'
const manifest = JSON.parse(fs.readFileSync(path, 'utf8'))

const args = process.argv.slice(2)
let code = null
let name = null
for (let i = 0; i < args.length; i++) {
  if (args[i] === '--code') code = parseInt(args[++i], 10)
  if (args[i] === '--name') name = args[++i]
}
if (code == null) {
  // Fallback: env from GitHub Actions run_number
  const envCode = parseInt(process.env.GITHUB_RUN_NUMBER || process.env.VERSION_CODE || '', 10)
  if (!isNaN(envCode)) code = envCode
}
if (name == null) {
  name = process.env.VERSION_NAME || process.env.GITHUB_REF_NAME || manifest.appVersion
  // If GITHUB_REF is tag v1.2.3, use 1.2.3
  if (name.startsWith('v')) name = name.slice(1)
}

if (code != null) {
  if (code <= manifest.appVersionCode) code = manifest.appVersionCode + 1
  manifest.appVersionCode = code
  console.log(`→ appVersionCode=${code}`)
}
if (name != null) {
  manifest.appVersion = name
  console.log(`→ appVersion=${name}`)
}
fs.writeFileSync(path, JSON.stringify(manifest, null, 2) + '\n')
console.log(`✅ Updated ${path}`)
