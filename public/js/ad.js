(function () {
  const root = document.querySelector("[data-gallery]");
  if (!root) return;

  const slides = Array.from(root.querySelectorAll(".ad-gallery__slide"));
  const thumbs = Array.from(root.querySelectorAll(".ad-gallery__thumb"));
  const btnPrev = root.querySelector("[data-prev]");
  const btnNext = root.querySelector("[data-next]");

  const lightbox = document.querySelector("[data-lightbox]");
  const lbImg = lightbox ? lightbox.querySelector(".ad-lightbox__img") : null;
  const lbPrev = lightbox ? lightbox.querySelector("[data-lb-prev]") : null;
  const lbNext = lightbox ? lightbox.querySelector("[data-lb-next]") : null;
  const lbClose = lightbox ? lightbox.querySelector("[data-close]") : null;

  let index = slides.findIndex(s => s.classList.contains("is-active"));
  if (index < 0) index = 0;

  function show(i) {
    index = (i + slides.length) % slides.length;
    slides.forEach(s => s.classList.remove("is-active"));
    thumbs.forEach(t => t.classList.remove("is-active"));
    slides[index].classList.add("is-active");
    if (thumbs[index]) {
      thumbs[index].classList.add("is-active");
      // автопрокрутка полосы превью
      thumbs[index].scrollIntoView({ block: "nearest", inline: "nearest", behavior: "smooth" });
    }
  }
  const next = () => show(index + 1);
  const prev = () => show(index - 1);

  btnNext && btnNext.addEventListener("click", next);
  btnPrev && btnPrev.addEventListener("click", prev);

  thumbs.forEach(t => t.addEventListener("click", () => show(Number(t.dataset.go) || 0)));

  // клавиатура
  document.addEventListener("keydown", (e) => {
    if (lightbox && !lightbox.hasAttribute("hidden")) {
      if (e.key === "Escape") closeLightbox();
      if (e.key === "ArrowRight") lbNext && lbNext.click();
      if (e.key === "ArrowLeft") lbPrev && lbPrev.click();
      return;
    }
    if (e.key === "ArrowRight") next();
    if (e.key === "ArrowLeft") prev();
  });

  // свайпы
  let x0 = null;
  root.addEventListener("touchstart", (e) => { x0 = e.touches[0].clientX; }, { passive: true });
  root.addEventListener("touchend", (e) => {
    if (x0 == null) return;
    const dx = e.changedTouches[0].clientX - x0;
    if (Math.abs(dx) > 40) (dx < 0 ? next() : prev());
    x0 = null;
  });

  // лайтбокс
  slides.forEach(s => s.addEventListener("click", () => openLightbox(index)));
  function openLightbox(i) {
    if (!lightbox || !lbImg) return;
    show(i);
    const img = slides[index].querySelector("img");
    lbImg.src = img ? img.src : "";
    lightbox.removeAttribute("hidden");
  }
  function closeLightbox() { lightbox && lightbox.setAttribute("hidden", ""); }
  lbClose && lbClose.addEventListener("click", closeLightbox);
  lightbox && lightbox.addEventListener("click", (e) => { if (e.target === lightbox) closeLightbox(); });
  lbNext && lbNext.addEventListener("click", () => { next(); lbImg.src = slides[index].querySelector("img").src; });
  lbPrev && lbPrev.addEventListener("click", () => { prev(); lbImg.src = slides[index].querySelector("img").src; });
})();
