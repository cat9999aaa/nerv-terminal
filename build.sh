#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DIST="$ROOT/dist"
VERSION="${NERV_VERSION:-0.1.11}"

usage() {
	printf 'Usage: %s --bundle | --split | --clean\n' "$0"
}

need_zip() {
	if ! command -v zip >/dev/null 2>&1; then
		printf 'Missing required command: zip\n' >&2
		exit 1
	fi
}

prepare_dist() {
	mkdir -p "$DIST"
}

zip_dir() {
	local source_dir="$1"
	local archive="$2"
	local entry_name="$3"
	local tmp
	tmp="$(mktemp -d)"
	cp -a "$source_dir" "$tmp/$entry_name"
	(
		cd "$tmp"
		zip -qr "$archive" "$entry_name" \
			-x '*/.DS_Store' \
			-x '*/node_modules/*' \
			-x '*/vendor/*' \
			-x '*/dist/*'
	)
	rm -rf "$tmp"
}

build_split() {
	need_zip
	prepare_dist
	zip_dir "$ROOT/theme" "$DIST/nerv-terminal-theme-$VERSION.zip" "nerv-terminal"
	zip_dir "$ROOT/plugin" "$DIST/nerv-core-plugin-$VERSION.zip" "nerv-core"
	printf 'Created split packages in %s\n' "$DIST"
}

build_bundle() {
	need_zip
	prepare_dist
	local tmp
	tmp="$(mktemp -d)"
	mkdir -p "$tmp/nerv-terminal-bundle/wp-content/themes" "$tmp/nerv-terminal-bundle/wp-content/plugins"
	cp -a "$ROOT/theme" "$tmp/nerv-terminal-bundle/wp-content/themes/nerv-terminal"
	cp -a "$ROOT/plugin" "$tmp/nerv-terminal-bundle/wp-content/plugins/nerv-core"
	cp -a "$ROOT/README.md" "$tmp/nerv-terminal-bundle/README.md"
	(
		cd "$tmp"
		zip -qr "$DIST/nerv-terminal-bundle-$VERSION.zip" "nerv-terminal-bundle" \
			-x '*/.DS_Store' \
			-x '*/node_modules/*' \
			-x '*/vendor/*' \
			-x '*/dist/*'
	)
	rm -rf "$tmp"
	printf 'Created bundle package in %s\n' "$DIST"
}

case "${1:-}" in
	--bundle)
		build_bundle
		;;
	--split)
		build_split
		;;
	--clean)
		rm -rf "$DIST"
		printf 'Removed %s\n' "$DIST"
		;;
	-h|--help|'')
		usage
		;;
	*)
		usage >&2
		exit 1
		;;
esac
