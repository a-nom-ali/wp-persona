#!/usr/bin/env bash
#
# Import (create or update) a persona via the REST API using WordPress Application Passwords.
# Requirements:
#   - Environment variables:
#       AI_PERSONA_SITE=https://example.com
#       AI_PERSONA_USER=automation-user
#       AI_PERSONA_APP_PASSWORD=xxxx-xxxx-xxxx-xxxx
#   - Arguments:
#       ACTION=create|update
#       JSON_FILE=path to payload (see README for structure)
#       PERSONA_ID (only when ACTION=update)
#
# Examples:
#   Create: AI_PERSONA_SITE=... AI_PERSONA_USER=... AI_PERSONA_APP_PASSWORD=... \\
#           ./scripts/persona-import.sh create persona.json
#   Update: AI_PERSONA_SITE=... AI_PERSONA_USER=... AI_PERSONA_APP_PASSWORD=... \\
#           ./scripts/persona-import.sh update persona.json 123
#

set -euo pipefail

if [[ $# -lt 2 ]]; then
	echo "Usage: AI_PERSONA_SITE=... AI_PERSONA_USER=... AI_PERSONA_APP_PASSWORD=... $0 create|update payload.json [persona_id]" >&2
	exit 1
fi

ACTION="$1"
JSON_FILE="$2"
PERSONA_ID="${3:-}"

if [[ ! -f "$JSON_FILE" ]]; then
	echo "JSON file not found: $JSON_FILE" >&2
	exit 1
fi

: "${AI_PERSONA_SITE:?Set AI_PERSONA_SITE to your WordPress URL}"
: "${AI_PERSONA_USER:?Set AI_PERSONA_USER to a WordPress user with access to personas}"
: "${AI_PERSONA_APP_PASSWORD:?Set AI_PERSONA_APP_PASSWORD to the user's application password}"

if [[ "$ACTION" != "create" && "$ACTION" != "update" ]]; then
	echo "ACTION must be 'create' or 'update'" >&2
	exit 1
fi

ENDPOINT="${AI_PERSONA_SITE%/}/wp-json/ai-persona/v1/persona"
if [[ "$ACTION" == "update" ]]; then
	if [[ -z "$PERSONA_ID" ]]; then
		echo "PERSONA_ID is required when ACTION=update" >&2
		exit 1
	fi
	ENDPOINT="${ENDPOINT}/${PERSONA_ID}"
fi

curl -sS \
	-u "${AI_PERSONA_USER}:${AI_PERSONA_APP_PASSWORD}" \
	-H "Content-Type: application/json" \
	-X POST \
	--data @"${JSON_FILE}" \
	"$ENDPOINT"
