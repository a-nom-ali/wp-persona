# Open Questions

Living log of uncertainties and decisions to clarify as the AI Persona plugin evolves. Reply inline or update this file when answers emerge.

## Active Questions

_None at the moment. Add new items as they arise._

## Resolved Answers

1. **Primary AI provider** – Target a provider-agnostic interface from the outset so multiple vendors (OpenAI, Anthropic, Azure OpenAI) can plug in via filters.
2. **Streaming transport** – Use Server-Sent Events (SSE) for delivering streaming chat responses.
3. **Persona storage** – Custom post meta is sufficient for prompt payloads; monitor for performance issues before introducing custom tables.
4. **Authentication strategy** – Follow the WordPress way: default to nonces/capabilities for logged-in flows and support Application Passwords (or similar core mechanisms) for external integrations.
5. **Analytics expectations** – Use judgement to ship privacy-preserving aggregated metrics; avoid capturing raw prompt/response bodies unless explicitly opted in.
6. **Design system alignment** – Ship a lightweight, WordPress-native design token layer that extends block styles via CSS variables without third-party frameworks.
