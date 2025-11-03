# Open Questions

Living log of uncertainties and decisions to clarify as the AI Persona plugin evolves. Reply inline or update this file when answers emerge.

1. **Primary AI provider** – Should OpenAI remain the default, or do we target a provider-agnostic interface from the outset (e.g., Anthropic, Azure OpenAI)?
2. **Streaming transport** – Do we prefer SSE, WebSockets, or WordPress Ajax for delivering streaming chat responses to the frontend widget?
3. **Persona storage** – Is custom post meta sufficient for larger prompt payloads, or should we explore custom tables for performance/scalability?
4. **Authentication strategy** – How will external integrations authenticate when hitting the REST `/ai-persona/v1/generate` endpoint (cookies, nonces, application passwords, custom tokens)?
5. **Analytics expectations** – What level of telemetry (counts, durations, content snippets) is acceptable for persona usage tracking while preserving privacy?
6. **Design system alignment** – Should the chat widget follow WordPress block styles, ship its own design tokens, or integrate with a third-party component library?
