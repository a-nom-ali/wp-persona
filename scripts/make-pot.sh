#!/usr/bin/env bash
# Regenerate translation template using wp-cli's make-pot command.
# Usage: (from repo root)
#   wp i18n make-pot ./ai-persona ./languages/ai-persona.pot --exclude=node_modules,vendor,tests

set -euo pipefail

if ! command -v wp >/dev/null 2>&1; then
	echo "wp-cli is required to regenerate the POT file." >&2
	exit 1
fi

wp i18n make-pot ./ai-persona ./languages/ai-persona.pot \
	--exclude=node_modules,vendor,tests,integrations,scripts,languages

echo "Updated languages/ai-persona.pot"
