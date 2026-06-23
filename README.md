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

Run rewrite-sensitive route checks against the local site:

```bash
php bin/audit-rewrite-routes.php
```

Check a production site the same way:

```bash
php bin/audit-rewrite-routes.php https://dashen.wang
```

Run static checks for the WordPress admin control surface:

```bash
php bin/audit-admin-control.php
```

Run public route and lightweight response health checks:

```bash
php bin/audit-runtime-health.php
php bin/audit-runtime-health.php https://dashen.wang
```

Run runtime acceptance checks against the local WordPress install:

```bash
sudo -u www php bin/audit-acceptance.php /www/wwwroot/127_0_0_1/wp-load.php
```

Run the full hardening goal sweep:

```bash
NERV_SKIP_WP_STATE=1 php bin/audit-goal.php http://127.0.0.1
sudo php bin/audit-legacy-markdown-redirect.php /www/wwwroot/127_0_0_1/wp-load.php
```

Build release packages:

```bash
bash build.sh --split
```

The split build produces:

- `dist/nerv-terminal-theme-0.1.19.zip`
- `dist/nerv-core-plugin-0.1.19.zip`

## Baota / Nginx Rewrite

Baota's WordPress pseudo-static rule should pass unknown pretty URLs to
WordPress. Do not add separate Nginx rules for `/blog/page/N`, `/projects/page/N`,
`/partners/page/N`, `llms.txt`, or article `.md` mirrors unless another site-level
rule intercepts them first.

Use this rule in the site's pseudo-static panel:

```nginx
location /
{
    try_files $uri $uri/ /index.php?$args;
}

rewrite /wp-admin$ $scheme://$host$uri/ permanent;
```

The theme and plugin register the WordPress rewrite rules for:

- `/blog/page/N`
- `/projects/page/N`
- `/partners/page/N`
- `/llms.txt`
- `/llms-full.txt`
- `/feed/json`
- `/{post-slug}.md`

After changing pseudo-static rules, activating the theme/plugin, or importing a
new package, refresh WordPress rewrite rules by opening **Settings ->
Permalinks -> Save Changes** once, or run `flush_rewrite_rules()` from WP-CLI.
If `.md` URLs still return 404, check that Baota's sensitive-file rules are not
blocking every `.md` file before the request reaches WordPress. Blocking
repository files such as `README.md` is fine; blocking all public `*.md` routes
will break Markdown mirrors.

Legacy `.md` URLs created before a GEO slug rewrite redirect to the current
Markdown mirror when the old slug redirect map is available. If front matter
still shows an old Markdown URL, run **NERV Theme -> Tools -> Refresh Markdown
mirrors** once or request the current `.md` URL again so the stale cache is
rebuilt.

The release packages also include `README.md` files inside the theme and plugin
directories so the Baota/Nginx rule travels with the installable zips.

### 宝塔排障说明

宝塔面板的「伪静态」使用上面的 WordPress 规则即可，不需要为
`/blog/page/444/` 或 `/{文章别名}.md` 单独写 rewrite。主题负责注册
`/blog/page/N`、`/projects/page/N`、`/partners/page/N`；插件负责注册
`/llms.txt`、`/llms-full.txt`、`/feed/json` 和 `.md` 镜像。

如果浏览器看到 404，按这个顺序查：

1. 打开 WordPress 后台「设置 -> 固定链接」，不改内容直接点一次「保存更改」。
2. 清理 Cloudflare、宝塔缓存、页面缓存插件缓存，再重新访问同一个 URL。
3. 在宝塔「伪静态」确认 `try_files $uri $uri/ /index.php?$args;` 没有被删。
4. 如果只有 `.md` 404，检查宝塔「防篡改 / 网站安全 / 禁止访问文件」里是否有拦截全部 `*.md` 的规则。可以禁止访问仓库文件 `README.md`，但不能拦截公开文章镜像，例如 `/my-post.md`。
5. 如果 `llms.txt` 里的 `.md` 能访问，但 Markdown front matter 里的 `markdown:` 指向旧地址，打开一次后台「NERV主题 · 工具」里的「刷新 Markdown 镜像」，或重新访问该 `.md`；插件会自动重建过期缓存。GEO 批量改链接留下的旧中文 `.md` 会自动 301 到当前 Markdown 镜像。
6. 如果 `/blog/page/444/` 仍然 404，说明请求没有进入 WordPress 或 WordPress rewrite 没刷新；先保存固定链接，再停用/启用一次 NERV Core 插件触发规则刷新。

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
