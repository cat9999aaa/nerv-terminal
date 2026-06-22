# NERV Core Plugin

NERV Core registers machine-readable GEO routes:

- `/llms.txt`
- `/llms-full.txt`
- `/feed/json`
- `/{post-slug}.md`

Use the normal Baota/Nginx WordPress pseudo-static rule:

```nginx
location /
{
    try_files $uri $uri/ /index.php?$args;
}

rewrite /wp-admin$ $scheme://$host$uri/ permanent;
```

Do not add a separate `.md` rewrite unless another server rule is blocking
Markdown paths before WordPress receives them. Baota security rules may block
repository files such as `README.md`, but they must not block public article
mirrors like `/my-post.md`.

After uploading or updating the plugin, WordPress rewrite rules are refreshed
automatically once. If `.md` mirrors still show 404, save **Settings ->
Permalinks** once and clear CDN or page cache.

## 宝塔面板说明

插件的 `.md`、`llms.txt`、`llms-full.txt` 和 `feed/json` 都是 WordPress
动态路由，不需要在宝塔里单独写一条 `.md` rewrite。宝塔「伪静态」保持标准
WordPress 规则即可：

```nginx
location /
{
    try_files $uri $uri/ /index.php?$args;
}

rewrite /wp-admin$ $scheme://$host$uri/ permanent;
```

如果 `/文章别名.md` 404，而 `/llms.txt` 正常，优先检查宝塔「防篡改 / 网站安全 /
禁止访问文件」是否拦截了全部 `*.md`。可以阻止访问仓库里的 `README.md`，但要让
公开文章镜像如 `/my-post.md` 进入 WordPress。改完规则后保存 WordPress
「固定链接」一次，并清理 CDN/页面缓存。
