# DotDev Workflow

This project uses **Dot** — a spec-driven development workflow for GitHub Copilot CLI.

## For New Models (After `/clear`)

If you're reading this file, the project has DotDev initialized. To understand the current state:

1. **Check project state:** See `.dot/STATE.md` (if running in an initialized project context)
2. **Understand the workflow:** DotDev uses `/dot:init` → `/dot:new-spec` → `/dot:plan-phase` → `/dot:execute-phase` → `/dot:review` → `/dot:test` → `/dot:commit`
3. **Quick reference:**
   - `/dot:init` — Initialize a project (detects stack, creates config)
   - `/dot:new-spec` — Create a feature spec (conversational discovery)
   - `/dot:quick` — Fast-track single-file changes (no spec needed)
   - `/dot:progress` — Show current workflow state

## Detection

- **DotDev is initialized** if you see `.dot/config.json` in the project
- **Current state** is in `.dot/STATE.md` (tracks phases, specs, tasks)
- **Instructions** are in `.github/copilot-instructions.md`
