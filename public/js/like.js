(function(){
  const widgets = document.querySelectorAll("[data-like]");
  if (!widgets.length) return;

  widgets.forEach(w => {
    const btn = w.querySelector(".like__btn");
    const cnt = w.querySelector("[data-like-count]");
    const url = w.dataset.url;
    const token = w.dataset.token;
    if (!btn || !cnt || !url || !token) return;

    btn.addEventListener("click", async () => {
      if (btn.disabled) return;
      try {
        const resp = await fetch(url, {
          method: "POST",
          headers: { "X-CSRF-TOKEN": token, "Content-Type": "application/x-www-form-urlencoded" },
          body: "",
          credentials: "same-origin"
        });
        const data = await resp.json();
        if (!resp.ok || !data.ok) return;

        btn.classList.toggle("is-liked", !!data.liked);
        btn.setAttribute("aria-pressed", data.liked ? "true" : "false");
        cnt.textContent = String(data.count);

        // если где-то нужно реагировать (например, убирать карточку из «понравившихся»)
        const adId = w.dataset.adId;
        window.dispatchEvent(new CustomEvent('ads:like-toggled', {
          detail: { adId, liked: !!data.liked, count: data.count }
        }));
      } catch (e) {
        console.error(e);
      }
    });
  });
})();
