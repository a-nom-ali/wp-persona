# Repository Guidelines

## Project Structure & Module Organization
Plugin code lives in `ai-persona/`. PHP services ship under `includes/` with `admin/` for wp-admin UI, `frontend/` for runtime features, and top-level classes like `class-ai-persona.php` and `class-api.php`. Gutenberg assets reside in `blocks/ai-persona-chat/` with supporting JS, while public/static files are kept in `assets/js/` and `assets/css/`. Tests scaffolds live in `tests/phpunit/` and `tests/js/`. Documentation (`README.md`, `ROADMAP.md`, `AGENTS.md`) stays in the repo root—update these when you land new capabilities so implementers remain in sync.

## Build, Test, and Development Commands
Run `composer install` inside `ai-persona/` to wire up the autoloader (additional dependencies will be declared as needed). Use WP-CLI to manage the plugin during development: `wp plugin activate ai-persona`, `wp plugin deactivate ai-persona`, and `wp i18n make-pot ai-persona languages/ai-persona.pot` for localization. When working with Gutenberg assets, build bundles via `wp-scripts build` (or `npm run build` once you add a package script) and commit the compiled files. Document any new commands in `README.md`.

## Coding Style & Naming Conventions
Follow WordPress PHPCS rulesets (`WordPress-Core`, `WordPress-Docs`) with 4-space indentation for PHP, snake_case function names, and prefix everything with `ai_persona_` to avoid collisions. For JavaScript, stick to ESNext modules, 2-space indentation, and descriptive action/filter handles (e.g., `ai-persona/chat-widget`). CSS selectors should be BEM-flavored (`.ai-persona__chat-header`). Keep hooks self-documenting and add inline comments when behavior differs from WordPress defaults.

## Testing Guidelines
Target PHPUnit-based integration tests via the WordPress testing suite stored under `tests/phpunit/` (create the directory when you add your first test). Name test classes after the component under test (`Test_Prompt_Builder`). For frontend widgets, add Jest or Playwright coverage under `tests/js/` as the UI stabilizes. Ensure new behavior includes happy-path and failure cases, and document manual QA steps in the pull request when automated coverage is pending.

## Commit & Pull Request Guidelines
Mirror the existing history: imperative, sentence-cased summaries (`Add roadmap for planned enhancements`). Reference issues with `Refs #123` when relevant, and keep bodies wrapped at 72 characters. Pull requests must outline scope, testing performed, and migration steps. Include screenshots or GIFs for UI changes, highlight new hooks or filters, and call out security considerations (API key handling, nonce usage) so reviewers can assess risk quickly.

## Security & Configuration Tips
Store API keys via WordPress options secured with salts and surface filters for custom storage backends when possible. Sanitize and escape all persona fields before output, and guard REST endpoints with capability checks plus nonces. When integrating third-party APIs, document rate limits, timeout defaults, and retry behavior in `README.md` to keep deployers informed.
