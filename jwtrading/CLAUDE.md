# jwtrading — WordPress Redesign

## Stack
- **Hosting:** EasyWP (Namecheap). No SSH, no WP-CLI on server. Deploy via **SFTP only**.
- **WordPress** + **WooCommerce**, payment via **Duitku** (official WooCommerce gateway plugin, sandbox first).
- **Theme:** Kadence (parent) + `jwtrading-child` (all custom presentation code lives here).
- **Plugin:** `jwtrading-core` (ALL business logic, integrations, sync — never put logic in the theme).
- **Editor:** Gutenberg + custom `jwt/*` blocks (+ Kadence Blocks if needed). Client edits content only, never layout/code.
- **Assets:** Vite. Build locally with `npm run build`, deploy `dist/` only. Never deploy `src/`, `node_modules/`, or config files.

## Brand
Two base values run everything (see `src/scss/_tokens.scss` + `theme.json` — keep in sync):
- `#7c4dff` purple — accent, headings highlight, CTA buttons (shadow `rgba(124,77,255,.38)`)
- `#08070e` near-black — background. Glows/haze = same purple at low opacity, e.g. hero `radial-gradient(rgba(124,77,255,.3), transparent 65%)` + `blur(20px)`.

## Custom blocks (`jwtrading-child/blocks/`)
All sections are **dynamic blocks**: `block.json` + `render.php` (server-rendered PHP → SEO-friendly markup, design changes never require re-saving content). Content lives in block **attributes**, so pattern/page markup is just `<!-- wp:jwt/x {...} /-->` comments.
- Parents: `jwt/hero`, `jwt/stats`, `jwt/features`, `jwt/curriculum`, `jwt/testimonials`, `jwt/faq` (emits FAQPage JSON-LD), `jwt/cta`, `jwt/course-grid` (queries Woo products).
- Item children (locked to parent via `parent:`): `jwt/stat-item`, `jwt/feature-item`, `jwt/curriculum-item`, `jwt/testimonial-item` (screenshot OR text quote), `jwt/faq-item`.
- Registration + shared helpers (`jwt_section_header_html()`, `jwt_icon()`): `inc/blocks.php`. Blocks auto-register from any `blocks/*/block.json`.
- Editor UI: `src/editor.jsx` — JSX compiled by esbuild to `wp.element.createElement` (WordPress globals, **no** `@wordpress/scripts`, no React bundle). One shared handle `jwt-blocks-editor`.
- Zero front-end JS required by blocks; `src/main.js` adds optional reveal/count-up enhancements only.
- Homepage pattern with real copy: `patterns/homepage.php` (category "JW Trading" in inserter).
- To add a block: new `blocks/<name>/` folder + edit component in `src/editor.jsx` + styles in `src/scss/_blocks.scss`, then `npm run build`.

## Git
- Repo root: `Redesign/` (one level above `jwtrading/`). Commit messages: user is **sole author — no Co-Authored-By trailers**.

## Architecture rules
1. Theme = presentation (templates, styles, enqueues). Plugin = logic (hooks, sync, settings, CPTs).
2. Never block checkout. All external sync calls (Kit, Google Sheets) are wrapped in try/catch and logged; failures retry via cron.
3. All secrets (Kit API key, Sheets webhook URL + shared secret) live in the `jwtrading-core` settings page (`wp_options`), never hardcoded.
4. Every sync attempt is logged to the custom table `{prefix}jwt_sync_log`.
5. Prefix everything: functions `jwt_`, classes `JWT_`, options `jwt_`, hooks `jwt/`.

## Integrations
- **Duitku:** official gateway plugin handles payment + callback (`?wc-api=...`). Do not build custom gateway code. Verify callback URL is excluded from EasyWP cache.
- **Kit (ConvertKit):** existing Kit plugin handles base connection. `class-kit-sync.php` adds order-completion glue: subscribe/tag buyer with product-specific tags via Kit API v4.
- **Google Sheets:** existing Apps Script deployed as Web App. `class-sheets-sync.php` POSTs order JSON (order ID, customer, items, total, payment status) + shared secret token via `wp_remote_post()`.

## Sync flow
`woocommerce_order_status_processing|completed` → `JWT_Woo::dispatch_order_sync()` → calls Kit + Sheets sync → each logs result via `JWT_Sync_Log`. WP-Cron event `jwt_retry_failed_syncs` runs every 15 min and retries failed rows (max 5 attempts). EasyWP cron is traffic-dependent — production uses cron-job.org pinging `wp-cron.php`.

## Local dev
- LocalWP site, PHP 8.1+.
- Vite dev server: `npm run dev` in the child theme; theme detects `dist/hot` file for HMR mode. Production mode reads `dist/.vite/manifest.json`.

## Deploy checklist (SFTP)
1. `npm run build` in child theme
2. Upload: theme PHP + `style.css` + `theme.json` + `dist/` + `blocks/` + `patterns/` + `inc/`, plugin folder
3. Exclude: `src/`, `node_modules/`, `vite.config.js`, `package.json`, `dist/hot`
4. Test live: Duitku sandbox transaction end-to-end, Kit tag applied, row appears in Sheet
5. Redesign only: verify 301 redirect map for changed URLs (Rank Math redirects)

## Coding conventions
- WordPress Coding Standards (spaces per WPCS, Yoda conditions OK but not required).
- Escape all output (`esc_html`, `esc_attr`, `esc_url`), sanitize all input.
- Text domain: `jwtrading`.
