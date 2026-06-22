# NERV Terminal Monorepo

Source tree for the NERV Terminal WordPress theme and companion NERV Core plugin. The public theme surface is rebuilt with the local EXOFRAME layers adapted from [`cat9999aaa/dashen_ui_kit`](https://github.com/cat9999aaa/dashen_ui_kit) while preserving the original NERV dashboard layout.

## Principles

- Keep the WordPress page layout stable: header, three-column dashboard, footer, mobile appbar, and bottom tabs stay in their existing positions.
- Keep frontend files small and layered: token, palette, semantic, layout, component, element, and motion files are separate.
- Keep palette files independent from components. Components consume semantic variables such as `--color-success`, `--color-primary`, and `--color-line`.
- Keep the theme and plugin open-source ready with reproducible local verification.

## Layout

- `theme/`: Block Theme visual layer.
- `theme/assets/css/frontend.css`: small CSS entrypoint.
- `theme/assets/css/exoframe/`: modular EXOFRAME frontend layers generated for this theme.
- `plugin/`: NERV Core data and service layer.
- `bin/`: local development helpers.
- `qa/`: screenshots and verification artifacts.

## Local Runtime

The local WordPress root is `/www/wwwroot/127_0_0_1`. Runtime copies are installed as:

- `/www/wwwroot/127_0_0_1/wp-content/themes/nerv-terminal`
- `/www/wwwroot/127_0_0_1/wp-content/plugins/nerv-core`

Sync local code into the WordPress test site:

```bash
bash bin/sync-local.sh
```

If the WordPress directories are owned by the web user, run the sync with appropriate privileges and return ownership to the web user after copying.

## Verification

Run focused frontend checks against `http://127.0.0.1`:

```bash
php bin/audit-frontend.php
```

Run runtime acceptance checks against the local WordPress install:

```bash
sudo -u www php bin/audit-acceptance.php /www/wwwroot/127_0_0_1/wp-load.php
```

Build release packages:

```bash
bash build.sh --split
```

The split build produces:

- `dist/nerv-terminal-theme-0.1.3.zip`
- `dist/nerv-core-plugin-0.1.3.zip`

## Online Updates

NERV Terminal uses GitHub Releases as its public update source. The theme and
plugin each register with the native WordPress updater, so installed sites can
see new versions in the WordPress updates screen.

- Release source: `https://github.com/cat9999aaa/nerv-terminal/releases/latest`
- Theme package asset: `nerv-terminal-theme-x.x.x.zip`
- Plugin package asset: `nerv-core-plugin-x.x.x.zip`
- Admin status page: `NERV主题 · 在线更新`

Every release note should clearly describe what changed because the same text is
shown inside the WordPress admin update page.

## i18n

Build gettext catalogs for the theme and companion plugin with:

```bash
bash bin/build-i18n.sh
```

The script generates POT, PO, and MO files for `zh_CN`, `ja`, and `en_US`.

## License

GPL-2.0-or-later. See `LICENSE`.
