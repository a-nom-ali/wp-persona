# Repository Guidelines

## Project Structure & Module Organization
Plugin code lives in `ai-persona/`. PHP services ship under `includes/` with `admin/` for wp-admin UI, `frontend/` for runtime features, and top-level classes like `class-ai-persona.php` and `class-api.php`. Provider abstractions sit under `includes/providers/` so contributors can drop in alternative clients without touching core—Ollama, OpenAI, and Anthropic adapters are available out of the box. Gutenberg assets reside in `blocks/ai-persona-chat/` with supporting JS, while public/static files are kept in `assets/js/` and `assets/css/`. Tests scaffolds live in `tests/phpunit/` and `tests/js/`. Documentation (`README.md`, `ROADMAP.md`, `AGENTS.md`) stays in the repo root—update these when you land new capabilities so implementers remain in sync.

## Build, Test, and Development Commands
Run `composer install` inside `ai-persona/` to wire up the autoloader (additional dependencies will be declared as needed). Use WP-CLI to manage the plugin during development: `wp plugin activate ai-persona`, `wp plugin deactivate ai-persona`, and `wp i18n make-pot ai-persona languages/ai-persona.pot` for localization. When working with Gutenberg assets, build bundles via `wp-scripts build` (or `npm run build` once you add a package script) and commit the compiled files. As we implement Server-Sent Events (SSE) streaming, document any helper commands (e.g., local proxy scripts) in `README.md`.
Keep the local Ollama daemon available on `http://localhost:11434` before running PHP integration tests or manual checks—the default provider relies on the `minimax-m2:cloud` model. Swap to OpenAI or Anthropic via **Settings → AI Persona** when you need remote models; add your API key and override base/model fields if you target non-default regions.

## Coding Style & Naming Conventions
Follow WordPress PHPCS rulesets (`WordPress-Core`, `WordPress-Docs`) with 4-space indentation for PHP, snake_case function names, and prefix everything with `ai_persona_` to avoid collisions. For JavaScript, stick to ESNext modules, 2-space indentation, and descriptive action/filter handles (e.g., `ai-persona/chat-widget`). CSS selectors should be BEM-flavored (`.ai-persona__chat-header`) and draw from the plugin’s lightweight design tokens so we can extend WordPress block styles via CSS variables. Keep hooks self-documenting and add inline comments when behavior differs from WordPress defaults.

Design tokens are centralized in `includes/frontend/design-tokens.php` and exposed via the `ai_persona_design_tokens` filter—extend or override values there instead of hardcoding new custom properties.

## Testing Guidelines
Target PHPUnit-based integration tests via the WordPress testing suite stored under `tests/phpunit/` (create the directory when you add your first test). Name test classes after the component under test (`Test_Prompt_Builder`). For frontend widgets, add Jest or Playwright coverage under `tests/js/` as the UI stabilizes. Ensure new behavior includes happy-path and failure cases, and document manual QA steps in the pull request when automated coverage is pending.

Run PHP unit tests from the plugin root: `composer install` (first run) then `composer test`. The bootstrap stubs core WordPress helpers so provider logic can be validated without a full WP stack. For JavaScript smoke coverage, use `node tests/js/chat-streaming.test.js` (Node 12+).

Hook into `Ai_Persona\get_persona_data()` / `Ai_Persona\compile_persona_prompt()` when you need structured persona details. Filters are provided (`ai_persona_persona_data`, `ai_persona_compiled_prompt`, `ai_persona_resolve_provider`) so integrations can override storage, prompt formulation, or provider selection without touching core classes.

## Commit & Pull Request Guidelines
Mirror the existing history: imperative, sentence-cased summaries (`Add roadmap for planned enhancements`). Reference issues with `Refs #123` when relevant, and keep bodies wrapped at 72 characters. Pull requests must outline scope, testing performed, and migration steps. Include screenshots or GIFs for UI changes, highlight new hooks or filters, and call out security considerations (API key handling, nonce usage) so reviewers can assess risk quickly.

Commit messages now follow the gitmoji pattern: begin each subject with an appropriate emoji (e.g., `✨`, `🐛`, `📝`), followed by a space and the imperative summary (`✨ Add streaming UI states`). Match the emoji to the change type per the [gitmoji.dev](https://gitmoji.dev/) specification.

## Security & Configuration Tips
Store API keys via WordPress options secured with salts and surface filters for custom storage backends when possible. Sanitize and escape all persona fields before output, and guard REST endpoints with capability checks plus nonces. When integrating third-party APIs, document rate limits, timeout defaults, and retry behavior in `README.md` to keep deployers informed.
The default Ollama provider runs locally and avoids transmitting content externally. When using OpenAI or Anthropic adapters, store API keys securely, review data residency constraints, and document any organisation-wide policies for remote inference.

---

**Notes**

- Use this section to capture environment or workflow details that agents and contributors should keep in mind between updates.
- Plugin currently installed on `http://campaign-forge.local` with the plugin directory symlinked to this repository folder.
- Gutenberg chat block now exposes persona selection and header controls via the inspector; create at least one published persona before adding the block to avoid zero-state UX hiccups.
- REST endpoints now accept `persona_id` and automatically assemble the system prompt; pass user inputs as `prompt` and keep nonces/API auth ready for streaming endpoints.
- Each persona edit screen exposes an **Export Persona JSON** button (and REST route `GET /wp-json/ai-persona/v1/persona/{id}`) returning the structured data and compiled prompt for automation workflows.
- Import personas by uploading the exported JSON in the persona editor—ensure the file carries a `.json` extension and save the post to apply it; server-side sanitization mirrors manual entry.
- Programmatic workflows can `POST /wp-json/ai-persona/v1/persona` to create personas (or `POST /persona/{id}` to update) using the same JSON structure as export/import.
- Use `DELETE /wp-json/ai-persona/v1/persona/{id}` to remove personas and `POST /persona/{id}/duplicate` to clone them—mirror these flows in automations instead of scripting raw database writes.
- For n8n/webhooks: leverage `ai_persona_prompt_before_render` to inject runtime context and `ai_persona_response_after_generate` to push responses outbound; maintain HMAC/nonce validation when exposing the REST endpoints publicly.
- Default REST requests rely on WordPress cookies + `wp_rest` nonces; for backend automations, document the use of Application Passwords and enforce HTTPS to keep credentials confidential.
- Local WordPress credentials use `admin`/`admin`; rotate these before deploying outside of development.
- Automation scripts live under `scripts/` (`persona-export.sh`, `persona-import.sh`) and expect `AI_PERSONA_SITE`, `AI_PERSONA_USER`, and `AI_PERSONA_APP_PASSWORD` env vars.
