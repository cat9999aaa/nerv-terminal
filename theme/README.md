# NERV Terminal Theme

NERV Terminal registers runtime WordPress routes for the dashboard archives:

- `/blog/page/N`
- `/projects/page/N`
- `/partners/page/N`

These routes do not need separate Baota/Nginx rewrite rules. They only need the
normal WordPress pseudo-static rule that sends unknown pretty URLs to
`index.php`.

```nginx
location /
{
    try_files $uri $uri/ /index.php?$args;
}

rewrite /wp-admin$ $scheme://$host$uri/ permanent;
```

After uploading or updating the theme, WordPress rewrite rules are refreshed
automatically once. If a host still shows 404 for `/blog/page/N`, open
**Settings -> Permalinks** and click **Save Changes** once, then clear CDN or
page cache.

## 宝塔面板说明

主题归档分页不需要单独写伪静态。宝塔「伪静态」保持标准 WordPress 规则：

```nginx
location /
{
    try_files $uri $uri/ /index.php?$args;
}

rewrite /wp-admin$ $scheme://$host$uri/ permanent;
```

如果 `/blog/page/444/` 404，先在 WordPress 后台「设置 -> 固定链接」点一次
「保存更改」，再清理 Cloudflare、宝塔缓存和页面缓存插件。这个主题会把超出
实际页数的分页地址重定向到稳定的归档页，正常情况下不应该显示 404。
