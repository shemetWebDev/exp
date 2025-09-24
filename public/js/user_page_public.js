// public/js/user_page_public.js
(function () {
  const root = document.querySelector('.userpage');
  if (!root) return;

  // =========================
  // THEME: simple dark <-> light
  // =========================
  const THEME_KEY = 'userpage.theme';
  const themeBtn = document.getElementById('themeToggle');

  function prefersDark() {
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  }
  function getInitialTheme() {
    try {
      return localStorage.getItem(THEME_KEY) || (prefersDark() ? 'dark' : 'light');
    } catch {
      return prefersDark() ? 'dark' : 'light';
    }
  }
  function setTheme(v) {
    root.setAttribute('data-theme', v);
    try { localStorage.setItem(THEME_KEY, v); } catch {}
    if (themeBtn) {
      themeBtn.textContent = v === 'light' ? '🌞' : '🌙';
      themeBtn.title = v === 'light' ? 'Светлая тема' : 'Тёмная тема';
      themeBtn.setAttribute('aria-pressed', v === 'dark'); // true = тёмная
    }
    // цвет адресной строки на мобилках
    const meta = document.querySelector('meta[name="theme-color"]') || (() => {
      const m = document.createElement('meta'); m.name = 'theme-color'; document.head.appendChild(m); return m;
    })();
    meta.content = v === 'dark' ? '#0b0c10' : '#ffffff';
  }
  setTheme(getInitialTheme());
  themeBtn?.addEventListener('click', () => {
    const next = (root.getAttribute('data-theme') === 'dark') ? 'light' : 'dark';
    setTheme(next);
  });

  // =========================
  // Smooth scroll for in-page anchors
  // =========================
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      const id = a.getAttribute('href');
      const target = document.querySelector(id);
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  // =========================
  // Mobile nav toggle
  // =========================
  const navToggle = document.getElementById('navToggle');
  const navList   = document.getElementById('navMenu');
  if (navToggle && navList) {
    const close = () => { navList.classList.remove('is-open'); navToggle.setAttribute('aria-expanded','false'); };
    const open  = () => { navList.classList.add('is-open');    navToggle.setAttribute('aria-expanded','true'); };
    navToggle.addEventListener('click', () => {
      navList.classList.contains('is-open') ? close() : open();
    });
    navList.querySelectorAll('a').forEach(l => l.addEventListener('click', close));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
  }

  // =========================
  // Scroll spy (active link while scrolling)
  // =========================
  const sectionIds = ['#about', '#advantages', '#contacts', '#map'];
  const sections = sectionIds.map(id => document.querySelector(id)).filter(Boolean);
  const links = new Map(
    [...document.querySelectorAll('.nav__link')].map(l => [l.getAttribute('href'), l])
  );
  if (sections.length) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach(en => {
        const id = '#' + en.target.id;
        const link = links.get(id);
        if (!link) return;
        if (en.isIntersecting) {
          document.querySelectorAll('.nav__link').forEach(x => x.classList.remove('is-active'));
          link.classList.add('is-active');
        }
      });
    }, { rootMargin: '-40% 0px -55% 0px', threshold: 0.01 });
    sections.forEach(sec => io.observe(sec));
  }

  // =========================
  // Hero background from data attribute (if used)
  // Supports both variants:
  //  - <section style="background-image: ...">  (ничего не делаем)
  //  - <section data-hero-bg="...">            (добавляем фон + затемнение)
  // =========================
  document.querySelectorAll('.hero[data-hero-bg], .hero.hero--has-bg').forEach(el => {
    const url = el.getAttribute('data-hero-bg');
    if (url) {
      el.style.backgroundImage =
        `linear-gradient(180deg, rgba(6,8,15,0.55) 0%, rgba(6,8,15,0.85) 100%), url('${url}')`;
      el.style.backgroundSize = 'cover';
      el.style.backgroundPosition = 'center';
      el.style.backgroundRepeat = 'no-repeat';
    }
  });

  // =========================
  // Reveal on scroll (cards etc.)
  // =========================
  const toReveal = document.querySelectorAll('.reveal');
  if (toReveal.length) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach(en => { if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); } });
    }, { rootMargin: '0px 0px -10% 0px', threshold: 0.01 });
    toReveal.forEach(el => io.observe(el));
  }

  // =========================
  // Blur-up images
  // =========================
  document.querySelectorAll('img.img-loading').forEach(img => {
    if (img.complete) img.classList.add('img-ready');
    else img.addEventListener('load', () => img.classList.add('img-ready'), {once:true});
  });

  // =========================
  // Leaflet map (responsive + mobile non-blocking)
  // =========================
  const mapEl = document.getElementById('map');
  if (mapEl) {
    const parts = (mapEl.getAttribute('data-coords') || '').split(',');
    let lat = parseFloat((parts[0] || '').trim());
    let lng = parseFloat((parts[1] || '').trim());
    if (Number.isNaN(lat) || Number.isNaN(lng)) { lat = 48.8566; lng = 2.3522; } // fallback: Paris

    const leafletCss = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    const leafletJs  = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';

    function ensureLeaflet(cb) {
      if (window.L && window.L.map) return cb();
      const lcss = document.createElement('link');
      lcss.rel = 'stylesheet'; lcss.href = leafletCss; document.head.appendChild(lcss);
      const ljs = document.createElement('script');
      ljs.src = leafletJs; ljs.onload = cb; document.body.appendChild(ljs);
    }

    ensureLeaflet(() => {
      const map = L.map('map', { zoomControl: true }).setView([lat, lng], 12);
      const tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      });
      tiles.on('load', () => { mapEl.querySelector('.map__skeleton')?.remove(); });
      tiles.addTo(map);

      const marker = L.marker([lat, lng]).addTo(map);

      // В попап подставим адрес, если он есть в блоке контактов
      const addressNode = document.querySelector('.list__item:nth-child(3) span:last-child');
      const addressText = addressNode && addressNode.textContent;
      if (addressText) marker.bindPopup(addressText);

      // На мобильных выключаем интеракции и показываем оверлей "Активировать карту"
      const isCoarse = window.matchMedia?.('(pointer:coarse)')?.matches || window.innerWidth <= 860;
      if (isCoarse) {
        map.dragging.disable();
        map.touchZoom.disable();
        map.scrollWheelZoom.disable();
        map.doubleClickZoom.disable();
        map.boxZoom.disable();
        map.keyboard.disable();
        if (map.tap) map.tap.disable();

        const cover = document.createElement('div');
        cover.className = 'map__cover';
        cover.innerHTML = '<button type="button" class="map__cover-btn" aria-label="Активировать карту">Активировать карту</button>';
        mapEl.appendChild(cover);

        const enableMap = () => {
          map.dragging.enable();
          map.touchZoom.enable();
          map.scrollWheelZoom.enable();
          map.doubleClickZoom.enable();
          map.boxZoom.enable();
          map.keyboard.enable();
          if (map.tap) map.tap.enable();
          cover.remove();
        };
        cover.addEventListener('click', enableMap);
      }

      // resize fix
      setTimeout(() => map.invalidateSize(), 80);
      window.addEventListener('resize', () => setTimeout(() => map.invalidateSize(), 80), { passive: true });
    });
  }
})();
