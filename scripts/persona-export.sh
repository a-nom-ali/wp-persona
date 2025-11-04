#!/usr/bin/env bash
#
# Export a persona JSON payload via the REST API using WordPress Application Passwords.
# Requirements:
#   - Environment variables:
#       AI_PERSONA_SITE=https://example.com
#       AI_PERSONA_USER=automation-user
#       AI_PERSONA_APP_PASSWORD=xxxx-xxxx-xxxx-xxxx
#   - Argument: PERSONA_ID
#
# Example:
#   AI_PERSONA_SITE=https://example.com \
#   AI_PERSONA_USER=automation-user \
#   AI_PERSONA_APP_PASSWORD=app-pass-here \
#   ./scripts/persona-export.sh 123 > persona-123.json
#

set -euo pipefail

if [[ $# -ne 1 ]]; then
	echo "Usage: AI_PERSONA_SITE=... AI_PERSONA_USER=... AI_PERSONA_APP_PASSWORD=... $0 PERSONA_ID" >&2
	exit 1
fi

: "${AI_PERSONA_SITE:?Set AI_PERSONA_SITE to your WordPress URL}"
: "${AI_PERSONA_USER:?Set AI_PERSONA_USER to a WordPress user with access to personas}"
: "${AI_PERSONA_APP_PASSWORD:?Set AI_PERSONA_APP_PASSWORD to the user's application password}"

PERSONA_ID="$1"

curl -sS \
	-u "${AI_PERSONA_USER}:${AI_PERSONA_APP_PASSWORD}" \
	-H "Accept: application/json" \
	"${AI_PERSONA_SITE%/}/wp-json/ai-persona/v1/persona/${PERSONA_ID}"
