# jwtrading — WordPress Redesign

## Stack
- **Hosting:** EasyWP (Namecheap). No SSH, no WP-CLI on server. Deploy via **SFTP only**.
- **WordPress** + **WooCommerce**, payment via **Duitku** (official WooCommerce gateway plugin, sandbox first).
- **Theme:** Kadence (parent) + `jwtrading-child` (all custom presentation code lives here).
- **Plugin:** `jwtrading-core` (ALL business logic, integrations, sync — never put logic in the theme).
- **Editor:** Gutenberg + custom `jwt/*` blocks (+ Kadence Blocks if needed). Client edits content only, never layout/code — enforced via `inc/editor-lock.php`, see below.
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

## Funnel / checkout (ported from the live child theme into jwtrading-core)
- `class-checkout.php` — slim virtual checkout (nama/WA/email + Discord field), terms checkbox (`jwt/terms_url`), buy-now flow (qty 1, skip cart, dupes OK), 2-col layout wrappers, 24h unpaid cancel, fires `jw_kit_tag_subscriber` with form_id `Checkout_Started`.
- `class-thankyou.php` — localized thank-you + "Langkah Selanjutnya" (Discord/WA filterable), hidden billing, minimal totals.
- `class-emails.php` — admin new-order email customer table (incl. Discord), default billing block removed, PRO Elements footer strip (delete when Elementor goes).
- `class-preview-gate.php` — free-preview gate; contract UNCHANGED from live: cookie `jw_free_preview_unlocked`, AJAX `jw_gate_unlock`, form_id `free_preview_gate_keep` → `jw_kit_tag_subscriber`.
- `class-tracking.php` — GTM `GTM-5726L37C` + GA4 `G-43GCQ182TL` + purchase dataLayer (session-deduped).
- The `jw_kit_tag_subscriber` action is consumed by the legacy jw-kit-auto-tagger plugin (keep until JWT_Kit_Sync absorbs it).

## Page conversion status (local)
**Elementor + PRO Elements are DEACTIVATED** (owner approved). All pages block-based; `_elementor_data` metas kept for recovery. Checkout #712 = classic `[woocommerce_checkout]` shortcode (required by the slim-checkout hooks — do NOT switch to blocks checkout). Blog: the 6 SEO articles are now POSTS (same slugs) under `/blog/…`; `class-redirects.php` 301s the old root URLs; posts page = Blog #2957. Migration tool: `Redesign/tools/convert-elementor-to-blocks.php` (wp eval-file) — reuse when repeating this on live.

## Design source of truth (until final design lands)
Old-site values extracted from the backup's `uploads/elementor/css/post-6|45|48.css`: fonts **Space Grotesk (headings) / Montserrat (body)** — self-hosted variable woff2 in `src/fonts/`; square-grid pattern (`src/img/pattern-square.png`) + radial purple tint layered on section backgrounds; buttons = full pills with `inset 0 -4px 4px` shadow; nav pill = `rgba(255,255,255,.05)` + inset + blur; footer = translucent accent gradient card; warm hover accent `--jwt-accent-warm #ff8a36`; container 1440px; `html` font-size `clamp(15px, 1vw+14px, 18px)`. Animations: CSS-only (reveal blur, haze drift, header `.is-scrolled` glass) — deliberately no GSAP.

## Editor locking (inc/editor-lock.php)
Pages (post_type=page) with existing content get `templateLock: 'all'` on the root block list — the section skeleton (which blocks, how many, what order) can't be moved/removed/added-to via the editor UI. What stays editable: inline text (RichText), every block's own Inspector fields (URLs, images, toggles), and — deliberately unlocked — the repeatable items *inside* a section (testimonial cards, FAQ entries, feature cards, stats, curriculum modules, CTA cards), since those `InnerBlocks` never got a `templateLock` prop in `editor.jsx`. A brand-new blank page stays unlocked until it has content, so it can still be built out from scratch; it locks itself the next time it's loaded once something's been saved. One-off exception: filter `jwt/lock_page_editor`, return false for that `$post`. Blog posts (post_type=post) are untouched — always fully flexible core blocks.

## Header / Footer
Fully custom in the child theme (`header.php` / `footer.php` override Kadence; the old Elementor `mainHeader` #45 / `mainFooter` #48 templates are set to draft — do the same on live at launch). Menus: `jwt-primary`, `jwt-footer`, `jwt-legal`, `jwt-social` (social icons auto-match the link URL). Header CTA via `jwt/header_cta` filter (default: Preview Gratis → `/free-content-preview/`). Logo = theme mod `custom_logo`.

## Git
- Repo root: `Redesign/` (one level above `jwtrading/`). Remote: `creativorium/JWlocal` (push as nego94, collaborator). Commit messages: user is **sole author — no Co-Authored-By trailers**.

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
