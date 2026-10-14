// SKINMETAL — shared site behavior

function wireFilterGroups() {
  document.querySelectorAll('[data-filter-group]').forEach((group) => {
    if (group.dataset.wired) return;
    group.dataset.wired = '1';
    const pills = group.querySelectorAll('.pill');
    const targetSelector = group.dataset.filterGroup;
    pills.forEach((pill) => {
      pill.addEventListener('click', () => {
        pills.forEach((p) => p.classList.remove('active'));
        pill.classList.add('active');
        const cat = pill.dataset.category;
        document.querySelectorAll(targetSelector).forEach((item) => {
          const show = cat === 'all' || item.dataset.category === cat;
          item.style.display = show ? '' : 'none';
        });
      });
    });
  });
}

function revealNow(container) {
  (container || document).querySelectorAll('.reveal').forEach((el) => el.classList.add('in'));
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str == null ? '' : String(str);
  return div.innerHTML;
}

const STREAM_LABELS = { spotify: 'Spotify', apple_music: 'Apple Music', youtube_music: 'YouTube Music', deezer: 'Deezer' };
const SOCIAL_LABELS = { instagram: 'IG', youtube: 'YT', spotify: 'SP', tiktok: 'TT' };

function applyLinks(links) {
  if (!links) return;

  // Streaming pill rows: <div data-stream-row> containing template pills driven by data
  document.querySelectorAll('[data-stream-row]').forEach((row) => {
    row.innerHTML = '';
    Object.keys(STREAM_LABELS).forEach((key) => {
      const url = links[key];
      if (!url) return;
      const a = document.createElement('a');
      a.href = url;
      a.target = '_blank';
      a.rel = 'noopener';
      a.className = 'stream-pill';
      a.textContent = STREAM_LABELS[key];
      row.appendChild(a);
    });
  });

  // Footer / nav social icons: <a data-social="instagram">
  document.querySelectorAll('[data-social]').forEach((a) => {
    const key = a.dataset.social;
    const url = links[key];
    if (url) {
      a.href = url;
      a.style.display = '';
    } else {
      a.style.display = 'none';
    }
  });
}

function cardLinksHtml(listenUrl, watchUrl) {
  const parts = [];
  if (listenUrl) parts.push(`<a href="${escapeHtml(listenUrl)}" target="_blank" rel="noopener">Listen</a>`);
  if (watchUrl) parts.push(`<a href="${escapeHtml(watchUrl)}">Watch</a>`);
  return parts.join('');
}

function renderSongs(songs, container) {
  if (!container) return;
  if (!songs.length) {
    container.innerHTML = '<p class="empty-note" style="color:var(--dark-silver);">No releases yet — check back soon.</p>';
    return;
  }
  container.innerHTML = songs.map((s) => `
    <div class="card">
      <div class="card-media"><span class="play-icon"></span></div>
      <div class="card-body">
        <h3>${escapeHtml(s.title)}</h3>
        <p class="meta">${escapeHtml(s.meta || s.type || '')}</p>
        <div class="card-links">${cardLinksHtml(s.listen_url, s.watch_url)}</div>
      </div>
    </div>
  `).join('');
  revealNow(container);
}

function renderProducts(products, container) {
  if (!container) return;
  if (!products.length) {
    container.innerHTML = '<p class="empty-note" style="color:var(--dark-silver);">No products listed yet — check back soon.</p>';
    return;
  }
  container.innerHTML = products.map((p) => `
    <div class="card product-card" data-product data-category="${escapeHtml(p.category)}">
      <div class="card-media"></div>
      <div class="card-body"><h3>${escapeHtml(p.name)}</h3><p class="meta price">${escapeHtml(p.price)}</p></div>
    </div>
  `).join('');
  revealNow(container);
  wireFilterGroups();
}

function renderShows(shows, container, emptyEl) {
  if (!container) return;
  if (!shows.length) {
    container.innerHTML = '';
    if (emptyEl) emptyEl.style.display = '';
    return;
  }
  if (emptyEl) emptyEl.style.display = 'none';
  container.innerHTML = shows.map((s) => `
    <div class="show-row">
      <span class="date">${escapeHtml(s.date)}</span>
      <div class="venue"><strong>${escapeHtml(s.venue)}</strong><span>${escapeHtml(s.city || '')}</span></div>
      ${s.ticket_url
        ? `<a href="${escapeHtml(s.ticket_url)}" target="_blank" rel="noopener" class="btn btn-outline">Get Tickets</a>`
        : `<a href="contact.html" class="btn btn-outline">More Info Soon</a>`}
    </div>
  `).join('');
  revealNow(container);
}

async function fetchJson(url) {
  try {
    const res = await fetch(url, { cache: 'no-store' });
    if (!res.ok) return null;
    return await res.json();
  } catch (e) {
    return null;
  }
}

function initDynamicContent() {
  const linksPromise = fetchJson('api/links.php').then((links) => links && applyLinks(links));

  const songsContainer = document.querySelector('[data-songs-grid]');
  if (songsContainer) {
    fetchJson('api/songs.php').then((songs) => renderSongs(songs || [], songsContainer));
  }

  const productsContainer = document.querySelector('[data-products-grid]');
  if (productsContainer) {
    fetchJson('api/products.php').then((products) => renderProducts(products || [], productsContainer));
  }

  const showsContainer = document.querySelector('[data-shows-list]');
  if (showsContainer) {
    const emptyEl = document.querySelector('[data-shows-empty]');
    fetchJson('api/shows.php').then((shows) => renderShows(shows || [], showsContainer, emptyEl));
  }

  return linksPromise;
}

function initContactForm() {
  const form = document.getElementById('contact-form');
  if (!form) return;
  const note = form.querySelector('.form-note');
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submitBtn = form.querySelector('button[type="submit"]');
    const data = Object.fromEntries(new FormData(form).entries());
    if (submitBtn) submitBtn.disabled = true;
    if (note) { note.textContent = 'Sending…'; note.style.color = 'var(--silver)'; }
    try {
      const res = await fetch('api/contact.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      });
      const result = await res.json();
      if (note) {
        note.textContent = result.message || (result.ok ? 'Thank you! Your message has been sent.' : 'Something went wrong.');
        note.style.color = result.ok ? '#C9A227' : '#e07a7a';
      }
      if (result.ok) form.reset();
    } catch (err) {
      if (note) { note.textContent = 'Could not send your message. Please try again later.'; note.style.color = '#e07a7a'; }
    } finally {
      if (submitBtn) submitBtn.disabled = false;
    }
  });
}

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
  const countdownEls = document.querySelectorAll('[data-countdown]');
  const preLabel = document.querySelectorAll('[data-pre-release]');
  const postLabel = document.querySelectorAll('[data-post-release]');

  const tick = () => {
    const now = Date.now();
    const diff = RELEASE_DATE - now;

    if (diff <= 0) {
      countdownEls.forEach((el) => (el.style.display = 'none'));
      preLabel.forEach((n) => (n.style.display = 'none'));
      postLabel.forEach((n) => (n.style.display = ''));
      return;
    }

    const days = Math.floor(diff / 86400000);
    const hours = Math.floor((diff % 86400000) / 3600000);
    const mins = Math.floor((diff % 3600000) / 60000);
    const secs = Math.floor((diff % 60000) / 1000);

    countdownEls.forEach((el) => {
      const dayEl = el.querySelector('[data-days]');
      const hourEl = el.querySelector('[data-hours]');
      const minEl = el.querySelector('[data-minutes]');
      const secEl = el.querySelector('[data-seconds]');
      if (dayEl) dayEl.textContent = String(days).padStart(2, '0');
      if (hourEl) hourEl.textContent = String(hours).padStart(2, '0');
      if (minEl) minEl.textContent = String(mins).padStart(2, '0');
      if (secEl) secEl.textContent = String(secs).padStart(2, '0');
    });
  };
  tick();
  setInterval(tick, 1000);

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

  /* Video / character / product filter pills (data-filter-group) */
  wireFilterGroups();

  /* Dynamic content from the admin-managed JSON data (songs, products, shows, links) */
  initDynamicContent();

  /* Real contact form submission */
  initContactForm();

  /* Newsletter / other local-only forms — no backend, just confirm intent */
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
