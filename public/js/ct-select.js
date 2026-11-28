/* CargoTrace: dropdown yang bisa diketik (searchable select), tanpa library/CDN.
   - Otomatis untuk <select class="input"> / <select class="sh-sel"> yang punya >= 6 pilihan,
     atau yang diberi atribut data-searchable. Pakai data-no-search untuk mengecualikan.
   - <select> asli tetap ada (disembunyikan) sehingga form, validasi "required", dan event "change" tetap jalan. */
(function () {
  'use strict';
  var MIN_OPTIONS = 6;

  var css = '' +
    '.ctsel{position:relative;width:100%}' +
    '.ctsel>select{position:absolute!important;left:0;bottom:0;width:100%!important;height:1px!important;min-height:0!important;padding:0!important;margin:0!important;border:0!important;opacity:0;pointer-events:none}' +
    '.ctsel>input.ctsel-input{cursor:text;text-overflow:ellipsis}' +
    '.ctsel.is-input:after{content:"";position:absolute;right:.9rem;top:50%;width:.45rem;height:.45rem;margin-top:-.35rem;border-right:2px solid #6b7280;border-bottom:2px solid #6b7280;transform:rotate(45deg);pointer-events:none}' +
    '.ctsel-list{position:absolute;left:0;right:0;top:calc(100% + 4px);z-index:80;max-height:15rem;overflow-y:auto;background:#fff;color:#1f2937;border:1px solid #d1d5db;border-radius:.75rem;box-shadow:0 12px 28px -10px rgba(0,0,0,.25);padding:.25rem;margin:0;list-style:none;font-size:.875rem}' +
    '.ctsel-list[hidden]{display:none}' +
    '.ctsel-item{padding:.5rem .7rem;border-radius:.5rem;cursor:pointer}' +
    '.ctsel-item.is-active{background:#eef6e0}' +
    '.ctsel-item.is-selected{font-weight:600}' +
    '.ctsel-item.is-muted{color:#9ca3af}' +
    '.ctsel-empty{padding:.6rem .7rem;color:#6b7280;cursor:default}';
  var st = document.createElement('style');
  st.textContent = css;
  document.head.appendChild(st);

  function enhance(sel) {
    if (sel.dataset.ctSel) { return; }
    if (sel.multiple || sel.disabled) { return; }
    if (sel.hasAttribute('data-no-search') || sel.hasAttribute('data-lang-select') || sel.id === 'country-sel') { return; }
    if (!sel.classList.contains('input') && !sel.classList.contains('sh-sel')) { return; }
    if (sel.options.length < MIN_OPTIONS && !sel.hasAttribute('data-searchable')) { return; }
    sel.dataset.ctSel = '1';

    var wrap = document.createElement('div');
    wrap.className = 'ctsel' + (sel.classList.contains('input') ? ' is-input' : '');
    sel.parentNode.insertBefore(wrap, sel);
    wrap.appendChild(sel);

    var input = document.createElement('input');
    input.type = 'text';
    input.className = sel.className + ' ctsel-input';
    input.setAttribute('autocomplete', 'off');
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-expanded', 'false');
    input.setAttribute('data-no-i18n', '');
    if (sel.getAttribute('aria-label')) { input.setAttribute('aria-label', sel.getAttribute('aria-label')); }
    wrap.insertBefore(input, sel);
    input.classList.remove('w-auto');
    sel.tabIndex = -1;

    var list = document.createElement('ul');
    list.className = 'ctsel-list';
    list.setAttribute('role', 'listbox');
    list.hidden = true;
    wrap.appendChild(list);

    var items = [];   // [{value, text, el}]
    var active = -1;

    function placeholderText() {
      var first = sel.options[0];
      return first && first.value === '' ? first.textContent.trim() : '';
    }
    function selectedText() {
      var o = sel.options[sel.selectedIndex];
      return o && o.value !== '' ? o.textContent.trim() : '';
    }
    function syncInput() {
      input.value = selectedText();
      input.placeholder = placeholderText();
    }
    function isOpen() { return !list.hidden; }

    function render(filter) {
      var q = (filter || '').trim().toLowerCase();
      list.innerHTML = '';
      items = [];
      Array.prototype.forEach.call(sel.options, function (o) {
        var text = o.textContent.trim();
        var blank = o.value === '';
        if (blank && sel.required) { return; }              // field wajib: tidak perlu pilihan kosong
        if (q && (blank || text.toLowerCase().indexOf(q) === -1)) { return; }
        var li = document.createElement('li');
        li.className = 'ctsel-item' + (blank ? ' is-muted' : '') + (o.value === sel.value && !blank ? ' is-selected' : '');
        li.setAttribute('role', 'option');
        li.setAttribute('data-no-i18n', '');
        li.textContent = text;
        li.addEventListener('mousedown', function (e) { e.preventDefault(); choose(o.value); });
        list.appendChild(li);
        items.push({ value: o.value, el: li });
      });
      if (!items.length) {
        var none = document.createElement('li');
        none.className = 'ctsel-empty';
        none.setAttribute('data-no-i18n', '');
        none.textContent = q ? 'Tidak ada hasil untuk "' + filter.trim() + '"' : 'Belum ada data pilihan';
        list.appendChild(none);
      }
      var idx = -1;
      items.forEach(function (it, i) { if (it.value === sel.value && it.value !== '') { idx = i; } });
      setActive(idx >= 0 ? idx : (q && items.length ? 0 : -1), true);
    }

    function setActive(i, scroll) {
      if (active >= 0 && items[active]) { items[active].el.classList.remove('is-active'); }
      active = i;
      if (i >= 0 && items[i]) {
        items[i].el.classList.add('is-active');
        if (scroll) { items[i].el.scrollIntoView({ block: 'nearest' }); }
      }
    }

    function open(showAll) {
      render(showAll ? '' : input.value);
      list.hidden = false;
      input.setAttribute('aria-expanded', 'true');
    }
    function close() {
      list.hidden = true;
      input.setAttribute('aria-expanded', 'false');
    }
    function choose(value) {
      var changed = sel.value !== value;
      sel.value = value;
      syncInput();
      close();
      if (changed) {
        sel.dispatchEvent(new Event('input', { bubbles: true }));
        sel.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }

    input.addEventListener('focus', function () { input.select(); open(true); });
    input.addEventListener('click', function () { if (!isOpen()) { open(true); } });
    input.addEventListener('input', function () { open(false); });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (!isOpen()) { open(true); return; }
        if (items.length) { setActive(Math.min(active + 1, items.length - 1), true); }
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (items.length) { setActive(Math.max(active - 1, 0), true); }
      } else if (e.key === 'Enter') {
        if (isOpen()) {
          e.preventDefault();                                   // Enter memilih, bukan submit form
          if (active >= 0 && items[active]) { choose(items[active].value); }
          else if (items.length === 1) { choose(items[0].value); }
        }
      } else if (e.key === 'Escape') {
        if (isOpen()) { e.preventDefault(); close(); syncInput(); }
      }
    });
    input.addEventListener('blur', function () {
      var typed = input.value.trim().toLowerCase();
      if (typed === '') {
        if (!sel.required && sel.value !== '') { choose(''); }   // dikosongkan = pilihan "semua/kosong"
      } else {
        var hit = null;
        Array.prototype.forEach.call(sel.options, function (o) {
          if (o.value !== '' && o.textContent.trim().toLowerCase() === typed) { hit = o; }
        });
        if (hit && hit.value !== sel.value) { choose(hit.value); }
      }
      close();
      syncInput();
    });

    sel.addEventListener('invalid', function () { input.focus(); });
    sel.addEventListener('change', syncInput);                  // perubahan dari script lain
    if (sel.form) { sel.form.addEventListener('reset', function () { setTimeout(syncInput, 0); }); }
    window.addEventListener('pageshow', syncInput);

    // Terjemahan bahasa mengubah teks <option>; ikuti agar teks di kolom ikut berganti.
    new MutationObserver(function () { syncInput(); if (isOpen()) { render(input.value); } })
      .observe(sel, { childList: true, subtree: true, characterData: true });

    syncInput();
  }

  function init() { document.querySelectorAll('select').forEach(enhance); }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
  window.CT = window.CT || {};
  window.CT.enhanceSelects = init;
})();
