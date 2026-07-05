import './main.scss';

// Progressive enhancement only — every block is fully usable without JS.
document.documentElement.classList.add('js');

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// --- Header: glassy once scrolled ---------------------------------------------
const header = document.querySelector('.jwt-header');

if (header) {
  const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });
}

// --- Mobile nav toggle -------------------------------------------------------
const navToggle = document.querySelector('.jwt-nav-toggle');

if (navToggle) {
  navToggle.addEventListener('click', () => {
    const open = document.body.classList.toggle('jwt-nav-open');
    navToggle.setAttribute('aria-expanded', String(open));
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && document.body.classList.contains('jwt-nav-open')) {
      document.body.classList.remove('jwt-nav-open');
      navToggle.setAttribute('aria-expanded', 'false');
      navToggle.focus();
    }
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
