document.addEventListener('DOMContentLoaded', function () {
  // Splide (популярные)
  if (document.querySelector('#popular-slider')) {
    new Splide('#popular-slider', {
      type: 'loop',
      perPage: 3,
      perMove: 1,
      gap: '1rem',
      autoplay: true,
      pauseOnHover: true,
      breakpoints: {
        1200: { perPage: 2 },
        768:  { perPage: 1 }
      }
    }).mount();
  }

  // Chart.js (примерная картинка)
  const ctx = document.getElementById('visitsChart');
  if (ctx && window.Chart) {
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: ['Янв','Фев','Мар','Апр','Май','Июн','Июл'],
        datasets: [
          {
            label: 'Посещения',
            data: [800, 1200, 1800, 2600, 3400, 4200, 5100],
            borderColor: 'rgba(75, 192, 192, 1)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            fill: true,
            tension: 0.2
          }
        ]
      },
      options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: { grid: { display: false } }, y: { beginAtZero: true } }
      }
    });
  }
});
