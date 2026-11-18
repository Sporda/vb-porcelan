/* Bílé zlato – drobná vylepšení stránek. Všechno funguje i bez JS (formuláře a odkazy), tady jen pohodlí navíc. */
(function () {
  'use strict';

  var NB = ' ';
  var doc = document;
  var $ = function (sel, root) { return (root || doc).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || doc).querySelectorAll(sel)); };
  var fmt = function (n) { return String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, NB) + NB + 'Kč'; };
  var reduced = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Odeslání formuláře přes fetch (JSON) ---------- */
  function send(form, extraHeaders, submitter) {
    var data = new FormData(form);
    if (submitter && submitter.name) data.set(submitter.name, submitter.value);
    var headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    Object.keys(extraHeaders || {}).forEach(function (k) { headers[k] = extraHeaders[k]; });
    return fetch(form.action, { method: 'POST', body: data, headers: headers, credentials: 'same-origin' })
      .then(function (r) {
        return r.json().catch(function () { return {}; }).then(function (json) {
          if (r.status === 419) { json.message = 'Stránka vypršela. Načtěte ji prosím znovu.'; }
          if (r.status === 429) { json.message = 'Příliš mnoho pokusů, zkuste to za chvíli.'; }
          if (!r.ok && !json.message && json.errors) {
            var first = Object.keys(json.errors)[0];
            json.message = json.errors[first][0];
          }
          json.httpOk = r.ok;
          json.status = r.status;
          return json;
        });
      });
  }

  /* ---------- Počty v hlavičce ---------- */
  function setBadge(sel, linkSel, n, label) {
    var badge = $(sel);
    var link = $(linkSel);
    if (!badge) return;
    badge.textContent = n;
    badge.hidden = !n;
    badge.classList.add('bump');
    setTimeout(function () { badge.classList.remove('bump'); }, 220);
    if (link && label) link.setAttribute('aria-label', label);
  }
  function plural(n, one, few, many) { return n === 1 ? one : (n >= 2 && n <= 4 ? few : many); }
  function setCartCount(n) { setBadge('[data-cart-count]', '[data-cart-link]', n, 'Košík, ' + n + ' ' + plural(n, 'kus', 'kusy', 'kusů')); }
  function setFavCount(n) { setBadge('[data-fav-count]', '[data-fav-link]', n, 'Oblíbené' + (n ? ', ' + n : '')); }

  /* ---------- Toast ---------- */
  var toast = $('#toast');
  var toastTimer;
  function showToast(opts) {
    if (!toast) return;
    var img = $('#toastImg');
    if (opts.image) { img.src = opts.image; toast.classList.remove('no-img'); } else { toast.classList.add('no-img'); }
    $('#toastTitle').textContent = opts.title || 'Přidáno do košíku';
    $('#toastName').textContent = opts.name || '';
    $('#toastMeta').textContent = opts.meta || '';
    var link = $('.t3 a', toast);
    if (link) link.hidden = opts.hideLink === true;
    toast.classList.toggle('is-error', !!opts.error);
    toast.classList.add('is-on');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toast.classList.remove('is-on'); }, opts.error ? 5000 : 3600);
  }
  if (toast) {
    toast.addEventListener('mouseenter', function () { clearTimeout(toastTimer); });
    toast.addEventListener('mouseleave', function () { toastTimer = setTimeout(function () { toast.classList.remove('is-on'); }, 1600); });
  }

  /* ---------- Přidání do košíku (karty i detail produktu) ---------- */
  doc.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.matches || !form.matches('[data-add-form]')) return;
    e.preventDefault();
    var btn = e.submitter || $('[type=submit]', form);
    if (btn) btn.classList.add('is-busy');
    send(form, null, null).then(function (res) {
      if (btn) btn.classList.remove('is-busy');
      if (!res.httpOk) { showToast({ title: 'Nepodařilo se přidat', name: res.message || 'Zkuste to prosím znovu.', error: true, hideLink: true }); return; }
      setCartCount(res.cart.count);
      var a = res.added || {};
      var meta = (a.collection ? a.collection + ' · ' : '') + (a.option ? a.option + ' · ' : '') + a.qty + '×' + NB + a.unit_price_formatted;
      showToast({ title: 'Přidáno do košíku', name: a.name, meta: meta, image: a.image });
    }).catch(function () {
      if (btn) btn.classList.remove('is-busy');
      form.submit();
    });
  });

  /* ---------- Oblíbené ---------- */
  doc.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.matches || !form.matches('[data-wish-form]')) return;
    e.preventDefault();
    var btn = $('button', form) || $('button[form="' + form.id + '"]');
    send(form).then(function (res) {
      if (!res.httpOk) { showToast({ title: 'Oblíbené', name: res.message || 'Zkuste to prosím znovu.', error: true, hideLink: true }); return; }
      if (btn) {
        btn.classList.toggle('is-on', res.on);
        btn.setAttribute('aria-pressed', res.on ? 'true' : 'false');
        var label = btn.getAttribute('aria-label');
        if (label) btn.setAttribute('aria-label', label.replace(/^(Odebrat z oblíbených|Uložit do oblíbených)/, res.on ? 'Odebrat z oblíbených' : 'Uložit do oblíbených'));
      }
      setFavCount(res.count);
      showToast({ title: res.on ? 'Uloženo do oblíbených' : 'Odebráno z oblíbených', name: res.message.replace(/^[^:]+:\s*/, ''), hideLink: true });
    }).catch(function () { form.submit(); });
  });

  /* ---------- Košík: změna množství, odebrání, vyprázdnění ---------- */
  var cartContent = $('[data-cart-content]');
  if (cartContent) {
    cartContent.addEventListener('submit', function (e) {
      var form = e.target;
      if (!form.matches('[data-cart-form]')) return;
      e.preventDefault();
      cartContent.classList.add('is-loading');
      send(form, { 'X-Shop-Partial': 'cart' }, e.submitter).then(function (res) {
        cartContent.classList.remove('is-loading');
        if (res.html !== undefined) cartContent.innerHTML = res.html;
        if (res.cart) setCartCount(res.cart.count);
        if (!res.httpOk && res.message) showToast({ title: 'Košík', name: res.message, error: true, hideLink: true });
      }).catch(function () { cartContent.classList.remove('is-loading'); form.submit(); });
    });
  }

  /* ---------- Menu (drawer), filtry, hledání ---------- */
  var overlay = $('#overlay');
  var drawer = $('#drawer');
  var filters = $('#filters');
  var menuBtn = $('[data-open-drawer]');
  var filterBtn = $('[data-open-filters]');
  var lastFocus = null;
  function closeAll() {
    if (drawer) { drawer.classList.remove('is-open'); drawer.setAttribute('aria-hidden', 'true'); }
    if (filters) filters.classList.remove('is-open');
    if (overlay) overlay.classList.remove('is-open');
    if (menuBtn) menuBtn.setAttribute('aria-expanded', 'false');
    if (filterBtn) filterBtn.setAttribute('aria-expanded', 'false');
    doc.body.style.overflow = '';
    if (lastFocus) { lastFocus.focus(); lastFocus = null; }
  }
  if (menuBtn && drawer) {
    menuBtn.addEventListener('click', function () {
      lastFocus = menuBtn;
      drawer.classList.add('is-open'); drawer.setAttribute('aria-hidden', 'false');
      overlay.classList.add('is-open'); menuBtn.setAttribute('aria-expanded', 'true');
      doc.body.style.overflow = 'hidden';
      var f = $('nav a', drawer); if (f) f.focus();
    });
  }
  if (filterBtn && filters) {
    filterBtn.addEventListener('click', function () {
      lastFocus = filterBtn;
      filters.classList.add('is-open'); overlay.classList.add('is-open');
      filterBtn.setAttribute('aria-expanded', 'true');
      doc.body.style.overflow = 'hidden';
      var f = $('input, summary', filters); if (f) f.focus();
    });
  }
  $$('[data-close-all],[data-close-filters]').forEach(function (b) { b.addEventListener('click', closeAll); });

  var search = $('#search');
  var searchBtn = $('[data-search-toggle]');
  function toggleSearch(open) {
    if (!search) return;
    search.hidden = !open;
    if (searchBtn) searchBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open) { var q = $('input', search); if (q) q.focus(); } else if (searchBtn) { searchBtn.focus(); }
  }
  if (searchBtn && search) {
    searchBtn.addEventListener('click', function (e) { e.preventDefault(); toggleSearch(search.hidden); });
    $$('[data-search-close]').forEach(function (b) { b.addEventListener('click', function () { toggleSearch(false); }); });
  }
  doc.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if ((drawer && drawer.classList.contains('is-open')) || (filters && filters.classList.contains('is-open'))) closeAll();
    if (search && !search.hidden) toggleSearch(false);
  });

  /* ---------- Úvod: náhled kategorií ---------- */
  var catList = $('#catList');
  var catPreview = $('#catPreview');
  if (catList && catPreview) {
    var setCat = function (i) {
      $$('.cat-row', catList).forEach(function (r) { r.classList.toggle('is-active', r.getAttribute('data-i') === String(i)); });
      $$('img', catPreview).forEach(function (im) { im.classList.toggle('is-active', im.getAttribute('data-i') === String(i)); });
    };
    var onRow = function (e) { var r = e.target.closest('.cat-row'); if (r) setCat(r.getAttribute('data-i')); };
    catList.addEventListener('mouseover', onRow);
    catList.addEventListener('focusin', onRow);
  }

  /* ---------- Úvod: záložky výběru ---------- */
  var tabs = $$('[data-pick]');
  if (tabs.length) {
    var selectTab = function (t, focus) {
      tabs.forEach(function (x) {
        var on = x === t;
        x.setAttribute('aria-selected', on ? 'true' : 'false');
        x.setAttribute('tabindex', on ? '0' : '-1');
        var panel = $('[data-pick-panel="' + x.getAttribute('data-pick') + '"]');
        if (panel) panel.hidden = !on;
      });
      if (focus) t.focus();
    };
    tabs.forEach(function (t, i) {
      t.setAttribute('tabindex', t.getAttribute('aria-selected') === 'true' ? '0' : '-1');
      t.addEventListener('click', function (e) { e.preventDefault(); selectTab(t, false); });
      t.addEventListener('keydown', function (e) {
        var n = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
        if (!n) return;
        e.preventDefault();
        selectTab(tabs[(i + n + tabs.length) % tabs.length], true);
      });
    });
  }

  /* ---------- Detail: galerie ---------- */
  var main = $('#galleryMain');
  var thumbs = $$('[data-thumb]');
  if (main && thumbs.length) {
    var count = $('#galleryCount');
    var total = String(thumbs.length).padStart(2, '0');
    thumbs.forEach(function (t, i) {
      t.addEventListener('click', function (e) {
        e.preventDefault();
        if (t.classList.contains('is-active')) return;
        thumbs.forEach(function (x) { x.classList.toggle('is-active', x === t); if (x === t) x.setAttribute('aria-current', 'true'); else x.removeAttribute('aria-current'); });
        main.classList.add('fade');
        var next = new Image();
        next.onload = next.onerror = function () {
          main.srcset = t.getAttribute('data-srcset') || '';
          main.src = t.getAttribute('data-src');
          main.alt = t.getAttribute('data-alt') || main.alt;
          main.style.objectPosition = t.getAttribute('data-focus') || '';
          main.classList.remove('fade');
        };
        next.src = t.getAttribute('data-src');
        if (count) count.textContent = String(i + 1).padStart(2, '0') + ' / ' + total;
      });
    });
  }

  /* ---------- Detail: balení a cena ---------- */
  var pdp = $('[data-pdp]');
  if (pdp) {
    var nowEl = $('[data-price-now]', pdp);
    var pctEl = $('[data-price-pct]', pdp);
    var rrpRow = $('[data-price-rrp-row]', pdp);
    var rrpEl = $('[data-price-rrp]', pdp);
    var savedEl = $('[data-price-saved]', pdp);
    var curEl = $('[data-opt-current]', pdp);
    var updatePrice = function () {
      var r = $('input[name="option"]:checked', pdp);
      if (!r || !r.hasAttribute('data-price')) return;
      var price = +r.getAttribute('data-price');
      var rrp = +r.getAttribute('data-rrp') || 0;
      nowEl.textContent = fmt(price);
      if (curEl) curEl.textContent = r.getAttribute('data-label');
      var has = rrp > price;
      if (pctEl) { pctEl.hidden = !has; pctEl.textContent = '−' + Math.floor(100 * (rrp - price) / (rrp || 1)) + NB + '%'; }
      if (rrpRow) rrpRow.hidden = !has;
      if (rrpEl) rrpEl.textContent = fmt(rrp);
      if (savedEl) savedEl.textContent = 'Ušetříte ' + fmt(Math.max(0, rrp - price));
      if (history.replaceState) {
        var u = new URL(location.href); u.searchParams.set('baleni', r.value); history.replaceState(null, '', u);
      }
    };
    $$('input[name="option"]', pdp).forEach(function (r) { r.addEventListener('change', updatePrice); });
    var qty = $('#qty', pdp);
    $$('[data-qty-step]', pdp).forEach(function (b) {
      b.addEventListener('click', function () {
        var max = +qty.getAttribute('max') || 20;
        qty.value = Math.max(1, Math.min(max, (parseInt(qty.value, 10) || 1) + (+b.getAttribute('data-qty-step'))));
      });
    });
  }

  /* ---------- Výpis: filtry a řazení ---------- */
  var filterForm = $('[data-filter-form]');
  if (filterForm) {
    var drawerMode = function () { return window.matchMedia('(max-width: 1024px)').matches; };
    var submitFilters = function () {
      // prázdná pole do URL nepatří
      $$('input', filterForm).forEach(function (i) { if ((i.type === 'text' || i.type === 'radio') && i.value === '' ) i.disabled = (i.type === 'text') || i.checked; });
      filterForm.submit();
    };
    filterForm.addEventListener('change', function (e) {
      if (drawerMode()) return;
      if (e.target.matches('input[type=checkbox], input[type=radio], input[type=text]')) submitFilters();
    });
    filterForm.addEventListener('submit', function (e) { e.preventDefault(); submitFilters(); });
    $$('[data-autosubmit]').forEach(function (s) { s.addEventListener('change', submitFilters); });
    // po návratu zpět (bfcache) pole znovu povolit
    window.addEventListener('pageshow', function () { $$('input', filterForm).forEach(function (i) { i.disabled = false; }); });
  }

  /* ---------- Výpis: načíst další ---------- */
  var more = $('[data-load-more]');
  if (more) {
    var grid = $('#shopGrid');
    var pager = $('.pager');
    if (pager) pager.classList.add('has-loadmore');
    more.addEventListener('click', function (e) {
      e.preventDefault();
      more.classList.add('is-busy');
      fetch(more.href, { headers: { 'Accept': 'text/html' }, credentials: 'same-origin' })
        .then(function (r) { return r.text(); })
        .then(function (html) {
          var page = new DOMParser().parseFromString(html, 'text/html');
          var cards = $$('#shopGrid > .card', page).map(function (c) { return grid.appendChild(doc.importNode(c, true)); });
          var shown = $('[data-shown]', page);
          var bar = $('[data-progress]', page);
          var nextMore = $('[data-load-more]', page);
          var nextPager = $('.pager', page);
          if (shown) $('[data-shown]').textContent = shown.textContent;
          if (bar) $('[data-progress]').style.width = bar.style.width;
          if (pager && nextPager) { pager.innerHTML = nextPager.innerHTML; }
          if (nextMore) { more.href = nextMore.href; more.textContent = nextMore.textContent; more.classList.remove('is-busy'); }
          else { more.remove(); }
          var firstNew = cards.length ? $('.card-title a', cards[0]) : null;
          if (firstNew) firstNew.focus({ preventScroll: true });
        })
        .catch(function () { location.href = more.href; });
    });
  }

  /* ---------- Pokladna: doprava a podmíněná pole ---------- */
  var checkout = $('[data-checkout]');
  if (checkout) {
    var radios = $$('input[name="delivery"]', checkout);
    var shipEl = $('[data-ship-value]', checkout);
    var totalEl = $('[data-total-value]', checkout);
    var rows = $('[data-items-total]', checkout);
    var itemsTotal = rows ? +rows.getAttribute('data-items-total') : 0;
    var applyDelivery = function () {
      var r = $('input[name="delivery"]:checked', checkout);
      var type = r ? r.getAttribute('data-type') : null;
      $$('[data-for-type]', checkout).forEach(function (g) {
        var on = g.getAttribute('data-for-type') === type;
        g.classList.toggle('is-on', on);
        $$('input, textarea', g).forEach(function (i) {
          if (i.name === 'pickup_point' || i.name === 'street' || i.name === 'city' || i.name === 'zip') i.required = on;
        });
      });
      if (r) {
        var price = +r.getAttribute('data-price') || 0;
        if (shipEl) shipEl.textContent = price ? fmt(price) : 'zdarma';
        if (totalEl) totalEl.textContent = fmt(itemsTotal + price);
      }
    };
    radios.forEach(function (r) { r.addEventListener('change', applyDelivery); });
    applyDelivery();
    var errorBox = $('[data-error-box]');
    if (errorBox) errorBox.focus();
    checkout.addEventListener('submit', function () {
      var b = $('button[type=submit]', checkout);
      if (b) { b.classList.add('is-busy'); b.textContent = 'Odesíláme…'; }
    });
  }

  /* Hladký posun na kotvy na stejné stránce (#pribeh) */
  if (!reduced) {
    doc.addEventListener('click', function (e) {
      var a = e.target.closest && e.target.closest('a[href^="#"]');
      if (!a || a.getAttribute('href').length < 2) return;
      var t = doc.getElementById(a.getAttribute('href').slice(1));
      if (!t || a.classList.contains('skip-link')) return;
      e.preventDefault();
      t.scrollIntoView({ behavior: 'smooth', block: 'start' });
      history.pushState && history.pushState(null, '', a.getAttribute('href'));
    });
  }
})();
