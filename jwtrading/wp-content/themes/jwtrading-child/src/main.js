import './main.scss';

// Progressive enhancement only — every block is fully usable without JS.
document.documentElement.classList.add('js');

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// --- Fix Kadence's full-bleed calc for the scrollbar's width ------------------
// Kadence's .alignfull/.alignwide CSS does `width:100vw; margin-left:calc(50% -
// 100vw/2)` to break full-width blocks out to the viewport edge. On Windows,
// 100vw includes the vertical scrollbar's width (~15-17px) while the visible
// content area does not, so every full-width section (hero included) ends up
// shifted off-center by half that gap. Kadence's own CSS reads a `--global-vw`
// custom property as an override (`var(--global-vw, 100vw)`) but never sets
// it — so we do, using clientWidth (the true scrollbar-excluded width).
const setGlobalVw = () => {
  document.documentElement.style.setProperty('--global-vw', `${document.documentElement.clientWidth}px`);
};
setGlobalVw();
window.addEventListener('resize', setGlobalVw);

// --- Header: glassy once scrolled ---------------------------------------------
const header = document.querySelector('.jwt-header');

if (header) {
  const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });
}

// --- Mobile nav toggle (slide-in drawer) --------------------------------------
const navToggle = document.querySelector('.jwt-nav-toggle');
const navBackdrop = document.querySelector('[data-jwt-nav-backdrop]');
const navPanel = document.getElementById('jwt-mobile-nav');

if (navToggle) {
  const closeNav = () => {
    document.body.classList.remove('jwt-nav-open');
    navToggle.setAttribute('aria-expanded', 'false');
  };

  navToggle.addEventListener('click', () => {
    const open = document.body.classList.toggle('jwt-nav-open');
    navToggle.setAttribute('aria-expanded', String(open));
  });

  navBackdrop?.addEventListener('click', closeNav);

  // Navigating via a drawer link should close it too (same-page anchors etc.).
  navPanel?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', closeNav);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && document.body.classList.contains('jwt-nav-open')) {
      closeNav();
      navToggle.focus();
    }
  });

  // Safety net: if the viewport crosses back above the drawer's breakpoint
  // while it's open (resizing the window, rotating, toggling devtools'
  // responsive mode) force-close it — otherwise the backdrop + scroll lock
  // are orphaned with no visible drawer/toggle left to close them.
  const desktopQuery = window.matchMedia('(min-width: 881px)');
  desktopQuery.addEventListener('change', (e) => {
    if (e.matches) closeNav();
  });
}

// --- Scroll reveal ---------------------------------------------------------
const revealEls = document.querySelectorAll('[data-jwt-reveal]');

if (!reducedMotion && 'IntersectionObserver' in window && revealEls.length) {
  const io = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          io.unobserve(entry.target);
        }
      }
    },
    { threshold: 0.12, rootMargin: '0px 0px -40px' }
  );
  revealEls.forEach((el) => io.observe(el));
} else {
  revealEls.forEach((el) => el.classList.add('is-visible'));
}

// --- Stat count-up -----------------------------------------------------------
// Markup ships the final text (SEO/no-JS safe); JS only animates toward it.
const counters = document.querySelectorAll('[data-jwt-count]');

if (!reducedMotion && 'IntersectionObserver' in window && counters.length) {
  const fmt = new Intl.NumberFormat('id-ID');

  const animate = (el) => {
    const target = parseFloat(el.dataset.jwtCount);
    if (Number.isNaN(target)) return;

    const suffix = el.dataset.jwtSuffix || '';
    const start = performance.now();
    const duration = 1200;

    const tick = (now) => {
      const p = Math.min((now - start) / duration, 1);
      const eased = 1 - Math.pow(1 - p, 3);
      el.textContent = fmt.format(Math.round(target * eased)) + suffix;
      if (p < 1) requestAnimationFrame(tick);
    };

    requestAnimationFrame(tick);
  };

  const io = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (entry.isIntersecting) {
          animate(entry.target);
          io.unobserve(entry.target);
        }
      }
    },
    { threshold: 0.6 }
  );

  counters.forEach((el) => io.observe(el));
}

// --- Lightbox for testimonial images ------------------------------------------
// Click any [data-jwt-lightbox] (the proof-card zoom buttons) to enlarge its
// full-size image in a dark overlay. Overlay is built once, on first use.
(() => {
  const triggers = document.querySelectorAll('[data-jwt-lightbox]');
  if (!triggers.length) return;

  let overlay;
  let lastFocused;

  const build = () => {
    overlay = document.createElement('div');
    overlay.className = 'jwt-lightbox';
    overlay.innerHTML =
      '<button class="jwt-lightbox__close" aria-label="Tutup">&times;</button>' +
      '<img class="jwt-lightbox__img" alt="">';
    document.body.appendChild(overlay);

    const close = () => {
      overlay.classList.remove('is-open');
      document.body.classList.remove('jwt-lightbox-open');
      lastFocused && lastFocused.focus();
    };

    overlay.addEventListener('click', (e) => {
      // Close when clicking the backdrop or the close button (not the image).
      if (e.target === overlay || e.target.closest('.jwt-lightbox__close')) close();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && overlay.classList.contains('is-open')) close();
    });
  };

  const open = (src, alt) => {
    if (!overlay) build();
    const img = overlay.querySelector('.jwt-lightbox__img');
    img.src = src;
    img.alt = alt || '';
    overlay.classList.add('is-open');
    document.body.classList.add('jwt-lightbox-open');
    overlay.querySelector('.jwt-lightbox__close').focus();
  };

  triggers.forEach((t) => {
    t.addEventListener('click', () => {
      lastFocused = t;
      open(t.dataset.jwtLightbox, t.querySelector('img')?.alt);
    });
  });
})();

// --- TEMPORARY preview guard: block navigation to unpublished pages -----------
// Homepage-only preview: any link pointing at Bootcamp, Discord, or the
// Testimonials page should do NOTHING when clicked — the buttons/links and the
// pages themselves stay in place, they just don't navigate for now. Works no
// matter where the link lives (hero/offer/faq CTAs, header nav, footer menus).
// Remove this whole block to restore normal navigation once those pages go live.
(() => {
  const blockedPaths = ['/bootcamp', '/discord', '/testimonials'];

  const isBlocked = (href) => {
    let url;
    try {
      url = new URL(href, location.origin);
    } catch {
      return false;
    }
    // External Discord invites (discord.gg / discord.com) count too.
    if (/(^|\.)discord\.(gg|com)$/i.test(url.hostname)) return true;
    if (url.origin !== location.origin) return false;
    return blockedPaths.some(
      (p) => url.pathname === p || url.pathname.startsWith(`${p}/`)
    );
  };

  // Capture phase so we win before any other click handler navigates/opens.
  document.addEventListener(
    'click',
    (e) => {
      const link = e.target.closest('a[href]');
      if (link && isBlocked(link.getAttribute('href'))) {
        e.preventDefault();
        e.stopPropagation();
      }
    },
    true
  );

  // Mark them for assistive tech (visual appearance is intentionally unchanged
  // so the homepage preview still looks complete).
  document.querySelectorAll('a[href]').forEach((a) => {
    if (isBlocked(a.getAttribute('href'))) a.setAttribute('aria-disabled', 'true');
  });
})();
