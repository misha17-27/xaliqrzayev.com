(function () {
  'use strict';

  /* Başlıq: ana səhifədə şəffaf, sürüşdürəndə tündləşir */
  var hdr = document.getElementById('hdr');
  if (hdr && !hdr.hasAttribute('data-solid')) {
    var onScroll = function () { hdr.classList.toggle('scrolled', window.scrollY > 40); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* Mobil menyu */
  var menu = document.getElementById('menu');
  var burger = document.querySelector('.burger');
  if (menu && burger) {
    burger.addEventListener('click', function () {
      var open = menu.classList.toggle('open');
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (hdr && open) hdr.classList.add('scrolled');
    });
    menu.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () { menu.classList.remove('open'); burger.setAttribute('aria-expanded', 'false'); });
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') menu.classList.remove('open'); });
  }

  /* Böyüklər / Uşaqlar tabları */
  document.querySelectorAll('.switch button').forEach(function (b) {
    b.addEventListener('click', function () {
      var sw = b.parentElement;
      sw.querySelectorAll('button').forEach(function (x) { x.classList.remove('active'); });
      b.classList.add('active');
      sw.parentElement.querySelectorAll('.pane').forEach(function (p) { p.classList.toggle('active', p.id === b.dataset.pane); });
    });
  });

  /* Bloklar görünəndə yumşaq peyda olur */
  var reveal = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
    }, { threshold: 0.12 });
    reveal.forEach(function (el) { io.observe(el); });
  } else {
    reveal.forEach(function (el) { el.classList.add('in'); });
  }

  /* Qəbul formu — səhifəni yeniləmədən */
  var form = document.getElementById('appt');
  if (form && window.fetch && window.FormData) {
    var msg = form.querySelector('.form-msg');
    var btn = form.querySelector('button[type=submit]');
    var show = function (ok, text) { msg.className = 'form-msg ' + (ok ? 'ok' : 'err'); msg.textContent = text; };

    form.addEventListener('submit', function (ev) {
      ev.preventDefault();
      var name = form.elements.name.value.trim();
      var phone = form.elements.phone.value.replace(/\D/g, '');
      if (name.length < 2) { show(false, form.dataset.errName); form.elements.name.focus(); return; }
      if (phone.length < 7) { show(false, form.dataset.errPhone); form.elements.phone.focus(); return; }

      btn.disabled = true;
      fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'fetch' } })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          show(j.ok, j.msg);
          if (j.ok) form.reset();
          if (window.turnstile) try { window.turnstile.reset(); } catch (e) {}
        })
        .catch(function () { show(false, form.dataset.errNet); })
        .then(function () { btn.disabled = false; });
    });
  }
})();
