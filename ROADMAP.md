# ROADMAP.md — Planned Enhancements

## 0.1.0 – Foundation
- [x] Establish plugin bootstrap, namespaces, and directory layout.
- [x] Register `ai_persona` custom post type with placeholder supports.
- [x] Scaffold admin metaboxes and settings screen with nonce and capability checks.
- [x] Provide REST endpoint stub and shortcode/block rendering hooks.
- [x] Register provider abstraction with default Ollama (minimax-m2:cloud) implementation.
- [x] Wire WordPress test suite bootstrap and sample tests for metabox saves.

## 0.2.0 – Persona Authoring
- [x] Flesh out structured prompt storage (role/guidelines/constraints/examples/variables).
- [x] Add autosave-aware React admin UI for prompt sections.
- [x] Implement validation and preview within wp-admin.
- [x] Document provider resolution filters so third parties can register clients.
- [x] Provide import/export tooling for personas (JSON download + upload).

## 0.3.0 – Chat Experience
- [x] Connect REST endpoint to provider abstraction with SSE streaming pipeline.
- [x] Build frontend chat widget (state management, SSE event piping, persona switching).
- [x] Add PHPUnit/JS smoke tests covering provider resolution and streaming error paths.
- [x] Provide Gutenberg block controls for persona selection and display options.
- [x] Ship design token registry and CSS variable bridge for block themes.

## 0.4.0 – Integrations & Extensibility
- [x] Ship optional remote provider adapters (OpenAI, Anthropic) reusing provider abstraction.
- [x] Add export/import flows (JSON + WordPress REST).
- [x] Document filters/actions; ship reference implementations for n8n and webhooks.
- [x] Implement WordPress-native authentication flows (nonces, application passwords) for external consumers.
- [x] Provide REST automation endpoints for persona lifecycle (create/update/delete/duplicate).

## 1.0.0 – Release Hardening
- [x] Implement analytics + logging opt-in.
- [x] Finalize localization files and developer docs.
- [x] Achieve test coverage targets across PHP/JS; add Playwright smoke tests.
- [ ] Publish to WordPress.org with deployment workflow.

## Backlog / Nice to Have
- [x] AI-assisted prompt refinement wizard.
- [x] Persona template marketplace or syncing mechanism.
- [x] Fine-grained capability mapping for persona creation vs usage.
- [x] Enhanced analytics dashboard with aggregated metrics and privacy guardrails.
- [x] Integration with other AI providers (e.g., Gemini, OpenRouter, etc).
- [x] Tabbed Settings
- [x] Add Documentation tab to Settings
- [x] Add Developer Documentation tab to Settings
- [x] Advanced variables (e.g., pull from WP queries).
- [x] Multi-persona switching in chat.
- [x] Webhook support for n8n-like integrations.
