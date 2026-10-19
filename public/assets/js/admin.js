/*
 * Admin panelinin köməkçisi.
 * Ayrıca fayldadır ki, CSP-də script-src üçün 'unsafe-inline' lazım olmasın.
 */
(function () {
  'use strict';

  /* ---------- Təsdiq: data-confirm ---------- */
  document.addEventListener('submit', function (ev) {
    var f = ev.target;
    if (!(f instanceof HTMLFormElement)) return;
    var msg = f.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) { ev.preventDefault(); ev.stopImmediatePropagation(); }
  }, true);

  document.addEventListener('click', function (ev) {
    var el = ev.target.closest('[data-confirm]');
    if (!el || el.tagName === 'FORM') return;
    var msg = el.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) { ev.preventDefault(); ev.stopPropagation(); }
  });

  /* ---------- Bir kliklə kopyalanan sahələr ---------- */
  document.addEventListener('click', function (ev) {
    var el = ev.target;
    if (!(el instanceof HTMLInputElement) || !el.hasAttribute('data-copy')) return;
    el.select();
    if (navigator.clipboard) navigator.clipboard.writeText(el.value);
  });

  /* ---------- Saxlanmamış dəyişikliklər ---------- */
  var dirty = false;
  var markDirty = function (ev) {
    var f = ev.target.form;
    if (f && f.method.toLowerCase() === 'post' && !f.hasAttribute('data-nodirty')) dirty = true;
  };
  document.addEventListener('input', markDirty);
  document.addEventListener('change', markDirty);
  document.addEventListener('submit', function () { dirty = false; });
  window.addEventListener('beforeunload', function (ev) {
    if (!dirty) return;
    ev.preventDefault();
    ev.returnValue = '';
  });

  /* ---------- Sətirlərin sırası: sürükləmə + ↑↓ ---------- */
  document.querySelectorAll('[data-sortable]').forEach(function (tbody) {
    var dragged = null;

    tbody.addEventListener('dragstart', function (ev) {
      var tr = ev.target.closest('tr');
      /* Sahənin içində mətn seçərkən sürükləmə başlamasın */
      if (!tr || ev.target.matches('input, textarea')) { ev.preventDefault(); return; }
      dragged = tr;
      tr.classList.add('dragging');
      ev.dataTransfer.effectAllowed = 'move';
      try { ev.dataTransfer.setData('text/plain', ''); } catch (e) {}
    });
    tbody.addEventListener('dragend', function () {
      if (dragged) dragged.classList.remove('dragging');
      dragged = null;
    });
    tbody.addEventListener('dragover', function (ev) {
      if (!dragged) return;
      ev.preventDefault();
      var over = ev.target.closest('tr');
      if (!over || over === dragged || over.classList.contains('new')) return;
      var r = over.getBoundingClientRect();
      var after = ev.clientY > r.top + r.height / 2;
      tbody.insertBefore(dragged, after ? over.nextSibling : over);
      dirty = true;
    });

    tbody.addEventListener('click', function (ev) {
      var b = ev.target.closest('[data-move]');
      if (!b) return;
      var tr = b.closest('tr');
      if (b.dataset.move === '-1') {
        var prev = tr.previousElementSibling;
        if (prev) tbody.insertBefore(tr, prev);
      } else {
        var next = tr.nextElementSibling;
        if (next && !next.classList.contains('new')) tbody.insertBefore(next, tr);
      }
      tr.classList.add('moved');
      setTimeout(function () { tr.classList.remove('moved'); }, 500);
      b.focus();
      dirty = true;
    });

    /* Saxlayanda cədvəldəki sıra = sort (10, 20, 30…) */
    var form = tbody.closest('form');
    if (form) form.addEventListener('submit', function () {
      var n = 0;
      tbody.querySelectorAll('input[data-sort]').forEach(function (inp) { n += 10; inp.value = n; });
    });
  });

  /* Silinəcək sətir vizual olaraq qeyd olunur */
  document.addEventListener('change', function (ev) {
    if (ev.target.classList && ev.target.classList.contains('del')) {
      ev.target.closest('tr').classList.toggle('to-delete', ev.target.checked);
    }
  });

  /* ---------- 2FA: QR kod (kitabxana lokal, açar heç yerə göndərilmir) ---------- */
  var qrBox = document.getElementById('totp-qr');
  if (qrBox && window.qrcode) {
    var qr = window.qrcode(0, 'M');
    qr.addData(qrBox.getAttribute('data-uri'));
    qr.make();
    qrBox.innerHTML = qr.createSvgTag({ cellSize: 5, margin: 2, scalable: true });
  }

  /* ---------- Simvol sayğacları: data-max ---------- */
  document.querySelectorAll('[data-max]').forEach(function (el) {
    var max = parseInt(el.getAttribute('data-max'), 10);
    var min = parseInt(el.getAttribute('data-min') || '0', 10);
    var out = document.createElement('div');
    out.className = 'counter';
    el.insertAdjacentElement('afterend', out);
    var upd = function () {
      var n = el.value.length;
      var rec = document.documentElement.lang === 'ru' ? 'рекомендуется' : 'tövsiyə olunur';
      out.textContent = n + ' / ' + max + (min ? ' (' + rec + ' ' + min + '–' + max + ')' : '');
      out.classList.toggle('over', n > max);
      out.classList.toggle('under', min > 0 && n < min);
    };
    el.addEventListener('input', upd);
    upd();
  });
})();
