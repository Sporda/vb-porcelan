/* Administrace Bílé zlato: klik na řádek poptávky, počítadlo znaků poznámky, ochrana proti dvojímu odeslání. */
(function () {
  'use strict';

  // Celý řádek přehledu otevře detail (odkaz na čísle poptávky zůstává pro klávesnici a čtečky).
  document.addEventListener('click', function (e) {
    var row = e.target.closest('tr[data-href]');
    if (!row || e.target.closest('a, button, input, label, select, textarea')) return;
    var selection = window.getSelection ? String(window.getSelection()) : '';
    if (selection.length > 0) return; // označování textu (např. e-mailu) není klik
    if (e.metaKey || e.ctrlKey || e.button === 1) {
      window.open(row.getAttribute('data-href'), '_blank', 'noopener');
      return;
    }
    window.location.href = row.getAttribute('data-href');
  });

  // Počítadlo znaků u interní poznámky
  var nf = new Intl.NumberFormat('cs-CZ');
  document.querySelectorAll('textarea[data-counter]').forEach(function (area) {
    var out = document.getElementById(area.getAttribute('data-counter'));
    var max = parseInt(area.getAttribute('maxlength'), 10) || 0;
    if (!out || !max) return;
    var update = function () {
      var n = area.value.replace(/\r\n?/g, '\n').length;
      out.textContent = nf.format(n) + ' / ' + nf.format(max);
      out.classList.toggle('is-near', n >= max * 0.9 && n <= max);
      out.classList.toggle('is-over', n > max);
    };
    area.addEventListener('input', update);
    update();
  });

  // Po odeslání formuláře tlačítko zablokovat (žádné dvojí odeslání e-mailu nebo přihlášení)
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.getAttribute('data-sending') === '1') {
      e.preventDefault();
      return;
    }
    form.setAttribute('data-sending', '1');
    var btn = form.querySelector('button[type="submit"]');
    if (btn) {
      btn.setAttribute('aria-busy', 'true');
      var busy = btn.getAttribute('data-busy');
      if (busy) {
        var label = Array.prototype.slice.call(btn.childNodes).filter(function (n) {
          return n.nodeType === 3 && n.textContent.trim() !== '';
        }).pop();
        if (label) {
          originals.set(btn, [label, label.textContent]);
          label.textContent = busy;
        }
      }
    }
  });

  // Návrat zpět v prohlížeči (bfcache) – formuláře zase odemknout
  var originals = new Map();
  window.addEventListener('pageshow', function () {
    document.querySelectorAll('form[data-sending]').forEach(function (form) {
      form.removeAttribute('data-sending');
      var btn = form.querySelector('button[aria-busy]');
      if (!btn) return;
      btn.removeAttribute('aria-busy');
      var saved = originals.get(btn);
      if (saved) saved[0].textContent = saved[1];
    });
  });
})();
