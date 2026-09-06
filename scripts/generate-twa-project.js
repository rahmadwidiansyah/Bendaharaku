#!/usr/bin/env node
// Generate TWA Android project non-interactively from existing twa-manifest.json
// Used in CI when app/build.gradle is missing, as alternative to `bubblewrap init` which needs interactive prompts.
// This bypasses inquirer prompts and directly calls TwaGenerator.
import fs from 'fs'
import path from 'path'
import { TwaManifest, TwaGenerator, ConsoleLog, BufferedLog } from '@bubblewrap/core'

const targetDir = process.argv[2] || 'twa'
const manifestPath = process.argv[3] || path.join(targetDir, 'twa-manifest.json')

async function main() {
  const resolvedManifest = path.resolve(manifestPath)
  const resolvedTarget = path.resolve(targetDir)

  console.log(`🔧 Generating TWA project`)
  console.log(`  manifest: ${resolvedManifest}`)
  console.log(`  target:   ${resolvedTarget}`)

  if (!fs.existsSync(resolvedManifest)) {
    console.error(`❌ Manifest not found: ${resolvedManifest}`)
    process.exit(1)
  }

  // Validate manifest can be parsed
  let twaManifest
  try {
    twaManifest = await TwaManifest.fromFile(resolvedManifest)
  } catch (e) {
    console.error(`❌ Failed to parse twa-manifest: ${e.message}`)
    process.exit(1)
  }

  const validationError = twaManifest.validate()
  if (validationError) {
    console.error(`❌ TwaManifest validation failed: ${validationError}`)
    process.exit(1)
  }
  console.log(`✅ Manifest valid: ${twaManifest.host}${twaManifest.startUrl} (${twaManifest.packageId})`)

  // Ensure target dir exists
  await fs.promises.mkdir(resolvedTarget, { recursive: true })

  // Remove existing project if any (clean generation)
  const twaGenerator = new TwaGenerator()
  // Use BufferedLog to capture output
  const log = new BufferedLog(new ConsoleLog('twa-generate'))
  console.log('⏳ Creating Android project...')
  try {
    // twaGenerator.createTwaProject is internal; we call via shared helper logic
    // Direct call mirrors bubblewrap's shared.generateTwaProject
    await twaGenerator.createTwaProject(resolvedTarget, twaManifest, log, () => {})
    log.flush()
  } catch (e) {
    console.error(`❌ createTwaProject failed: ${e.message}`)
    console.error(e.stack)
    process.exit(1)
  }

  // Save manifest back (ensure generatorApp etc is updated)
  twaManifest.generatorApp = 'bubblewrap-cli'
  await twaManifest.saveToFile(resolvedManifest)
  console.log(`✅ Project generated at ${resolvedTarget}`)

  // Generate checksum (same as Bubblewrap's computeChecksum)
  const crypto = await import('crypto')
  const data = await fs.promises.readFile(resolvedManifest)
  const sum = crypto.createHash('sha1').update(data).digest('hex')
  const checksumPath = path.join(resolvedTarget, 'manifest-checksum.txt')
  await fs.promises.writeFile(checksumPath, sum)
  console.log(`✅ Checksum written: ${sum} -> ${checksumPath}`)

  // Verify gradlew exists
  const gradlew = path.join(resolvedTarget, 'gradlew')
  const buildGradle = path.join(resolvedTarget, 'app', 'build.gradle')
  if (fs.existsSync(gradlew) && fs.existsSync(buildGradle)) {
    console.log(`✅ Android project ready: gradlew + app/build.gradle present`)
  } else {
    console.log(`⚠️  Expected files missing after generation:`)
    console.log(`   gradlew: ${fs.existsSync(gradlew)}`)
    console.log(`   app/build.gradle: ${fs.existsSync(buildGradle)}`)
    // List target dir
    const files = await fs.promises.readdir(resolvedTarget).catch(() => [])
    console.log(`   target contents: ${files.join(', ')}`)
  }
}

main().catch(e => {
  console.error(e)
  process.exit(1)
})
