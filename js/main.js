// SKINMETAL — shared site behavior

document.addEventListener('DOMContentLoaded', () => {
  /* Header scroll state */
  const header = document.querySelector('.site-header');
  const onScroll = () => {
    if (!header) return;
    header.classList.toggle('scrolled', window.scrollY > 30);
  };
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  /* Mobile menu */
  const toggle = document.querySelector('.menu-toggle');
  const navLinks = document.querySelector('.nav-links');
  if (toggle && navLinks) {
    toggle.addEventListener('click', () => {
      toggle.classList.toggle('open');
      navLinks.classList.toggle('open');
    });
    navLinks.querySelectorAll('a').forEach((a) =>
      a.addEventListener('click', () => {
        toggle.classList.remove('open');
        navLinks.classList.remove('open');
      })
    );
  }

  /* Active nav link */
  const page = document.body.dataset.page;
  if (page) {
    document.querySelectorAll(`.nav-links a[data-nav="${page}"]`).forEach((a) => a.classList.add('active'));
  }

  /* Sparks in hero */
  document.querySelectorAll('.hero-bg').forEach((bg) => {
    const count = 26;
    for (let i = 0; i < count; i++) {
      const s = document.createElement('span');
      s.className = 'spark';
      s.style.left = Math.random() * 100 + '%';
      s.style.bottom = -10 + 'px';
      s.style.animationDuration = 6 + Math.random() * 8 + 's';
      s.style.animationDelay = Math.random() * 10 + 's';
      bg.appendChild(s);
    }
  });

  /* Countdown to album launch */
  const RELEASE_DATE = new Date('2026-09-26T00:00:00-03:00').getTime();
  document.querySelectorAll('[data-countdown]').forEach((el) => {
    const dayEl = el.querySelector('[data-days]');
    const hourEl = el.querySelector('[data-hours]');
    const minEl = el.querySelector('[data-minutes]');
    const secEl = el.querySelector('[data-seconds]');
    const preLabel = document.querySelectorAll('[data-pre-release]');
    const postLabel = document.querySelectorAll('[data-post-release]');

    const tick = () => {
      const now = Date.now();
      const diff = RELEASE_DATE - now;

      if (diff <= 0) {
        el.style.display = 'none';
        preLabel.forEach((n) => (n.style.display = 'none'));
        postLabel.forEach((n) => (n.style.display = ''));
        return;
      }

      const days = Math.floor(diff / 86400000);
      const hours = Math.floor((diff % 86400000) / 3600000);
      const mins = Math.floor((diff % 3600000) / 60000);
      const secs = Math.floor((diff % 60000) / 1000);

      if (dayEl) dayEl.textContent = String(days).padStart(2, '0');
      if (hourEl) hourEl.textContent = String(hours).padStart(2, '0');
      if (minEl) minEl.textContent = String(mins).padStart(2, '0');
      if (secEl) secEl.textContent = String(secs).padStart(2, '0');
    };
    tick();
    setInterval(tick, 1000);
  });

  /* Scroll reveal */
  const revealEls = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && revealEls.length) {
    const io = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('in');
            io.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.15 }
    );
    revealEls.forEach((el) => io.observe(el));
  } else {
    revealEls.forEach((el) => el.classList.add('in'));
  }

  /* Video / character filter pills (data-filter-group) */
  document.querySelectorAll('[data-filter-group]').forEach((group) => {
    const pills = group.querySelectorAll('.pill');
    const targetSelector = group.dataset.filterGroup;
    const items = document.querySelectorAll(targetSelector);
    pills.forEach((pill) => {
      pill.addEventListener('click', () => {
        pills.forEach((p) => p.classList.remove('active'));
        pill.classList.add('active');
        const cat = pill.dataset.category;
        items.forEach((item) => {
          const show = cat === 'all' || item.dataset.category === cat;
          item.style.display = show ? '' : 'none';
        });
      });
    });
  });

  /* Forms — no backend yet, just confirm intent locally */
  document.querySelectorAll('form[data-local-form]').forEach((form) => {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const note = form.querySelector('.form-note');
      if (note) {
        note.textContent = 'Thank you. Your message has been received by the SKINMETAL team.';
        note.style.color = '#C9A227';
      }
      form.reset();
    });
  });
});
