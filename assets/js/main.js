(function () {
  'use strict';

  /* Mobil menyu */
  var menu = document.getElementById('menu');
  var burger = document.querySelector('.burger');
  if (menu && burger) {
    burger.addEventListener('click', function () {
      var open = menu.classList.toggle('open');
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    menu.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () { menu.classList.remove('open'); burger.setAttribute('aria-expanded', 'false'); });
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') menu.classList.remove('open'); });
  }

  /* «Hamısını göstər» */
  document.querySelectorAll('.toggle').forEach(function (b) {
    var label = b.textContent;
    b.addEventListener('click', function () {
      var open = b.closest('.aud-card').classList.toggle('open');
      b.textContent = open ? 'Gizlət ↑' : label;
      b.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });

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
      if (name.length < 2) { show(false, 'Adınızı daxil edin.'); form.elements.name.focus(); return; }
      if (phone.length < 7) { show(false, 'Telefon nömrəsini düzgün daxil edin.'); form.elements.phone.focus(); return; }

      btn.disabled = true;
      fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'fetch' } })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          show(j.ok, j.msg);
          if (j.ok) form.reset();
          if (window.turnstile) try { window.turnstile.reset(); } catch (e) {}
        })
        .catch(function () { show(false, 'Xəta baş verdi. Zəhmət olmasa zəng edin.'); })
        .then(function () { btn.disabled = false; });
    });
  }
})();
