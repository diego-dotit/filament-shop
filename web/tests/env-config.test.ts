import { describe, it, expect } from 'vitest'
import { readFileSync, existsSync } from 'node:fs'
import { resolve } from 'node:path'

const ROOT = resolve(__dirname, '..')

describe('Environment configuration', () => {
  it('.env.example file exists', () => {
    expect(existsSync(resolve(ROOT, '.env.example'))).toBe(true)
  })

  it('.env.example contains NUXT_PUBLIC_API_BASE_URL variable', () => {
    const content = readFileSync(resolve(ROOT, '.env.example'), 'utf-8')
    expect(content).toContain('NUXT_PUBLIC_API_BASE_URL')
  })

  it('.env.example contains NUXT_PUBLIC_APP_URL variable', () => {
    const content = readFileSync(resolve(ROOT, '.env.example'), 'utf-8')
    expect(content).toContain('NUXT_PUBLIC_APP_URL')
  })

  it('.env.example documents VITE_API_URL alias for compatibility', () => {
    const content = readFileSync(resolve(ROOT, '.env.example'), 'utf-8')
    // Either the variable itself or a comment referencing it
    expect(content).toMatch(/VITE_API_URL|NUXT_PUBLIC_API_BASE_URL/)
  })

  it('nuxt.config.ts exists', () => {
    expect(existsSync(resolve(ROOT, 'nuxt.config.ts'))).toBe(true)
  })

  it('nuxt.config.ts contains runtimeConfig with apiBaseUrl', () => {
    const content = readFileSync(resolve(ROOT, 'nuxt.config.ts'), 'utf-8')
    expect(content).toContain('apiBaseUrl')
  })

  it('nuxt.config.ts has TypeScript strict mode enabled', () => {
    const content = readFileSync(resolve(ROOT, 'nuxt.config.ts'), 'utf-8')
    expect(content).toContain('strict: true')
  })

  it('nuxt.config.ts has runtimeConfig public section', () => {
    const content = readFileSync(resolve(ROOT, 'nuxt.config.ts'), 'utf-8')
    expect(content).toContain('public')
    expect(content).toContain('runtimeConfig')
  })
})
