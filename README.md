# SKINMETAL — Official Website

The official website for **SKINMETAL**, "The New Force of Brazilian Heavy Metal" — a static, dark, cinematic site covering the band's music, videos, the SKINMETAL/Tecno Metal universe, live shows, press, merch and contact.

## Structure

- `index.html` — Home (hero, manifesto, new album countdown, discography preview, universe preview)
- `music.html` — Discography (featured release + full catalog)
- `videos.html` — Music videos, lyric videos, live, short films, Metal Tales, behind the scenes
- `universe.html` — The Story, Tecno Metal manifesto, Characters, Metal Tales
- `live.html` — Tour dates, live album, gallery
- `media.html` — Press kit, photos, logos, press releases, interviews
- `merch.html` — Official merch store (filterable grid)
- `about.html` — Band bio, creator, positioning
- `contact.html` — Booking / press / business / partnerships contact channels + form
- `css/styles.css` — Design system (colors, typography, components)
- `js/main.js` — Nav, mobile menu, hero sparks, album countdown, scroll reveal, filters

## Design system

- **Palette:** black `#050505`, crimson `#8B0000`, blood red `#B00000`, fire orange `#FF5A00`, silver `#B8B8B8`, gold `#C9A227`
- **Typography:** Metal Mania (display) + Space Grotesk (body), via Google Fonts
- **Countdown:** targets the new album release, September 26, 2026 (America/Sao_Paulo)

## Running locally

Static site, no build step required:

```bash
python3 -m http.server 8000
```

Then open `http://localhost:8000`.
