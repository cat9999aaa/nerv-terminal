#!/usr/bin/env bash
set -euo pipefail

ROOT="/www/wwwroot/127_0_0_1"
SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

install_dir() {
	local from="$1"
	local to="$2"
	rm -rf "$to"
	mkdir -p "$(dirname "$to")"
	cp -a "$from" "$to"
}

install_dir "$SRC/theme" "$ROOT/wp-content/themes/nerv-terminal"
install_dir "$SRC/plugin" "$ROOT/wp-content/plugins/nerv-core"

if [ -d "$ROOT/wp-content/uploads/nerv-terminal" ]; then
	upload_owner="$(stat -c '%u:%g' "$ROOT/wp-content/uploads")"
	chown -R "$upload_owner" "$ROOT/wp-content/uploads/nerv-terminal"
fi

echo "Synced NERV Terminal theme and NERV Core plugin to $ROOT"
