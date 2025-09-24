document.addEventListener('DOMContentLoaded', () => {
  // --- Счётчики символов ---
  document.querySelectorAll('[data-counter-for]').forEach(c => {
    const name = c.getAttribute('data-counter-for');
    const input = document.querySelector('[data-field="'+name+'"]');
    if (!input) return;
    const max = Number(input.getAttribute('maxlength') || 0);
    const update = () => c.textContent = input.value.length + ' / ' + max;
    input.addEventListener('input', update); update();
  });

  // --- Авто-slug до первого ручного редактирования ---
  const formEl = document.getElementById('userPageForm');
  const titleEl = formEl?.querySelector('[data-field="title"]');
  const slugEl  = formEl?.querySelector('[data-field="slug"]');
  let userEditedSlug = false;
  slugEl?.addEventListener('input', ()=> userEditedSlug = true);
  if (titleEl && slugEl) {
    const slugify = (s)=>s.toString().trim().toLowerCase()
      .replace(/[^\w\s-]+/g,'').replace(/_/g,'-').replace(/\s+/g,'-').replace(/-+/g,'-');
    titleEl.addEventListener('input', ()=>{ if (!userEditedSlug) slugEl.value = slugify(titleEl.value); });
  }

  // --- Предпросмотр файлов + валидация ---
  const accept = ['image/jpeg','image/png','image/gif','image/webp'];
  document.querySelectorAll('.ui-file').forEach(input=>{
    input.addEventListener('change', e=>{
      const file = e.target.files && e.target.files[0];
      const max  = Number(e.target.getAttribute('data-max')||0);
      const previewSel = e.target.getAttribute('data-preview');
      const role = e.target.getAttribute('data-role');
      if (!file) return;
      if (!accept.includes(file.type)){ e.target.value=''; alert('Недопустимый формат. JPEG/PNG/GIF/WebP.'); return; }
      if (max && file.size>max){ e.target.value=''; alert('Файл слишком большой. Максимум 5 МБ.'); return; }
      if (!previewSel) return;

      const preview = document.querySelector(previewSel);
      if (!preview) return;
      preview.innerHTML='';
      const reader = new FileReader();
      reader.onload = ()=>{
        const img = new Image();
        img.onload = ()=>{
          if (role==='file-banner'){
            const ratio = img.width/img.height;
            if (ratio<2 || ratio>4){
              const warn = document.createElement('div');
              warn.className='ui-tip ui-tip--warn';
              warn.textContent='Совет: баннер лучше делать широким (~16:6, например 1600×600).';
              preview.appendChild(warn);
            }
          }
        };
        img.src = reader.result; img.className='ui-preview__img'; img.alt='Предпросмотр';
        preview.appendChild(img); preview.setAttribute('aria-hidden','false');
      };
      reader.readAsDataURL(file);
    });
  });

  // --- Карта: инициализируем только если есть контейнер ---
  const mapEl = document.getElementById('map');
  if (!mapEl || typeof L === 'undefined') return;

  const hiddenMap = document.querySelector('input[name="user_page[mapPosition]"]');
  const coordField = document.getElementById('coordinates');
  const btnLocate  = document.getElementById('btnLocate');
  const placeInput = document.getElementById('placeSearch');
  const placeBtn   = document.getElementById('placeSubmit');

  function setCoords(lat,lng){
    const val = lat.toFixed(6)+','+lng.toFixed(6);
    coordField.value = val;
    if (hiddenMap) hiddenMap.value = val;
  }

  let start = [48.8566, 2.3522];
  if (hiddenMap && hiddenMap.value && hiddenMap.value.includes(',')) {
    const parts = hiddenMap.value.split(',').map(parseFloat);
    if (!isNaN(parts[0]) && !isNaN(parts[1])) start = [parts[0], parts[1]];
  }

  const map = L.map('map', { zoomControl: true }).setView(start, 10);
  const tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    { attribution: '&copy; OpenStreetMap contributors' });
  tiles.on('load', ()=>{
    const skel = document.querySelector('.wizard__map-skel');
    if (skel) skel.style.display='none';
  });
  tiles.addTo(map);

  const marker = L.marker(start, { draggable:true }).addTo(map);
  setCoords(start[0], start[1]);

  marker.on('dragend', e=>{ const p = e.target.getLatLng(); setCoords(p.lat, p.lng); });
  map.on('click', e=>{ marker.setLatLng(e.latlng); setCoords(e.latlng.lat, e.latlng.lng); });
  setTimeout(()=>map.invalidateSize(), 60);

  btnLocate?.addEventListener('click', ()=>{
    if (!navigator.geolocation){ alert('Геолокация не поддерживается браузером.'); return; }
    navigator.geolocation.getCurrentPosition(
      pos => { const {latitude:lat, longitude:lng} = pos.coords; map.setView([lat,lng], 13); marker.setLatLng([lat,lng]); setCoords(lat,lng); },
      () => alert('Не удалось определить местоположение.')
    );
  });

  // Поиск по кнопке (Nominatim)
  async function geocodeAndCenter(query){
    const q = (query||'').trim();
    if (q.length < 2) return;
    const url = new URL('https://nominatim.openstreetmap.org/search');
    url.searchParams.set('format','json');
    url.searchParams.set('limit','8');
    url.searchParams.set('q', q);
    url.searchParams.set('accept-language','ru,en');
    try{
      const res = await fetch(url.toString(), { headers:{'Accept':'application/json'} });
      if (!res.ok) return;
      const data = await res.json();
      if (!data.length){ alert('Ничего не найдено.'); return; }
      // предпочитаем города
      const ok = new Set(['city','town','village','hamlet','municipality']);
      const cand = data.find(i=>ok.has(i.type||'')) || data[0];
      const lat = parseFloat(cand.lat), lon = parseFloat(cand.lon);
      if (isNaN(lat) || isNaN(lon)) return;
      map.setView([lat,lon], 12);
      marker.setLatLng([lat,lon]);
      setCoords(lat, lon);
    }catch(e){ /* no-op */ }
  }

  placeBtn?.addEventListener('click', ()=> geocodeAndCenter(placeInput.value));
  placeInput?.addEventListener('keydown', (e)=>{
    if (e.key === 'Enter'){ e.preventDefault(); geocodeAndCenter(placeInput.value); }
  });
});
