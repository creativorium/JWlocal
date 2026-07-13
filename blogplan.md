# Blog Redesign Plan — Archive + Single + Authoring

_Drafted 2026-07-12. Status: proposal, awaiting decisions in §2 and §3._

## 1. Current state (audit)
- **Permalinks:** posts already live at `/blog/%postname%/`. Front page = Homepage (#8), Posts page = "Blog" (#2957) via Reading settings.
- **Rendering:** the child theme has **no `archive.php` / `single.php` / `home.php`** — Kadence (parent) renders blog + single pages today; the child theme only styles `.single-content`. So we own the design by adding our own templates.
- **The blog articles are ALREADY POSTS.** 8 posts total (7 by Ihsan, 1 by it.cular): _Apa Itu Prop Firm_, _Best Prop Firms_, _Funded Account_, _ICT Trading_, _Order Block_, _Smart Money Concept_, _Panduan ICT Trading Strategy_. They resolve under `/blog/…`.
- **Ihsan's 18 PAGES are not blog articles.** They are: the "Blog" archive page (#2957); a batch of internal **"SEO Preview" / "Native Elementor Design Preview"** staging pages (Homepage/Bootcamp/Contact/Discord/etc. design previews); and **6 `*-old-elementor` DRAFT pages** that are duplicates of the posts above.
- Each post currently carries an **inline `<style id="jw-article-reading-width">`** plus imported classic HTML — messy, to be replaced by proper template CSS.
- **Categories:** only "Uncategorized" exists. No real taxonomy yet.
- **Stack:** Yoast SEO, Google Site Kit, LiteSpeed Cache, WooCommerce, jwtrading-core, jw-integrations, Duplicate Post. **ACF is NOT installed.**

## 2. Migration scope — RESOLVED (no migration needed)
Verified in the DB: the blog articles Ihsan sees in **Pages** are the 6 `*-old-elementor` **draft** pages, and each is a near-identical twin of an already-published **Post** (same title/slug, content within ~35 chars, all drafts batch-touched 2026-07-04; the live Posts are the migrated versions from 2026-06-29). So the "move Page → Post" is **already done** — migrating the drafts would just duplicate existing Posts.

The blog = **7 existing Posts** (already under `/blog/…`):
`seo-preview-prop-firm`, `seo-preview-best-prop-firms-indonesia`, `seo-preview-funded-account`, `seo-preview-ict-trading`, `order-block`, `seo-preview-smart-money-concept`, `ict-trading-strategy-untuk-pemula`.

Action (destructive — confirm first):
- **Trash the 6 `*-old-elementor` draft pages** (dupes; drafts are ~35 chars *smaller* than the live Posts, i.e. older). Keep in Trash for recovery, don't permanently delete.
- **Leave the design-preview pages** (Homepage/Bootcamp/Contact/Discord/etc. "Native Design Preview") — not blogs; retire when Elementor is removed.
- No `post_type` conversion required.

## 3. Authoring experience — DECIDED: Classic Editor (posts only), native fields, no ACF
The client sets **featured image + title + subtitle + category + body** (rich text *or* raw HTML); output follows our layout.

- **Classic Editor for `post` only** — `use_block_editor_for_post_type` returns false for `post` (no plugin). One TinyMCE box: **Visual** tab = normal text auto-styled to our layout; **Text** tab = paste raw HTML/code. Pages and other types keep Gutenberg.
- **Featured image** → native Featured Image panel.
- **Title** → native title.
- **Subtitle / description** → native **Excerpt** (surface the Excerpt panel on the classic screen). Shown on the card and under the single title.
- **Categories** → native **hierarchical Category taxonomy** — supports **parent + sub-category** out of the box; the client adds/edits them in the Categories box. This powers the filter + `/blog/category/…` archives + Yoast SEO. (No free-text category field — that would break filtering/SEO.)
- **No ACF** — nothing here needs it; it only earns a place later for *structured* extras (read-time override, author bio, TOC, FAQ schema).

## 4. Templates to build (child theme)
- **`home.php`** (posts page #2957) / **`archive.php`** / **`category.php`** → blog grid:
  - Hero header (title + tagline, jwt/hero look).
  - **Category filter:** links to category archives (SEO-friendly, zero JS) + optional JS instant-filter on the main grid.
  - **Card grid:** featured image (16:9), date (Indonesian format), category chip, title, subtitle (excerpt), hover lift.
  - Pagination.
  - Reusable **`template-parts/blog-card.php`** (archive + "related posts").
- **`single.php`** → article:
  - Header: featured-image banner (or compact jwt/hero with image beside) + category chip + title + subtitle + meta (date · read-time).
  - Body: `.single-content` typography (same as the Privacy page) — headings, lists, images, blockquotes, and styled `pre/code` for raw HTML/code.
  - Footer: back-to-blog, share, **related posts** (same category), **CTA band** (Bootcamp/Discord).
  - Article JSON-LD (or rely on Yoast).
- Remove the per-post inline `<style>` hack; move reading-width + typography into a new `src/scss/_blog.scss`.

## 5. Styling
Reuse jwt tokens + `.single-content`. New `src/scss/_blog.scss`: archive grid, card, filter chips, single header, code-block styling, related grid. Dark theme, responsive (cards 3 → 2 → 1 col).

## 6. SEO / performance / housekeeping
- **Yoast** per-post titles/meta/breadcrumbs (fields already available).
- **LiteSpeed:** purge cache on deploy.
- **Redirects:** old root article URLs already 301'd by `class-redirects.php` — verify.
- **Read time:** computed from word count.
- **Editor lock:** posts stay fully flexible (per CLAUDE.md only pages are locked) — no change.

## 7. Build order (once approved)
1. Confirm migration scope (§2); create categories; assign them to the 8 posts.
2. Classic Editor for posts + surface the Excerpt field (§3).
3. `_blog.scss` + `blog-card.php` partial.
4. `home.php` / `archive.php` / `category.php` + filter.
5. `single.php` + related + CTA + code styling.
6. Remove inline post `<style>`, retest, purge cache.

## Open questions
1. Confirm the 8 posts = the blog, and OK to **trash the 6 old-elementor draft pages**?
2. Body editor: **Classic Editor (recommended)** vs keep Gutenberg vs ACF?
3. Category filter: category-archive links (SEO) vs JS instant filter vs **both**?
4. Single header: full-bleed featured-image banner vs compact jwt/hero with image beside?


//Read below after blog and admin function is fixed
also create a thing when we go live, i will download the backup of the current site like everything and we need to make sure the blog, the order and everything will be able to migrate, is that easy to do?

https://pagespeed.web.dev/analysis/https-jwtradingacademy-creativorium-com/2vlanir9o6?form_factor=mobile
Why the performance on both mobile and desktop is so low?
google said:
Render-blocking requests Est savings of 1,470 ms
Requests are blocking the page's initial render, which may delay LCP. Deferring or inlining can move these network requests out of the critical path.LCPFCPUnscored
URL
Transfer Size
Duration
creativorium.com 1st party
95.9 KiB	5,420 ms
…jquery/jquery.min.js(jwtradingacademy.creativorium.com)
29.3 KiB
750 ms
…jquery/jquery-migrate.min.js(jwtradingacademy.creativorium.com)
5.0 KiB
300 ms
…js/duitku_dom_manipulate.js(jwtradingacademy.creativorium.com)
2.4 KiB
150 ms
…css/woocommerce.min.css(jwtradingacademy.creativorium.com)
20.0 KiB
750 ms
…assets/main-CiT_7xpd.css(jwtradingacademy.creativorium.com)
15.0 KiB
900 ms
…css/global.min.css(jwtradingacademy.creativorium.com)
5.3 KiB
600 ms
…css/header.min.css(jwtradingacademy.creativorium.com)
4.5 KiB
600 ms
…css/footer.min.css(jwtradingacademy.creativorium.com)
2.0 KiB
150 ms
…css/content.min.css(jwtradingacademy.creativorium.com)
6.0 KiB
600 ms
…assets/dashboard.css(jwtradingacademy.creativorium.com)
3.7 KiB
450 ms
…blocks/wc-blocks.css(jwtradingacademy.creativorium.com)
2.8 KiB
150 ms

Reduce JavaScript execution time 1.3 s
Consider reducing the time spent parsing, compiling, and executing JS. You may find delivering smaller JS payloads helps with this. Learn how to reduce Javascript execution time.TBTUnscored
URL
Total CPU Time
Script Evaluation
Script Parse
creativorium.com 1st party
1,234 ms	78 ms	19 ms
https://jwtradingacademy.creativorium.com
1,115 ms
17 ms
3 ms
…jquery/jquery.min.js(jwtradingacademy.creativorium.com)
62 ms
45 ms
15 ms
…assets/main-Cey8dlPO.js(jwtradingacademy.creativorium.com)
57 ms
16 ms
1 ms
Google Tag Manager tag-manager 
584 ms	346 ms	234 ms
/gtag/js?id=G-43GCQ182TL(www.googletagmanager.com)
253 ms
171 ms
80 ms
/gtm.js?id=GTM-5726L37C(www.googletagmanager.com)
240 ms
158 ms
79 ms
/gtag/js?id=G-43GCQ182TL&cx=c&gtm=4e6781(www.googletagmanager.com)
91 ms
17 ms
74 ms
Facebook social 
418 ms	311 ms	104 ms
…config/147…?v=…(connect.facebook.net)
298 ms
235 ms
61 ms
/en_US/fbevents.js(connect.facebook.net)
120 ms
76 ms
43 ms
TikTok social 
221 ms	150 ms	60 ms
…static/main.MTA2MWE1OWMwMQ.js(analytics.tiktok.com)
221 ms
150 ms
60 ms
Unattributable
193 ms	46 ms	0 ms
Unattributable
193 ms
46 ms
0 ms


Forced reflow
A forced reflow occurs when JavaScript queries geometric properties (such as offsetWidth) after styles have been invalidated by a change to the DOM state. This can result in poor performance. Learn more about forced reflows and possible mitigations.Unscored
Source
Total reflow time
https://jwtradingacademy.creativorium.com:667:83
62 ms
[unattributed]
9 ms
…assets/main-Cey8dlPO.js:1:207(jwtradingacademy.creativorium.com)
7 ms
…assets/main-Cey8dlPO.js:1:369(jwtradingacademy.creativorium.com)
13 ms
…assets/main-Cey8dlPO.js:1:1020(jwtradingacademy.creativorium.com)
1 ms
…assets/main-Cey8dlPO.js:3:1729(jwtradingacademy.creativorium.com)
1 ms
