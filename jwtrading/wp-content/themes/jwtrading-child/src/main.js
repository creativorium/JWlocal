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

// --- Desktop header pill: measure the nav so the pill can hug it at rest, then
// stretch out to the bar edges on scroll (the growth itself is CSS transition).
const headerBar = document.querySelector('.jwt-header__bar');
const headerPillNav = document.querySelector('.jwt-header__nav .jwt-nav');

if (headerBar && headerPillNav) {
  const setPillInsets = () => {
    if (window.innerWidth < 881) return; // desktop-only pill
    const b = headerBar.getBoundingClientRect();
    const n = headerPillNav.getBoundingClientRect();
    headerBar.style.setProperty('--jwt-pill-left', `${Math.max(0, n.left - b.left)}px`);
    headerBar.style.setProperty('--jwt-pill-right', `${Math.max(0, b.right - n.right)}px`);
    headerBar.style.setProperty('--jwt-pill-top', `${Math.max(0, n.top - b.top)}px`);
    headerBar.style.setProperty('--jwt-pill-bottom', `${Math.max(0, b.bottom - n.bottom)}px`);
  };
  setPillInsets();
  window.addEventListener('resize', setPillInsets);
  // Fonts change the nav's width → remeasure once they're ready.
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(setPillInsets);
}

// --- Mobile nav toggle (slide-in drawer) --------------------------------------
const navToggle = document.querySelector('.jwt-nav-toggle');
const navBackdrop = document.querySelector('[data-jwt-nav-backdrop]');
const navPanel = document.getElementById('jwt-mobile-nav');

if (navToggle) {
  // Scroll-lock: freeze the page by clipping <html> vertical overflow. This keeps
  // the current scroll position in place (no jump on open OR close) and, paired
  // with html { scrollbar-gutter: stable }, doesn't shift the layout. No
  // position:fixed (which was pushing the logo/CTA around) and no scrollTo (which
  // was animating on close because of scroll-behavior:smooth).
  const openNav = () => {
    document.documentElement.style.overflowY = 'hidden';
    document.body.classList.add('jwt-nav-open');
    navToggle.setAttribute('aria-expanded', 'true');
  };
  const closeNav = () => {
    if (!document.body.classList.contains('jwt-nav-open')) return;
    document.documentElement.style.overflowY = '';
    document.body.classList.remove('jwt-nav-open');
    navToggle.setAttribute('aria-expanded', 'false');
  };

  navToggle.addEventListener('click', () => {
    if (document.body.classList.contains('jwt-nav-open')) closeNav();
    else openNav();
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

// --- VSL facade: load + play the video only on click --------------------------
// The block ships just a poster image (fast); the heavy video file is fetched
// only when the visitor clicks play, then swapped in and auto-played.
(() => {
  document.querySelectorAll('[data-jwt-vsl]').forEach((fig) => {
    const btn = fig.querySelector('[data-jwt-vsl-play]');
    const src = fig.getAttribute('data-jwt-vsl-src');
    if (!btn || !src) return;

    btn.addEventListener('click', () => {
      const video = document.createElement('video');
      video.className = 'jwt-vsl__video';
      video.src = src;
      video.controls = true;
      video.autoplay = true;
      video.playsInline = true;
      video.setAttribute('playsinline', ''); // iOS Safari attribute form

      const frame = fig.querySelector('.jwt-vsl__frame');
      frame.innerHTML = '';
      frame.appendChild(video);
      fig.classList.add('is-playing');
      video.play().catch(() => {}); // ignore autoplay rejections; controls remain
    });
  });
})();

// --- Showcase: tab-cards drive a shared media stage ---------------------------
// Clicking a card (or the arrows/dots) swaps the left stage to that card's
// media, or a labelled placeholder if it has none yet. No-JS: cards read as a
// plain list, which is fine.
(() => {
  document.querySelectorAll('[data-jwt-showcase]').forEach((root) => {
    const stage = root.querySelector('[data-jwt-showcase-stage]');
    const cards = Array.from(root.querySelectorAll('[data-jwt-showcase-card]'));
    const dotsWrap = root.querySelector('[data-jwt-showcase-dots]');
    const prev = root.querySelector('[data-jwt-showcase-prev]');
    const next = root.querySelector('[data-jwt-showcase-next]');
    if (!stage || !cards.length) return;

    let active = -1;

    // Build one dot per card.
    const dots = cards.map((_, i) => {
      const dot = document.createElement('button');
      dot.type = 'button';
      dot.className = 'jwt-showcase__dot';
      dot.setAttribute('aria-label', `${i + 1}`);
      dot.addEventListener('click', () => set(i));
      dotsWrap && dotsWrap.appendChild(dot);
      return dot;
    });

    const fillStage = (card) => {
      stage.textContent = '';
      const media = card.getAttribute('data-media');
      if (media) {
        const img = document.createElement('img');
        img.className = 'jwt-showcase__media';
        img.src = media;
        img.alt = '';
        img.loading = 'lazy';
        stage.appendChild(img);
        return;
      }
      // Placeholder (built with textContent — no HTML injection from data-*).
      const ph = document.createElement('div');
      ph.className = 'jwt-showcase__placeholder';
      const label = document.createElement('span');
      label.className = 'jwt-showcase__ph-label';
      label.textContent = 'Screenshot';
      ph.appendChild(label);
      const title = card.getAttribute('data-placeholder');
      if (title) {
        const t = document.createElement('span');
        t.className = 'jwt-showcase__ph-title';
        t.textContent = `[ ${title} ]`;
        ph.appendChild(t);
      }
      stage.appendChild(ph);
    };

    const set = (i) => {
      const n = cards.length;
      active = ((i % n) + n) % n;
      cards.forEach((c, idx) => c.classList.toggle('is-active', idx === active));
      dots.forEach((d, idx) => d.classList.toggle('is-active', idx === active));
      fillStage(cards[active]);
    };

    cards.forEach((card, i) => {
      card.addEventListener('click', () => set(i));
      card.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          set(i);
        }
      });
    });
    prev && prev.addEventListener('click', () => set(active - 1));
    next && next.addEventListener('click', () => set(active + 1));

    set(0);
  });
})();

// --- Community card image carousel --------------------------------------------
// Slides the track, builds dots, wires arrows, and auto-advances (pauses on
// hover / when the tab is hidden). No JS: the first image just shows statically.
(() => {
  document.querySelectorAll('[data-jwt-community]').forEach((track) => {
    const slides = Array.from(track.children);
    if (slides.length <= 1) return;
    const media = track.closest('.jwt-community__media');
    const dotsWrap = media && media.querySelector('[data-jwt-community-dots]');
    const prev = media && media.querySelector('[data-jwt-community-prev]');
    const next = media && media.querySelector('[data-jwt-community-next]');

    let active = 0;
    const dots = slides.map((_, i) => {
      const d = document.createElement('button');
      d.type = 'button';
      d.className = 'jwt-community__dot';
      d.setAttribute('aria-label', `${i + 1}`);
      d.addEventListener('click', () => go(i, true));
      dotsWrap && dotsWrap.appendChild(d);
      return d;
    });

    const render = () => {
      track.style.transform = `translateX(-${active * 100}%)`;
      dots.forEach((d, i) => d.classList.toggle('is-active', i === active));
    };
    const go = (i, stop) => {
      active = (i + slides.length) % slides.length;
      render();
      if (stop) rearm();
    };

    let timer = null;
    const rearm = () => {
      clearInterval(timer);
      if (!reducedMotion) timer = setInterval(() => go(active + 1), 4500);
    };

    prev && prev.addEventListener('click', () => go(active - 1, true));
    next && next.addEventListener('click', () => go(active + 1, true));
    media.addEventListener('mouseenter', () => clearInterval(timer));
    media.addEventListener('mouseleave', rearm);

    render();
    rearm();
  });
})();

// --- Contact form: compose a WhatsApp message on submit -----------------------
// No backend — the form gathers name/email/message and opens wa.me pre-filled.
(() => {
  document.querySelectorAll('[data-jwt-contact]').forEach((form) => {
    const wa = form.getAttribute('data-wa');
    if (!wa) return;
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const val = (n) => (form.querySelector(`[name="${n}"]`)?.value || '').trim();
      const nama = val('nama');
      const email = val('email');
      const pesan = val('pesan');
      const lines = [
        `Halo JW Trading Academy, saya ${nama || '(tanpa nama)'}.`,
        pesan,
        email ? `Email: ${email}` : '',
      ].filter(Boolean);
      window.open(`https://wa.me/${wa}?text=${encodeURIComponent(lines.join('\n\n'))}`, '_blank', 'noopener');
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
  // Bootcamp is live now, so it's navigable; Discord + Testimonials stay
  // blocked until those pages are ready.
  // Bootcamp, Discord and Testimonials are all built now.
  const blockedPaths = [];

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
