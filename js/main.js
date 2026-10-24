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

/* ---- Shopping cart (client-side, localStorage) ---- */
const CART_KEY = 'skinmetal_cart';

function getCart() {
  try {
    const raw = localStorage.getItem(CART_KEY);
    const cart = raw ? JSON.parse(raw) : [];
    return Array.isArray(cart) ? cart : [];
  } catch (e) {
    return [];
  }
}

function saveCart(cart) {
  try {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
  } catch (e) {
    /* storage unavailable (private mode, quota) — cart just won't persist */
  }
  updateCartBadge();
}

function addToCart(product, qty) {
  qty = qty || 1;
  const cart = getCart();
  const existing = cart.find((item) => item.id === product.id);
  if (existing) {
    existing.qty += qty;
  } else {
    cart.push({
      id: product.id,
      name: product.name,
      price_amount: Number(product.price_amount) || 0,
      currency: product.currency || 'USD',
      weight_kg: Number(product.weight_kg) || 0,
      image: product.image || '',
      qty: qty,
    });
  }
  saveCart(cart);
}

function setCartQty(id, qty) {
  const cart = getCart();
  const item = cart.find((i) => i.id === id);
  if (!item) return;
  if (qty <= 0) {
    saveCart(cart.filter((i) => i.id !== id));
  } else {
    item.qty = qty;
    saveCart(cart);
  }
}

function removeFromCart(id) {
  saveCart(getCart().filter((i) => i.id !== id));
}

function cartCount() {
  return getCart().reduce((sum, i) => sum + i.qty, 0);
}

function cartWeightKg() {
  return getCart().reduce((sum, i) => sum + i.weight_kg * i.qty, 0);
}

function updateCartBadge() {
  const count = cartCount();
  document.querySelectorAll('[data-cart-count]').forEach((el) => {
    el.textContent = String(count);
    el.classList.toggle('is-empty', count === 0);
  });
}

function wireAddToCartButtons(container) {
  (container || document).querySelectorAll('[data-add-to-cart]').forEach((btn) => {
    if (btn.dataset.wired) return;
    btn.dataset.wired = '1';
    btn.addEventListener('click', () => {
      const product = (window.PRODUCTS_CACHE || []).find((p) => p.id === btn.dataset.addToCart);
      if (!product) return;
      addToCart(product, 1);
      const original = btn.textContent;
      btn.textContent = 'Added!';
      btn.disabled = true;
      setTimeout(() => {
        btn.textContent = original;
        btn.disabled = false;
      }, 1100);
    });
  });
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

  // Contact page email / WhatsApp
  if (links.contact_email) {
    document.querySelectorAll('[data-contact-email]').forEach((a) => {
      a.href = 'mailto:' + links.contact_email;
      a.textContent = links.contact_email;
    });
  }
  if (links.contact_whatsapp_number) {
    document.querySelectorAll('[data-contact-whatsapp]').forEach((a) => {
      a.href = 'https://wa.me/' + links.contact_whatsapp_number;
      if (links.contact_whatsapp_display) a.textContent = links.contact_whatsapp_display;
    });
  }
}

function applyAbout(about) {
  if (!about) return;
  document.querySelectorAll('[data-about-photo]').forEach((el) => {
    if (about.photo) el.src = about.photo;
    const pos = about.image_position === 'top' ? 'center top' : about.image_position === 'bottom' ? 'center bottom' : 'center center';
    el.style.objectPosition = pos;
  });
}

function applyMedia(media) {
  if (!media) return;
  document.querySelectorAll('[data-media-link]').forEach((a) => {
    const kind = a.dataset.mediaLink;
    const url = media[kind + '_url'];
    if (url) {
      a.href = url;
      a.classList.remove('is-disabled');
    } else {
      a.removeAttribute('href');
      a.classList.add('is-disabled');
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
  window.PRODUCTS_CACHE = products;
  container.innerHTML = products.map((p) => `
    <div class="card product-card" data-product data-category="${escapeHtml(p.category)}">
      <div class="card-media">${imgTag(p.image, p.name, p.image_position)}</div>
      <div class="card-body">
        <h3>${escapeHtml(p.name)}</h3>
        <p class="meta price">${escapeHtml(p.price)}</p>
        <button type="button" class="btn btn-outline add-to-cart-btn" data-add-to-cart="${escapeHtml(p.id)}">Add to Cart</button>
      </div>
    </div>
  `).join('');
  revealNow(container);
  wireFilterGroups();
  wireAddToCartButtons(container);
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
    const bioHtml = (m.bio || '').split('\n\n').filter(Boolean).map((p) => `<p>${escapeHtml(p)}</p>`).join('');
    const cardsHtml = (m.cards || []).length
      ? `<div class="member-facts">${m.cards.map((c) => `<div class="fact"><span class="label">${escapeHtml(c.label)}</span><span class="text">${escapeHtml(c.text)}</span></div>`).join('')}</div>`
      : '';
    return `
    <div class="member-row">
      <div class="member-portrait">${imgTag(m.photo, m.name, m.image_position)}</div>
      <div class="member-copy">
        <h3>${escapeHtml(m.name)}</h3>
        <div class="role">${escapeHtml(m.role || '')}</div>
        ${bioHtml}
        ${cardsHtml}
      </div>
    </div>
  `;
  }).join('');
  revealNow(container);
}

function renderCharacters(characters, container) {
  if (!container) return;
  if (!characters.length) {
    container.innerHTML = '<p class="empty-note" style="color:var(--dark-silver);">The characters will be revealed soon.</p>';
    return;
  }
  container.innerHTML = characters.map((c) => {
    const thumb = c.photo || youtubeThumbnail(c.link_url);
    const linkHtml = c.link_url
      ? `<div class="card-links" style="padding:0 18px 18px;"><a href="${escapeHtml(c.link_url)}" target="_blank" rel="noopener">${escapeHtml(c.link_label || 'Watch')}</a></div>`
      : '';
    return `
    <div class="character-card">
      <div class="portrait">${imgTag(thumb, c.name, c.image_position)}</div>
      <h3>${escapeHtml(c.name)}</h3>
      <div class="role">${escapeHtml(c.role || '')}</div>
      ${linkHtml}
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

  const charactersContainer = document.querySelector('[data-characters-grid]');
  if (charactersContainer) {
    fetchJson('api/characters.php').then((characters) => renderCharacters(characters || [], charactersContainer));
  }

  if (document.querySelector('[data-about-photo]')) {
    fetchJson('api/about.php').then((about) => applyAbout(about));
  }

  if (document.querySelector('[data-media-link]')) {
    fetchJson('api/media.php').then((media) => applyMedia(media));
  }

  fetchJson('api/release.php').then((release) => applyRelease(release));

  return linksPromise;
}

/* ---- Checkout ---- */
const COUNTRIES = [
  ['BR', 'Brazil'],
  ['AR', 'Argentina'], ['UY', 'Uruguay'], ['PY', 'Paraguay'], ['CL', 'Chile'], ['BO', 'Bolivia'],
  ['PE', 'Peru'], ['CO', 'Colombia'], ['VE', 'Venezuela'], ['EC', 'Ecuador'], ['GY', 'Guyana'], ['SR', 'Suriname'],
  ['US', 'United States'], ['CA', 'Canada'], ['MX', 'Mexico'], ['GT', 'Guatemala'], ['BZ', 'Belize'],
  ['SV', 'El Salvador'], ['HN', 'Honduras'], ['NI', 'Nicaragua'], ['CR', 'Costa Rica'], ['PA', 'Panama'],
  ['CU', 'Cuba'], ['DO', 'Dominican Republic'], ['HT', 'Haiti'], ['JM', 'Jamaica'], ['TT', 'Trinidad and Tobago'],
  ['PT', 'Portugal'], ['ES', 'Spain'], ['FR', 'France'], ['DE', 'Germany'], ['IT', 'Italy'], ['GB', 'United Kingdom'],
  ['NL', 'Netherlands'], ['BE', 'Belgium'], ['IE', 'Ireland'], ['CH', 'Switzerland'], ['AT', 'Austria'],
  ['SE', 'Sweden'], ['NO', 'Norway'], ['DK', 'Denmark'], ['FI', 'Finland'], ['PL', 'Poland'], ['GR', 'Greece'],
  ['CZ', 'Czechia'], ['HU', 'Hungary'], ['RO', 'Romania'],
  ['JP', 'Japan'], ['CN', 'China'], ['KR', 'South Korea'], ['IN', 'India'], ['AU', 'Australia'], ['NZ', 'New Zealand'],
  ['SG', 'Singapore'], ['TH', 'Thailand'], ['ID', 'Indonesia'], ['PH', 'Philippines'], ['VN', 'Vietnam'],
  ['MY', 'Malaysia'], ['AE', 'United Arab Emirates'], ['SA', 'Saudi Arabia'], ['IL', 'Israel'], ['TR', 'Turkey'],
  ['XX', 'Other / Rest of World'],
];

/* Checkout currency follows the shipping destination: BRL for Brazil (paid via
   Mercado Pago), USD everywhere else (paid via PayPal). Product price_amount
   is treated as a plain number valid in either context — there is no live
   currency conversion in this store. */
function checkoutCurrencyForCountry(code) {
  return code === 'BR' ? 'BRL' : 'USD';
}

function formatMoney(amount, currency) {
  const symbol = currency === 'BRL' ? 'R$' : '$';
  return symbol + Number(amount || 0).toFixed(2);
}

function renderCartItems(container, currency) {
  if (!container) return;
  const cart = getCart();
  container.innerHTML = cart.map((item) => `
    <div class="cart-row" data-cart-row="${escapeHtml(item.id)}">
      <div class="cart-row-media">${imgTag(item.image, item.name, 'center')}</div>
      <div class="cart-row-info">
        <h4>${escapeHtml(item.name)}</h4>
        <div class="cart-row-price">${formatMoney(item.price_amount, currency)} each</div>
      </div>
      <div class="cart-row-qty">
        <button type="button" class="qty-btn" data-qty-step="-1" data-id="${escapeHtml(item.id)}" aria-label="Decrease quantity">&minus;</button>
        <span>${item.qty}</span>
        <button type="button" class="qty-btn" data-qty-step="1" data-id="${escapeHtml(item.id)}" aria-label="Increase quantity">+</button>
      </div>
      <div class="cart-row-total">${formatMoney(item.price_amount * item.qty, currency)}</div>
      <button type="button" class="cart-row-remove" data-cart-remove="${escapeHtml(item.id)}" aria-label="Remove item">&times;</button>
    </div>
  `).join('');

  container.querySelectorAll('[data-qty-step]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const item = getCart().find((i) => i.id === btn.dataset.id);
      if (!item) return;
      setCartQty(item.id, item.qty + parseInt(btn.dataset.qtyStep, 10));
      refreshCheckoutPage();
    });
  });
  container.querySelectorAll('[data-cart-remove]').forEach((btn) => {
    btn.addEventListener('click', () => {
      removeFromCart(btn.dataset.cartRemove);
      refreshCheckoutPage();
    });
  });
}

let checkoutShippingQuote = null;

async function updateShippingQuote() {
  const countryEl = document.getElementById('co-country');
  const shippingEl = document.querySelector('[data-shipping-cost]');
  const country = countryEl ? countryEl.value : '';
  checkoutShippingQuote = null;
  if (!country) {
    if (shippingEl) shippingEl.textContent = 'Enter your country';
    updateCheckoutTotals();
    return;
  }
  const weight = cartWeightKg();
  if (weight <= 0) {
    updateCheckoutTotals();
    return;
  }
  if (shippingEl) shippingEl.textContent = 'Calculating…';
  const quote = await fetchJson(`api/shipping-quote.php?country=${encodeURIComponent(country)}&weight_kg=${encodeURIComponent(weight)}`);
  checkoutShippingQuote = quote;
  if (shippingEl) {
    shippingEl.textContent = quote ? `${formatMoney(quote.price, quote.currency)} (${quote.zone_name})` : 'Unable to calculate shipping';
  }
  updateCheckoutTotals();
}

function updateCheckoutTotals() {
  const country = document.getElementById('co-country')?.value || '';
  const currency = checkoutCurrencyForCountry(country);
  const cart = getCart();
  const subtotal = cart.reduce((sum, i) => sum + i.price_amount * i.qty, 0);
  const shipping = checkoutShippingQuote ? checkoutShippingQuote.price : 0;
  const subtotalEl = document.querySelector('[data-subtotal]');
  const totalEl = document.querySelector('[data-order-total]');
  if (subtotalEl) subtotalEl.textContent = formatMoney(subtotal, currency);
  if (totalEl) {
    totalEl.textContent = checkoutShippingQuote
      ? formatMoney(subtotal + shipping, currency)
      : formatMoney(subtotal, currency) + ' + shipping';
  }
}

function refreshCheckoutPage() {
  const root = document.querySelector('[data-checkout]');
  if (!root) return;
  const cart = getCart();
  const emptyEl = root.querySelector('[data-cart-empty]');
  const contentEl = root.querySelector('[data-checkout-content]');
  if (!cart.length) {
    if (emptyEl) emptyEl.style.display = '';
    if (contentEl) contentEl.style.display = 'none';
    return;
  }
  if (emptyEl) emptyEl.style.display = 'none';
  if (contentEl) contentEl.style.display = '';
  const currency = checkoutCurrencyForCountry(document.getElementById('co-country')?.value || '');
  renderCartItems(root.querySelector('[data-cart-items]'), currency);
  updateShippingQuote();
}

async function submitOrder(method) {
  const form = document.getElementById('checkout-form');
  const msgEl = document.querySelector('[data-checkout-message]');
  if (!form || !form.reportValidity()) return;
  const cart = getCart();
  if (!cart.length) return;
  const customer = Object.fromEntries(new FormData(form).entries());
  document.querySelectorAll('[data-pay]').forEach((b) => (b.disabled = true));
  if (msgEl) { msgEl.textContent = 'Placing your order…'; msgEl.style.color = 'var(--silver)'; }
  try {
    const res = await fetch('api/orders.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ customer, items: cart, payment_method: method }),
    });
    const result = await res.json();
    if (result.ok && result.redirect_url) {
      window.location.href = result.redirect_url;
      return;
    }
    if (msgEl) {
      msgEl.textContent = result.message || (result.ok ? 'Order placed!' : 'Something went wrong. Please try again.');
      msgEl.style.color = result.ok ? '#C9A227' : '#e07a7a';
    }
    if (result.ok) {
      saveCart([]);
      refreshCheckoutPage();
    }
  } catch (e) {
    if (msgEl) { msgEl.textContent = 'Could not place your order. Please try again later.'; msgEl.style.color = '#e07a7a'; }
  } finally {
    document.querySelectorAll('[data-pay]').forEach((b) => (b.disabled = false));
  }
}

function initPaymentButtons() {
  fetchJson('api/payment-status.php').then((status) => {
    const mpBtn = document.querySelector('[data-pay="mercadopago"]');
    const ppBtn = document.querySelector('[data-pay="paypal"]');
    const mpNote = document.querySelector('[data-pay-note="mercadopago"]');
    const ppNote = document.querySelector('[data-pay-note="paypal"]');
    if (status && status.mercadopago) {
      if (mpBtn) mpBtn.disabled = false;
    } else if (mpNote) {
      mpNote.textContent = 'Mercado Pago payments are being set up — check back soon.';
    }
    if (status && status.paypal) {
      if (ppBtn) ppBtn.disabled = false;
    } else if (ppNote) {
      ppNote.textContent = 'PayPal payments are being set up — check back soon.';
    }
  });
  const mpBtn = document.querySelector('[data-pay="mercadopago"]');
  const ppBtn = document.querySelector('[data-pay="paypal"]');
  if (mpBtn) mpBtn.addEventListener('click', () => submitOrder('mercadopago'));
  if (ppBtn) ppBtn.addEventListener('click', () => submitOrder('paypal'));
}

function initCheckoutPage() {
  const root = document.querySelector('[data-checkout]');
  if (!root) return;
  const countrySelect = document.getElementById('co-country');
  if (countrySelect) {
    countrySelect.innerHTML = '<option value="">Select your country</option>' +
      COUNTRIES.map(([code, label]) => `<option value="${escapeHtml(code)}">${escapeHtml(label)}</option>`).join('');
    countrySelect.addEventListener('change', () => {
      renderCartItems(root.querySelector('[data-cart-items]'), checkoutCurrencyForCountry(countrySelect.value));
      updateShippingQuote();
    });
  }
  refreshCheckoutPage();
  initPaymentButtons();
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
  /* Cart badge reflects whatever is already stored, on every page */
  updateCartBadge();

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

  /* Checkout page (cart, shipping quote, payment buttons) */
  initCheckoutPage();

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
