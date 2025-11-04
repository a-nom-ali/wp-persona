#!/usr/bin/env bash
set -euo pipefail

PLUGIN_SLUG="ai-persona"
SVN_URL="https://plugins.svn.wordpress.org/${PLUGIN_SLUG}"
SVN_DIR="/tmp/${PLUGIN_SLUG}-svn"
BUILD_DIR="/tmp/${PLUGIN_SLUG}-build"
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if [[ -z "${SVN_USERNAME:-}" ]]; then
  echo "SVN_USERNAME env var is required" >&2
  exit 1
fi

if [[ -z "${SVN_PASSWORD:-}" ]]; then
  echo "SVN_PASSWORD env var is required" >&2
  exit 1
fi

rm -rf "${SVN_DIR}" "${BUILD_DIR}"
svn checkout "${SVN_URL}" "${SVN_DIR}" --non-interactive --trust-server-cert --username "${SVN_USERNAME}" --password "${SVN_PASSWORD}"

mkdir -p "${BUILD_DIR}"
rsync -av --delete \
  --exclude-from="${PROJECT_ROOT}/.wp-org-publish-ignore" \
  "${PROJECT_ROOT}/" "${BUILD_DIR}/"

# Copy build into svn trunk
rsync -av --delete "${BUILD_DIR}/" "${SVN_DIR}/trunk/"

# Copy top-level assets for the root of the SVN (banner, icon)
if compgen -G "${BUILD_DIR}/assets/*.png" > /dev/null; then
  rsync -av "${BUILD_DIR}/assets/" "${SVN_DIR}/assets/"
fi

cd "${SVN_DIR}"
svn status

echo "Review the SVN status above. Run 'svn commit' manually when ready."
