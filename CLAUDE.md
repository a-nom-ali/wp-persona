# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**AI Persona** is a WordPress plugin that enables structured AI persona management. It provides system prompt builders, multiple AI provider integrations (OpenAI, Anthropic, Ollama), REST APIs, and a chat interface via Gutenberg blocks. The plugin emphasizes extensibility through WordPress hooks and filters.

Key concepts:
- **Persona as Prompt**: Each persona is a structured system prompt with role, guidelines, constraints, examples, and variables
- **Provider Abstraction**: Swappable AI providers via `Ai_Persona\Providers\Provider_Interface`
- **Hook-Driven Architecture**: All major operations expose WordPress actions/filters for customization

## Development Environment

### Initial Setup
```bash
# Install PHP dependencies
cd ai-persona
composer install

# The plugin is ready to activate in WordPress
# Configure AI provider settings at Settings → AI Persona
```

### Local WordPress Testing
The plugin expects a WordPress installation. If testing locally:
- Local site URL: http://campaign-forge.local (as indicated by MCP server config)
- Plugin directory: `wp-content/plugins/ai-persona/`

## Essential Commands

### Testing
```bash
# Run PHP unit tests (provider logic, helpers, API endpoints)
cd ai-persona
composer test

# Run WordPress core integration tests (requires test database setup)
composer test:wpunit
# Note: Requires wp-tests-config.php configured with test database

# JavaScript tests (persona editor UI)
cd ../tests/js
npm test
```

### Localization
```bash
# Regenerate translation template after string changes
./scripts/make-pot.sh
# Outputs to languages/ai-persona.pot
```

### Persona Export/Import (CLI)
```bash
# Export persona to JSON (requires Application Password)
AI_PERSONA_SITE=https://example.com \
AI_PERSONA_USER=username \
AI_PERSONA_APP_PASSWORD=app-password \
./scripts/persona-export.sh 123 > persona-123.json

# Import/create persona from JSON
AI_PERSONA_SITE=https://example.com \
AI_PERSONA_USER=username \
AI_PERSONA_APP_PASSWORD=app-password \
./scripts/persona-import.sh create persona.json
```

### Publishing to WordPress.org
```bash
# Package and deploy to WordPress.org SVN (requires credentials)
SVN_USERNAME=your-username \
SVN_PASSWORD=your-password \
./scripts/publish-wporg.sh
# Uses .wp-org-publish-ignore to exclude dev files
```

## Architecture

### Core Components

**Plugin Bootstrap** (`ai-persona/ai-persona.php`)
- Entry point defining constants: `AI_PERSONA_VERSION`, `AI_PERSONA_PLUGIN_FILE`, `AI_PERSONA_PLUGIN_DIR`
- Initializes singleton `\Ai_Persona\Plugin::instance()->boot()` on `plugins_loaded`

**Main Plugin Class** (`includes/class-ai-persona.php`)
- Registers `ai_persona` custom post type
- Enqueues frontend/admin/editor assets
- Registers Gutenberg blocks from `blocks/` directory
- Resolves active AI provider via `ai_persona_resolve_provider` filter

**Persona Data Layer** (`includes/persona.php`)
- `get_persona_data($post_id)`: Returns structured array (role, guidelines, constraints, examples, variables)
- `compile_persona_prompt($persona, $context)`: Builds final system prompt string from structured data
- Both functions apply filters: `ai_persona_persona_data`, `ai_persona_compiled_prompt`

**Provider Abstraction** (`includes/class-api.php`, `includes/providers/`)
- Interface: `Ai_Persona\Providers\Provider_Interface` with `generate()` and `stream()` methods
- Implementations: `Ollama_Provider`, `OpenAI_Provider`, `Anthropic_Provider`, `Null_Provider` (fallback)
- Resolution: Override via `ai_persona_resolve_provider` filter to inject custom providers

**REST API** (`includes/frontend/api-endpoints.php`)
- `POST /wp-json/ai-persona/v1/generate` - Non-streaming persona invocation
- `GET /wp-json/ai-persona/v1/stream` - SSE streaming endpoint
- `GET /wp-json/ai-persona/v1/persona/{id}` - Fetch structured persona data + compiled prompt
- `POST /wp-json/ai-persona/v1/persona` - Create new persona
- `POST /wp-json/ai-persona/v1/persona/{id}` - Update existing persona
- `DELETE /wp-json/ai-persona/v1/persona/{id}` - Delete persona
- `POST /wp-json/ai-persona/v1/persona/{id}/duplicate` - Duplicate persona
- Authentication: WordPress nonce (`X-WP-Nonce`) or Application Passwords

**Frontend Chat** (`includes/frontend/chat-widget.php`, `assets/js/chat.js`)
- Gutenberg block: `ai-persona-chat` renders floating chat widget
- SSE consumption for streaming responses
- Styling via design tokens (`includes/frontend/design-tokens.php`)

**Admin Interface**
- Metaboxes: `includes/admin/metaboxes.php` - Prompt builder fields (role, guidelines, constraints, examples, variables)
- Settings: `includes/admin/settings.php` - API keys, provider selection, analytics opt-in
- Templates: `includes/admin/templates.php` - Pre-built persona templates (support, content curator, etc.)
- Analytics: `includes/admin/analytics.php` - Dashboard showing persona usage, provider stats

### Critical Filters & Actions

**Filters:**
- `ai_persona_resolve_provider` - Override active provider instance
- `ai_persona_prompt_before_render` - Modify prompt before sending to provider
- `ai_persona_compiled_prompt` - Adjust final compiled system prompt
- `ai_persona_persona_data` - Transform structured persona data before use
- `ai_persona_design_tokens` - Extend chat widget CSS tokens
- `ai_persona_chat_attributes` - Modify block/shortcode attributes
- `ai_persona_rest_permissions_check` - Custom REST authentication logic
- `ai_persona_template_library` - Add custom persona templates
- `ai_persona_capability_map` - Adjust persona CRUD capabilities per role

**Actions:**
- `ai_persona_response_after_generate` - React to provider responses (logging, webhooks)
- `ai_persona_logged_event` - Forward analytics events to external systems

### Custom Post Type

**Type:** `ai_persona`
**Meta Keys:**
- `ai_persona_role` (string) - AI role/identity statement
- `ai_persona_guidelines` (array) - Behavioral rules
- `ai_persona_constraints` (array) - Limits and boundaries
- `ai_persona_examples` (array) - Few-shot examples (user/assistant pairs)
- `ai_persona_variables` (array) - Dynamic placeholders for context injection

## Working with Providers

### Adding a New Provider

1. Create provider class in `includes/providers/class-{name}-provider.php`
2. Implement `Provider_Interface` (requires `generate()` and `stream()` methods)
3. Register via filter:
```php
add_filter( 'ai_persona_resolve_provider', function( $provider ) {
    $active_provider = get_option( 'ai_persona_provider', 'ollama' );
    if ( 'custom' === $active_provider ) {
        return new \Ai_Persona\Providers\Custom_Provider();
    }
    return $provider;
} );
```

### Provider Contract
```php
interface Provider_Interface {
    public function generate( string $prompt, array $context ): array;
    public function stream( string $prompt, array $context ): void;
}
```

Expected return structure:
```php
return [
    'output' => 'Generated response text',
    'usage' => [ 'prompt_tokens' => 100, 'completion_tokens' => 50 ],
    'provider' => 'provider-name'
];
```

## Block Development

**Block Location:** `ai-persona/blocks/ai-persona-chat/`
**Build:** Block uses classic WordPress script enqueue (no build step)
- `block.json` - Block metadata, attributes, render callback
- `index.js` - Editor-side block registration (enqueued via `enqueue_block_editor_assets`)
- Dynamic render via PHP callback in `includes/frontend/chat-widget.php`

## Testing Strategy

### PHPUnit Tests (`tests/phpunit/`)
- Provider implementations (OllamaProviderTest, OpenAIProviderTest, etc.)
- API endpoint permissions and streaming (ApiPermissionsTest, ApiStreamTest)
- Persona helper functions (PersonaHelpersTest)
- Design token registry (DesignTokensTest)
- Logging opt-in (LoggingTest)
- CRUD operations (ApiPersonaCrudTest)

### WordPress Integration Tests (`tests/wpunit/`)
- Metabox save routines
- Post meta normalization
- Requires MySQL test database configured in `tests/wpunit/wp-tests-config.php`

### JavaScript Tests (`tests/js/`)
- React admin UI for persona builder
- Run via `npm test` in `tests/js/`

### Playwright E2E (`tests/playwright/`)
- Smoke tests for critical user flows (persona creation, chat interaction)
- Bootstrap with `npm init playwright@latest` in `tests/playwright/`

## n8n Integration

**Workflow Files:** `integrations/`
- `n8n-persona-sync.json` - Creates/updates personas from structured payloads
- `n8n-agent-fetch-persona.json` - Fetches persona and feeds compiled prompt to OpenAI agent

**Usage Pattern:**
1. Import workflow into n8n
2. Configure "AI Persona Basic Auth" credential node with Application Password
3. Use HTTP Request nodes targeting WordPress REST API
4. Store `compiled_prompt` from response for downstream AI nodes

## Logging & Analytics

**Opt-in:** Settings → AI Persona → Analytics & Logging
**Log Location:** `wp-content/uploads/ai-persona/persona.log` (newline-delimited JSON)
**Dashboard:** AI Personas → Analytics (admin menu)

Log entries include:
- Timestamp, persona ID, provider name, prompt length, user input preview
- No sensitive provider responses stored by default
- Hook `ai_persona_logged_event` to forward to external observability systems

## Security Considerations

- **API Keys:** Stored encrypted in `wp_options` using `wp_salt`
- **Input Sanitization:** All prompts sanitized via `wp_kses` before storage/display
- **Nonces:** Required for all admin actions and REST calls (cookie auth)
- **Application Passwords:** Recommended for automation/external integrations
- **Rate Limiting:** Optional transient-based limits per user/IP (filterable via hooks)
- **Capabilities:** Fine-grained via Settings → AI Persona → Permissions

## File Structure Quick Reference

```
ai-persona/
├── ai-persona.php                      # Main plugin file
├── includes/
│   ├── class-ai-persona.php           # Plugin orchestrator (singleton)
│   ├── class-api.php                  # API client with provider resolution
│   ├── persona.php                    # Persona data helpers (get_persona_data, compile_persona_prompt)
│   ├── capabilities.php               # Role capability management
│   ├── logging.php                    # Analytics logging
│   ├── providers/
│   │   ├── interface-provider.php     # Provider contract
│   │   ├── class-ollama-provider.php  # Ollama implementation
│   │   ├── class-openai-provider.php  # OpenAI implementation
│   │   ├── class-anthropic-provider.php # Anthropic implementation
│   │   └── class-null-provider.php    # Fallback no-op provider
│   ├── admin/
│   │   ├── metaboxes.php             # Prompt builder UI
│   │   ├── settings.php              # Plugin settings page
│   │   ├── templates.php             # Persona template library
│   │   └── analytics.php             # Analytics dashboard
│   └── frontend/
│       ├── chat-widget.php           # Chat block render callback
│       ├── design-tokens.php         # CSS token registry
│       └── api-endpoints.php         # REST route handlers
├── blocks/
│   └── ai-persona-chat/
│       ├── block.json                # Block metadata
│       └── index.js                  # Block editor script
├── assets/
│   ├── css/
│   │   └── styles.css                # Frontend/admin styles
│   └── js/
│       ├── chat.js                   # Chat widget SSE handler
│       └── admin.js                  # Metabox enhancements
├── tests/
│   ├── phpunit/                      # Unit tests (providers, helpers, API)
│   ├── wpunit/                       # WordPress integration tests
│   ├── js/                           # JavaScript tests
│   └── playwright/                   # E2E smoke tests
├── integrations/                      # n8n workflow examples
├── scripts/                           # Automation scripts (export, import, publish)
├── languages/                         # i18n pot/po/mo files
└── vendor/                            # Composer dependencies
```

## Common Development Tasks

### Modifying Persona Structure
1. Update metabox fields in `includes/admin/metaboxes.php`
2. Adjust meta key handling in `includes/persona.php` (`get_persona_data`, normalization functions)
3. Update prompt compilation logic in `compile_persona_prompt()`
4. Add tests in `tests/phpunit/PersonaHelpersTest.php`

### Adding a REST Endpoint
1. Define route in `includes/frontend/api-endpoints.php` within `register_routes()`
2. Add permission callback (reuse `rest_permission_check()` or custom logic)
3. Implement handler function
4. Add tests in `tests/phpunit/ApiPersonaCrudTest.php` or similar

### Extending Chat Widget
1. Modify block attributes in `blocks/ai-persona-chat/block.json`
2. Update editor controls in `blocks/ai-persona-chat/index.js`
3. Adjust render logic in `includes/frontend/chat-widget.php`
4. Extend design tokens in `includes/frontend/design-tokens.php` if styling changes needed
5. Update `assets/js/chat.js` for frontend behavior

### Debugging Provider Issues
1. Check active provider setting: Settings → AI Persona → Provider
2. Inspect `ai_persona_resolve_provider` filter applications
3. Add logging in provider `generate()` or `stream()` methods
4. Test provider in isolation via `tests/phpunit/{Provider}Test.php`
5. Verify API keys in `wp_options` table (look for `ai_persona_openai_key`, etc.)

## Versioning

Current version: 0.1.0 (defined in `ai-persona/ai-persona.php`)

Before release:
1. Update version in `ai-persona.php` header and `AI_PERSONA_VERSION` constant
2. Update changelogs in `README.md` and `README_WORDPRESS.org`
3. Commit and tag: `git tag -a v0.1.0 -m "Release 0.1.0"`
4. Run `./scripts/publish-wporg.sh` to deploy to WordPress.org SVN

## MCP Integration

This repository has WordPress MCP configured for local development at http://campaign-forge.local. When using MCP tools to interact with WordPress:
- Use `mcp__wordpress-mcp__*` tools for WordPress operations
- Persona custom post type is queryable via standard WordPress REST API
- Settings stored in `wp_options` table with `ai_persona_` prefix