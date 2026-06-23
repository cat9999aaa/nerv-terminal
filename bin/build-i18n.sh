#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

need() {
	if ! command -v "$1" >/dev/null 2>&1; then
		printf 'Missing required command: %s\n' "$1" >&2
		exit 1
	fi
}

extract_php() {
	local domain="$1"
	local output="$2"
	shift 2

	xgettext \
		--from-code=UTF-8 \
		--language=PHP \
		--keyword=__ \
		--keyword=_e \
		--keyword=esc_html__ \
		--keyword=esc_html_e \
		--keyword=esc_attr__ \
		--keyword=esc_attr_e \
		--keyword=_x:1,2c \
		--keyword=_ex:1,2c \
		--keyword=esc_html_x:1,2c \
		--keyword=esc_attr_x:1,2c \
		--keyword=_n:1,2 \
		--keyword=_nx:1,2,4c \
		--add-comments=translators: \
		--package-name="$domain" \
		--msgid-bugs-address="https://dashen.wang/" \
		-o "$output" \
		"$@"
}

extract_js() {
	local domain="$1"
	local output="$2"
	shift 2

	xgettext \
		--from-code=UTF-8 \
		--language=JavaScript \
		--keyword=__ \
		--keyword=_x:1,2c \
		--keyword=_n:1,2 \
		--keyword=_nx:1,2,4c \
		--package-name="$domain" \
		--msgid-bugs-address="https://dashen.wang/" \
		-o "$output" \
		"$@"
}

merge_pots() {
	local output="$1"
	shift

	msgcat --use-first --sort-output -o "$output" "$@"
}

fill_po() {
	local pot="$1"
	local po="$2"
	local locale="$3"

	if [ -f "$po" ]; then
		msgmerge --update --backup=none "$po" "$pot" >/dev/null
	else
		msginit --no-translator --locale="$locale" --input="$pot" --output-file="$po" >/dev/null
	fi
	msgfmt --check --output-file="${po%.po}.mo" "$po"
}

build_js_json() {
	local po="$1"
	local locale="$2"
	local handle="$3"
	local domain="$4"
	local lang_dir="$5"
	shift 5

	php "$ROOT/bin/build-js-i18n.php" "$po" "$lang_dir/$domain-$locale-$handle.json" "$domain" "$@"
}

build_theme() {
	local lang_dir="$ROOT/theme/languages"
	local tmp
	tmp="$(mktemp -d)"
	mkdir -p "$lang_dir"

	extract_php "nerv-terminal" "$tmp/php.pot" \
		"$ROOT/theme/functions.php" \
		"$ROOT/theme/inc/dashboard-render.php" \
		"$ROOT/theme/inc/defaults.php"
	merge_pots "$lang_dir/nerv-terminal.pot" "$tmp/php.pot"

	fill_po "$lang_dir/nerv-terminal.pot" "$lang_dir/zh_CN.po" "zh_CN.UTF-8"
	fill_po "$lang_dir/nerv-terminal.pot" "$lang_dir/ja.po" "ja.UTF-8"
	fill_po "$lang_dir/nerv-terminal.pot" "$lang_dir/en_US.po" "en_US.UTF-8"
	rm -rf "$tmp"
}

build_plugin() {
	local lang_dir="$ROOT/plugin/languages"
	local tmp
	tmp="$(mktemp -d)"
	mkdir -p "$lang_dir"

	extract_php "nerv-core" "$tmp/php.pot" \
		"$ROOT/plugin/nerv-core.php" \
		"$ROOT/plugin/inc/"*.php
	extract_js "nerv-core" "$tmp/js.pot" \
		"$ROOT/plugin/assets/js/admin-control-utils.js" \
		"$ROOT/plugin/assets/js/admin-control.js" \
		"$ROOT/plugin/assets/js/blocks.js"
	merge_pots "$lang_dir/nerv-core.pot" "$tmp/php.pot" "$tmp/js.pot"

	fill_po "$lang_dir/nerv-core.pot" "$lang_dir/nerv-core-zh_CN.po" "zh_CN.UTF-8"
	fill_po "$lang_dir/nerv-core.pot" "$lang_dir/nerv-core-ja.po" "ja.UTF-8"
	fill_po "$lang_dir/nerv-core.pot" "$lang_dir/nerv-core-en_US.po" "en_US.UTF-8"
	for locale in zh_CN ja en_US; do
		build_js_json "$lang_dir/nerv-core-$locale.po" "$locale" "nerv-core-blocks" "nerv-core" "$lang_dir" "$ROOT/plugin/assets/js/blocks.js"
		build_js_json "$lang_dir/nerv-core-$locale.po" "$locale" "nerv-core-admin-control-utils" "nerv-core" "$lang_dir" "$ROOT/plugin/assets/js/admin-control-utils.js"
		build_js_json "$lang_dir/nerv-core-$locale.po" "$locale" "nerv-core-admin-control" "nerv-core" "$lang_dir" "$ROOT/plugin/assets/js/admin-control.js"
	done
	rm -rf "$tmp"
}

need xgettext
need msgcat
need msginit
need msgfmt
need php

build_theme
build_plugin
printf 'Built i18n catalogs for theme and plugin.\n'
