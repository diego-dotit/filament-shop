// Verification script for T1.9 - Nuxt 3 scaffold
// Run BEFORE scaffold to see RED, run AFTER to see GREEN

import { existsSync, readFileSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const webDir = __dirname;

let passed = 0;
let failed = 0;

function check(description, condition) {
  if (condition) {
    console.log(`  ✅ PASS: ${description}`);
    passed++;
  } else {
    console.log(`  ❌ FAIL: ${description}`);
    failed++;
  }
}

console.log('\n=== T1.9 Nuxt 3 Scaffold Verification ===\n');

// Check 1: package.json exists
const pkgPath = resolve(webDir, 'package.json');
check('web/package.json exists', existsSync(pkgPath));

// Check 2: package.json has nuxt dependency
if (existsSync(pkgPath)) {
  const pkg = JSON.parse(readFileSync(pkgPath, 'utf8'));
  const deps = { ...pkg.dependencies, ...pkg.devDependencies };
  check('nuxt is listed as a dependency', 'nuxt' in deps);
  check('vue is listed as a dependency', 'vue' in deps);
  check('npm run dev script exists', pkg.scripts && 'dev' in pkg.scripts);
  check('npm run build script exists', pkg.scripts && 'build' in pkg.scripts);
} else {
  check('nuxt is listed as a dependency', false);
  check('vue is listed as a dependency', false);
  check('npm run dev script exists', false);
  check('npm run build script exists', false);
}

// Check 3: nuxt.config.ts exists
check('web/nuxt.config.ts exists', existsSync(resolve(webDir, 'nuxt.config.ts')));

// Check 4: app.vue exists
check('web/app.vue exists', existsSync(resolve(webDir, 'app.vue')));

// Check 5: tsconfig.json exists
check('web/tsconfig.json exists', existsSync(resolve(webDir, 'tsconfig.json')));

// Check 6: node_modules exists (npm install ran)
check('web/node_modules/ exists (npm install succeeded)', existsSync(resolve(webDir, 'node_modules')));

// Check 7: nuxt binary exists in node_modules
check('nuxt binary exists in node_modules/.bin/', existsSync(resolve(webDir, 'node_modules/.bin/nuxt')));

console.log(`\n=== Results: ${passed} passed, ${failed} failed ===\n`);
process.exit(failed > 0 ? 1 : 0);
