/* ============================================
   AquaFix Pro — JavaScript
   Dark mode, navbar, animations, forms, sliders
   ============================================ */

/* ---------- SVG Icon Renderer ---------- */
const ICONS = {
  'droplet': '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>',
  'waves': '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 6c.6.5 1.2 1 2.5 1C7 7 7 5 9.5 5c2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 18c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/></svg>',
  'flame': '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>',
  'git-branch': '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="3" x2="6" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/></svg>',
  'shower-head': '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4l16 16"/><path d="M14 4a4 4 0 0 1 4 4"/><path d="M14 4v6"/><path d="M11 13l1.5 1.5"/><path d="M18 13l1.5 1.5"/></svg>',
  'alert-triangle': '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
  'siren': '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 18v-6a5 5 0 1 1 10 0v6"/><path d="M5 21a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v1H5z"/><path d="M12 2v2"/><path d="M4 6l1.5 1.5"/><path d="M20 6l-1.5 1.5"/></svg>',
  'shield-check': '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>',
  'sparkles': '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5z"/><path d="M19 16l.7 2.3L22 19l-2.3.7L19 22l-.7-2.3L16 19l2.3-.7z"/></svg>',
};
function renderIcon(name) { return ICONS[name] || ICONS['droplet']; }

/* ---------- Loader ---------- */
window.addEventListener('load', () => {
  const loader = document.getElementById('loader');
  if (loader) setTimeout(() => loader.classList.add('hide'), 400);
});

/* ---------- Theme (Dark Mode) ---------- */
(function initTheme() {
  const saved = localStorage.getItem('theme') || 'light';
  document.documentElement.setAttribute('data-theme', saved);
})();
function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme');
  const next = current === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', next);
  localStorage.setItem('theme', next);
  updateThemeIcon(next);
}
function updateThemeIcon(theme) {
  const btn = document.getElementById('themeToggle');
  if (!btn) return;
  btn.innerHTML = theme === 'dark'
    ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>'
    : '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
}
document.addEventListener('DOMContentLoaded', () => {
  updateThemeIcon(document.documentElement.getAttribute('data-theme') || 'light');
  const tBtn = document.getElementById('themeToggle');
  if (tBtn) tBtn.addEventListener('click', toggleTheme);

  /* ---------- Navbar scroll effect ---------- */
  const navbar = document.getElementById('navbar');
  if (navbar) {
    window.addEventListener('scroll', () => {
      navbar.classList.toggle('navbar--scrolled', window.scrollY > 20);
    });
  }

  /* ---------- Mobile menu ---------- */
  const hamburger = document.getElementById('hamburger');
  const mobileMenu = document.getElementById('mobileMenu');
  if (hamburger && mobileMenu) {
    hamburger.addEventListener('click', () => {
      hamburger.classList.toggle('open');
      mobileMenu.classList.toggle('open');
    });
    mobileMenu.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', () => {
        hamburger.classList.remove('open');
        mobileMenu.classList.remove('open');
      });
    });
  }

  /* ---------- Reveal on scroll ---------- */
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });
  document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

  /* ---------- Stats counter ---------- */
  const counters = document.querySelectorAll('[data-count]');
  const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      const el = entry.target;
      const target = parseInt(el.dataset.count, 10);
      const suffix = el.dataset.suffix || '';
      let current = 0;
      const step = Math.max(1, Math.ceil(target / 50));
      const timer = setInterval(() => {
        current += step;
        if (current >= target) { current = target; clearInterval(timer); }
        el.textContent = current.toLocaleString() + suffix;
      }, 30);
      counterObserver.unobserve(el);
    });
  }, { threshold: 0.5 });
  counters.forEach(c => counterObserver.observe(c));

  /* ---------- Back to top ---------- */
  const backTop = document.getElementById('backTop');
  if (backTop) {
    window.addEventListener('scroll', () => {
      backTop.classList.toggle('visible', window.scrollY > 400);
    });
    backTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  /* ---------- Cookie consent ---------- */
  const cookieBar = document.getElementById('cookieBar');
  if (cookieBar && !localStorage.getItem('cookieConsent')) {
    setTimeout(() => cookieBar.classList.add('show'), 1200);
  }
  const cookieAccept = document.getElementById('cookieAccept');
  if (cookieAccept) {
    cookieAccept.addEventListener('click', () => {
      localStorage.setItem('cookieConsent', '1');
      cookieBar.classList.remove('show');
    });
  }

  /* ---------- Service filter ---------- */
  const filterBtns = document.querySelectorAll('.filter-btn');
  if (filterBtns.length) {
    filterBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const cat = btn.dataset.filter;
        document.querySelectorAll('.service-card').forEach(card => {
          const match = cat === 'all' || card.dataset.category === cat;
          card.style.display = match ? '' : 'none';
        });
      });
    });
  }

  /* ---------- Booking step navigation ---------- */
  const bookingForm = document.getElementById('bookingForm');
  if (bookingForm) initBookingSteps(bookingForm);

  /* ---------- Form validation ---------- */
  document.querySelectorAll('form[data-validate]').forEach(form => {
    form.addEventListener('submit', (e) => {
      let valid = true;
      form.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) {
          valid = false;
          field.style.borderColor = 'var(--c-error)';
          setTimeout(() => { field.style.borderColor = ''; }, 2000);
        } else {
          field.style.borderColor = '';
        }
        if (field.type === 'email' && field.value) {
          if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value)) {
            valid = false;
            field.style.borderColor = 'var(--c-error)';
          }
        }
      });
      if (!valid) {
        e.preventDefault();
        const msg = form.querySelector('.form-msg');
        if (msg) {
          msg.className = 'form-msg form-msg--error';
          msg.textContent = 'Please fill in all required fields correctly.';
        }
      }
    });
  });
});

/* ---------- Booking Steps Logic ---------- */
function initBookingSteps(form) {
  const steps = document.querySelectorAll('.booking-step');
  const stepPanels = document.querySelectorAll('.booking-panel');
  const nextBtns = form.querySelectorAll('[data-action="next"]');
  const prevBtns = form.querySelectorAll('[data-action="prev"]');
  const stepIndicator = document.getElementById('stepIndicator');
  let current = 0;

  function showStep(idx) {
    stepPanels.forEach((p, i) => p.classList.toggle('hidden', i !== idx));
    steps.forEach((s, i) => {
      s.classList.remove('active', 'done');
      if (i < idx) s.classList.add('done');
      else if (i === idx) s.classList.add('active');
    });
    if (stepIndicator) stepIndicator.textContent = 'Step ' + (idx + 1) + ' of ' + stepPanels.length;
    current = idx;
    window.scrollTo({ top: form.offsetTop - 100, behavior: 'smooth' });
  }

  nextBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const panel = stepPanels[current];
      let valid = true;
      panel.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) { valid = false; field.style.borderColor = 'var(--c-error)'; }
        else { field.style.borderColor = ''; }
      });
      if (valid && current < stepPanels.length - 1) showStep(current + 1);
    });
  });

  prevBtns.forEach(btn => {
    btn.addEventListener('click', () => { if (current > 0) showStep(current - 1); });
  });

  showStep(0);
}
