# Code Health Notes

Generated during the NERV Terminal hardening pass.

## Admin Control Surface

- `plugin/assets/js/admin-control.js` is currently over 4,000 lines. The audit
  gate allows it below 4,500 lines, but the next cleanup pass should split it
  into feature modules:
  - `ai-services`
  - `geo`
  - `partners`
  - `tools`
  - shared form helpers
- `plugin/assets/css/admin-control.css` still contains legacy dark terminal
  rules for historical panels. A late WordPress-native light override now
  covers the rendered admin surface, and `bin/audit-admin-control.php` tracks
  regression risk. The long-term cleanup is to delete old dark admin rules
  after visual screenshots confirm every settings page.

## Runtime Risks Closed

- GEO slug batch now stores old URL redirect mappings and WordPress old slugs.
- Social cards no longer depend on dynamic `?nerv_cover=` SVG URLs when posts
  lack featured images; real WebP images are materialized under uploads.
- Partner health results now expose redirect counts and final URLs.

## Release Evidence

- Admin screenshots are stored in `qa/screenshots/` for GEO, AI, appearance,
  and partner settings.
- Split release zips are built by `bash build.sh --split`; bundle builds are
  produced by `bash build.sh --bundle`.
- Online smoke checks should be repeated after each production deployment and
  cache purge.
