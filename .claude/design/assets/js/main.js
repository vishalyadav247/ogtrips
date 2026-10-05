// Wanderway — UI interactions (WP: enqueue in functions.php, load Lucide before this)
document.addEventListener('DOMContentLoaded', function () {
  if (window.lucide) lucide.createIcons();

  var $ = function (s, ctx) { return (ctx || document).querySelector(s); };
  var $$ = function (s, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(s)); };

  // ---- Nav: hide on scroll down, show on scroll up ----
  var nav = $('.nav');
  var lastY = 0;
  window.addEventListener('scroll', function () {
    var y = window.scrollY;
    if (nav && !document.body.classList.contains('menu-open')) {
      nav.classList.toggle('is-hidden', y > lastY && y > 300);
    }
    lastY = y;
  }, { passive: true });

  // ---- Mobile menu ----
  var burger = $('.burger');
  var mobileMenu = $('.mobile-menu');
  if (burger && mobileMenu) {
    var setMenu = function (open) {
      mobileMenu.classList.toggle('is-open', open);
      document.body.classList.toggle('menu-open', open);
      document.body.style.overflow = open ? 'hidden' : '';
      burger.innerHTML = open ? '<i data-lucide="x"></i>' : '<i data-lucide="menu"></i>';
      if (window.lucide) lucide.createIcons();
    };
    burger.addEventListener('click', function () { setMenu(!mobileMenu.classList.contains('is-open')); });
    $$('a', mobileMenu).forEach(function (a) { a.addEventListener('click', function () { setMenu(false); }); });
  }

  // ---- Hero: animated places slideshow ----
  var slides = $$('.slide');
  if (slides.length) {
    var tabs = $$('.hp'), placeEl = $('.hero .place'), countryEl = $('#now-country');
    var SLIDE_MS = 6000, si = 0, slideTimer;
    document.documentElement.style.setProperty('--slide-time', SLIDE_MS / 1000 + 's');
    var go = function (n) {
      si = (n + slides.length) % slides.length;
      slides.forEach(function (s, k) { s.classList.toggle('is-active', k === si); });
      tabs.forEach(function (t, k) {
        t.classList.remove('is-active');
        t.classList.toggle('is-done', k < si);
        if (k === si) { void t.offsetWidth; t.classList.add('is-active'); }
      });
      var s = slides[si];
      if (placeEl) {
        placeEl.classList.remove('is-swap'); void placeEl.offsetWidth;
        placeEl.textContent = s.dataset.place; placeEl.classList.add('is-swap');
      }
      if (countryEl) countryEl.textContent = s.dataset.country;
      clearTimeout(slideTimer);
      slideTimer = setTimeout(function () { go(si + 1); }, SLIDE_MS);
    };
    tabs.forEach(function (t, k) { t.addEventListener('click', function () { go(k); }); });
    go(0);
  }

  // ---- Reveal on scroll ----
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) {
      if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
  $$('.reveal').forEach(function (el) { io.observe(el); });

  // ---- Count-up stats ----
  var countIO = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) {
      if (!e.isIntersecting) return;
      var el = e.target, end = parseFloat(el.dataset.count), dec = (el.dataset.count.split('.')[1] || '').length;
      var suffix = el.dataset.suffix || '', start = performance.now(), dur = 1600;
      (function tick(now) {
        var p = Math.min((now - start) / dur, 1), eased = 1 - Math.pow(1 - p, 3);
        el.textContent = (end * eased).toFixed(dec) + suffix;
        if (p < 1) requestAnimationFrame(tick);
      })(start);
      countIO.unobserve(el);
    });
  }, { threshold: 0.6 });
  $$('[data-count]').forEach(function (el) { countIO.observe(el); });

  // ---- Carousel arrows ----
  $$('[data-carousel]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var track = $(btn.dataset.target);
      var card = track.firstElementChild;
      var step = card ? card.getBoundingClientRect().width + 18 : 300;
      track.scrollBy({ left: btn.dataset.carousel === 'next' ? step : -step, behavior: 'smooth' });
    });
  });

  // ---- Tour filters (WP: itinerary taxonomy terms) ----
  var filters = $$('.filter');
  filters.forEach(function (f) {
    f.addEventListener('click', function () {
      filters.forEach(function (x) { x.classList.remove('is-active'); });
      f.classList.add('is-active');
      $$('.tour').forEach(function (t) {
        var show = f.dataset.filter === 'all' || t.dataset.cat === f.dataset.filter;
        t.classList.toggle('is-hidden', !show);
        t.classList.toggle('is-feature', show && f.dataset.filter === 'all' && t.hasAttribute('data-feature'));
      });
    });
  });

  // ---- Wishlist heart ----
  $$('.fav').forEach(function (b) {
    b.addEventListener('click', function (e) { e.preventDefault(); b.classList.toggle('is-on'); });
  });

  // ---- Testimonials slider ----
  var quotes = $$('.quote');
  if (quotes.length) {
    var dotsWrap = $('.dots'), qi = 0, timer;
    quotes.forEach(function (_, i) {
      var d = document.createElement('button');
      d.setAttribute('aria-label', 'Review ' + (i + 1));
      d.addEventListener('click', function () { showQuote(i); });
      dotsWrap.appendChild(d);
    });
    var showQuote = function (i) {
      qi = (i + quotes.length) % quotes.length;
      quotes.forEach(function (q, k) { q.classList.toggle('is-active', k === qi); });
      $$('button', dotsWrap).forEach(function (d, k) { d.classList.toggle('is-active', k === qi); });
      clearInterval(timer); timer = setInterval(function () { showQuote(qi + 1); }, 7000);
    };
    $('[data-quote="prev"]').addEventListener('click', function () { showQuote(qi - 1); });
    $('[data-quote="next"]').addEventListener('click', function () { showQuote(qi + 1); });
    showQuote(0);
  }

  // ---- Accordions (itinerary days + FAQ) ----
  $$('.day-head').forEach(function (h) {
    h.addEventListener('click', function () {
      var d = h.closest('.day'); d.classList.toggle('is-open');
      h.setAttribute('aria-expanded', d.classList.contains('is-open'));
    });
  });
  $$('.faq-q').forEach(function (q) {
    q.addEventListener('click', function () {
      var item = q.closest('.faq-item'); item.classList.toggle('is-open');
      q.setAttribute('aria-expanded', item.classList.contains('is-open'));
    });
  });
  var expandAll = $('[data-expand-all]');
  if (expandAll) {
    expandAll.addEventListener('click', function () {
      var days = $$('.day'), allOpen = days.every(function (d) { return d.classList.contains('is-open'); });
      days.forEach(function (d) { d.classList.toggle('is-open', !allOpen); });
      expandAll.firstChild.textContent = allOpen ? 'Expand all ' : 'Collapse all ';
    });
  }

  // ---- Traveller stepper + live total ----
  var pax = $('#pax');
  if (pax) {
    var price = parseInt(pax.dataset.price, 10);
    var fmt = function (n) { return '₹' + n.toLocaleString('en-IN'); };
    var update = function (v) {
      v = Math.max(1, Math.min(12, v)); pax.textContent = v;
      $('#total').textContent = fmt(price * v);
    };
    $('[data-step="minus"]').addEventListener('click', function () { update(+pax.textContent - 1); });
    $('[data-step="plus"]').addEventListener('click', function () { update(+pax.textContent + 1); });
  }

  // ---- Scroll-spy for sub nav / table of contents ----
  var spyLinks = $$('.subnav a, .toc a');
  if (spyLinks.length) {
    var targets = spyLinks.map(function (a) { return $(a.getAttribute('href')); }).filter(Boolean);
    var spy = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (!e.isIntersecting) return;
        spyLinks.forEach(function (a) { a.classList.toggle('is-active', a.getAttribute('href') === '#' + e.target.id); });
      });
    }, { rootMargin: '-35% 0px -60% 0px' });
    targets.forEach(function (t) { spy.observe(t); });
  }

  // ---- Reading progress ----
  var bar = $('.progress');
  if (bar) {
    window.addEventListener('scroll', function () {
      var h = document.documentElement.scrollHeight - window.innerHeight;
      bar.style.width = (h > 0 ? (window.scrollY / h) * 100 : 0) + '%';
    }, { passive: true });
  }

  // ---- Demo forms (WP: replace with Contact Form 7 / WPForms / Fluent Forms) ----
  $$('form').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      var btn = $('button[type="submit"]', f);
      if (btn) { btn.innerHTML = 'Thank you — we\'ll be in touch'; btn.disabled = true; }
    });
  });

  var y = $('#year'); if (y) y.textContent = new Date().getFullYear();
});
