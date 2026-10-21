// SKINMETAL: shared site behavior

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

function objectPositionStyle(position) {
  const pos = position === 'top' ? 'center top' : position === 'bottom' ? 'center bottom' : 'center center';
  return ` style="object-position:${pos};"`;
}

function imgTag(src, alt, position) {
  if (!src) return '';
  return `<img src="${escapeHtml(src)}" alt="${escapeHtml(alt || '')}" loading="lazy"${objectPositionStyle(position)}>`;
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

function youtubeThumbnail(url) {
  if (!url) return null;
  try {
    const u = new URL(url, window.location.href);
    const host = u.hostname.replace(/^www\./, '');
    let id = null;
    if (host === 'youtu.be') {
      id = u.pathname.slice(1).split('/')[0];
    } else if (host.endsWith('youtube.com')) {
      if (u.pathname.startsWith('/shorts/')) {
        id = u.pathname.split('/')[2];
      } else if (u.pathname.startsWith('/embed/')) {
        id = u.pathname.split('/')[2];
      } else {
        id = u.searchParams.get('v');
      }
    }
    if (!id) return null;
    id = id.split('?')[0].split('&')[0];
    return `https://img.youtube.com/vi/${id}/hqdefault.jpg`;
  } catch (e) {
    return null;
  }
}

function mediaCardHtml(innerHtml, link) {
  if (!link) return `<div class="card-media">${innerHtml}</div>`;
  const isExternal = /^https?:\/\//i.test(link);
  return `<a class="card-media" href="${escapeHtml(link)}"${isExternal ? ' target="_blank" rel="noopener"' : ''}>${innerHtml}</a>`;
}

function renderSongs(songs, container) {
  if (!container) return;
  if (!songs.length) {
    container.innerHTML = '<p class="empty-note" style="color:var(--dark-silver);">No releases yet. Check back soon.</p>';
    return;
  }
  container.innerHTML = songs.map((s) => {
    const thumb = s.image || youtubeThumbnail(s.listen_url) || youtubeThumbnail(s.watch_url);
    const mediaLink = s.listen_url || s.watch_url || '';
    const media = imgTag(thumb, s.title, s.image ? s.image_position : null) + '<span class="play-icon"></span>';
    return `
    <div class="card">
      ${mediaCardHtml(media, mediaLink)}
      <div class="card-body">
        <h3>${escapeHtml(s.title)}</h3>
        <p class="meta">${escapeHtml(s.meta || s.type || '')}</p>
        <div class="card-links">${cardLinksHtml(s.listen_url, s.watch_url)}</div>
      </div>
    </div>
  `;
  }).join('');
  revealNow(container);
}

function renderProducts(products, container) {
  if (!container) return;
  if (!products.length) {
    container.innerHTML = '<p class="empty-note" style="color:var(--dark-silver);">No products listed yet. Check back soon.</p>';
    return;
  }
  container.innerHTML = products.map((p) => `
    <div class="card product-card" data-product data-category="${escapeHtml(p.category)}">
      <div class="card-media">${imgTag(p.image, p.name, p.image_position)}</div>
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

let RELEASE_DATE = new Date('2026-09-26T00:00:00-03:00').getTime();

function formatReleaseDate(dateStr) {
  if (!dateStr) return '';
  const d = new Date(dateStr + 'T00:00:00');
  if (isNaN(d.getTime())) return dateStr;
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function applyRelease(release) {
  if (!release) return;
  if (release.release_date) {
    const parsed = new Date(release.release_date + 'T00:00:00-03:00').getTime();
    if (!isNaN(parsed)) RELEASE_DATE = parsed;
  }
  document.querySelectorAll('[data-release-name]').forEach((el) => {
    el.textContent = release.name || el.textContent;
  });
  document.querySelectorAll('[data-release-text]').forEach((el) => {
    const field = el.dataset.releaseText;
    if (release[field]) el.textContent = release[field];
  });
  document.querySelectorAll('[data-release-pre-label]').forEach((el) => {
    el.textContent = `New Album: ${formatReleaseDate(release.release_date)}`;
  });
  document.querySelectorAll('[data-release-cover]').forEach((el) => {
    if (release.cover_image) el.src = release.cover_image;
  });
  document.querySelectorAll('[data-release-link]').forEach((el) => {
    const kind = el.dataset.releaseLink;
    const url = kind === 'listen' ? release.listen_url : kind === 'trailer' ? release.trailer_url : null;
    if (url) el.href = url;
  });
}

const CATEGORY_TAGS = {
  'music-video': 'Music Video', 'lyric': 'Lyric Video', 'live': 'Live',
  'short-film': 'Short Film', 'metal-tales': 'Metal Tales', 'bts': 'Behind the Scenes',
};

const FEATURED_VIDEO_CARD = `
  <div class="card" data-video data-category="music-video">
    <div class="card-media" style="aspect-ratio:4/5; display:block;">
      <video controls preload="metadata" style="width:100%; height:100%; object-fit:cover; display:block;">
        <source src="assets/skinmetal-video.mp4" type="video/mp4">
      </video>
    </div>
    <div class="card-body"><h3>SKINMETAL: Official Video</h3><p class="meta">Featured</p></div>
  </div>
`;

function renderVideos(videos, container) {
  if (!container) return;
  container.innerHTML = FEATURED_VIDEO_CARD + videos.map((v) => {
    const thumb = v.image || youtubeThumbnail(v.watch_url);
    const tag = CATEGORY_TAGS[v.category] || v.category;
    const media = imgTag(thumb, v.title, v.image ? v.image_position : null) + `<span class="play-icon"></span><div class="card-tag">${escapeHtml(tag)}</div>`;
    return `
    <div class="card" data-video data-category="${escapeHtml(v.category)}">
      ${mediaCardHtml(media, v.watch_url || '')}
      <div class="card-body">
        <h3>${escapeHtml(v.title)}</h3><p class="meta">${escapeHtml(v.meta || '')}</p>
        ${v.watch_url ? `<div class="card-links"><a href="${escapeHtml(v.watch_url)}" target="_blank" rel="noopener">Watch</a></div>` : ''}
      </div>
    </div>
  `;
  }).join('');
  revealNow(container);
}

function renderGallery(items, container) {
  if (!container) return;
  const section = container.dataset.gallerySection || 'live';
  const filtered = items.filter((it) => (it.section || 'live') === section);
  const emptyEl = container.nextElementSibling && container.nextElementSibling.hasAttribute('data-gallery-empty')
    ? container.nextElementSibling : null;
  if (!filtered.length) {
    container.innerHTML = '';
    if (emptyEl) emptyEl.style.display = '';
    return;
  }
  if (emptyEl) emptyEl.style.display = 'none';
  container.innerHTML = filtered.map((it) => `
    <div class="card-media" style="aspect-ratio:1;">${imgTag(it.image, it.caption, it.image_position)}</div>
  `).join('');
  revealNow(container);
}

function renderMembers(members, container) {
  if (!container) return;
  if (!members.length) {
    container.innerHTML = '<p class="empty-note" style="color:var(--dark-silver);">The lineup will be revealed soon.</p>';
    return;
  }
  container.innerHTML = members.map((m) => {
    const bioHtml = (m.bio || '').split('\n\n').filter(Boolean).map((p) => `<p style="padding:0 18px; color:var(--silver); font-size:0.9rem;">${escapeHtml(p)}</p>`).join('');
    const cardsHtml = (m.cards || []).length
      ? `<div class="member-facts">${m.cards.map((c) => `<div class="fact"><span class="label">${escapeHtml(c.label)}</span><span class="text">${escapeHtml(c.text)}</span></div>`).join('')}</div>`
      : '';
    return `
    <div class="character-card">
      <div class="portrait">${imgTag(m.photo, m.name, m.image_position)}</div>
      <h3>${escapeHtml(m.name)}</h3>
      <div class="role">${escapeHtml(m.role || '')}</div>
      ${bioHtml}
      ${cardsHtml}
      <div style="padding-bottom:18px;"></div>
    </div>
  `;
  }).join('');
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
    const limit = parseInt(songsContainer.dataset.limit || '', 10);
    fetchJson('api/songs.php').then((songs) => {
      const list = songs || [];
      renderSongs(limit ? list.slice(0, limit) : list, songsContainer);
    });
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

  const videosContainer = document.querySelector('[data-videos-grid]');
  if (videosContainer) {
    fetchJson('api/videos.php').then((videos) => renderVideos(videos || [], videosContainer));
  }

  const galleryContainers = document.querySelectorAll('[data-gallery-grid]');
  if (galleryContainers.length) {
    fetchJson('api/gallery.php').then((items) => {
      galleryContainers.forEach((c) => renderGallery(items || [], c));
    });
  }

  const membersContainer = document.querySelector('[data-members-grid]');
  if (membersContainer) {
    fetchJson('api/members.php').then((members) => renderMembers(members || [], membersContainer));
  }

  fetchJson('api/release.php').then((release) => applyRelease(release));

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

  /* Newsletter / other local-only forms: no backend, just confirm intent */
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
