/**
 * StockWise global JS: webcam barcode scanning + Chart.js renderers.
 */
(function () {
  'use strict';

  /* ---------- Webcam barcode scanner (html5-qrcode) ---------- */
  function initScanner() {
    var scanBtn = document.getElementById('scanBtn');
    if (!scanBtn || typeof Html5Qrcode === 'undefined') {
      if (scanBtn) scanBtn.textContent = 'Scanner library not loaded';
      return;
    }
    var scanner = null;
    var scanning = false;

    scanBtn.addEventListener('click', function () {
      var box = document.getElementById('qrScanner');
      var result = document.getElementById('scanResult');
      if (scanning) {
        scanner.stop().then(function () {
          scanBtn.textContent = 'Start Camera Scan';
          box.classList.add('d-none');
          scanning = false;
        });
        return;
      }

      if (!('mediaDevices' in navigator)) {
        alert('Webcam not available in this browser.');
        return;
      }

      scanner = new Html5Qrcode('qrScanner');
      box.classList.remove('d-none');
      scanBtn.textContent = 'Stop Camera';
      scanning = true;

      scanner.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: { width: 220, height: 120 } },
              function (decodedText) {
          // Match against embedded item list
          var items = window.STOCKWISE_ITEMS || [];
          var select = document.getElementById('itemSelect');
          var found = items.find(function (i) { return i.code === decodedText || decodedText.indexOf(i.code) !== -1; });
          if (found) {
            select.value = String(found.id);
            result.textContent = 'Scanned: ' + found.code + ' - ' + found.name;
          } else {
            result.textContent = 'Scanned unknown code: ' + decodedText;
            result.className = 'alert alert-warning mt-2 d-block mb-0';
          }
          result.classList.remove('d-none');
          result.classList.add('d-block');
          // Stop after a successful read
          scanner.stop().then(function () {
            scanBtn.textContent = 'Start Camera Scan';
            box.classList.add('d-none');
            scanning = false;
          }).catch(function () {});
        },
        function (err) { /* decode error - ignore (keeps scanning) */ }
      ).catch(function (err) {
        alert('Camera error: ' + err);
        scanning = false;
        scanBtn.textContent = 'Start Camera Scan';
      });
    });
  }

  /* ---------- Chart.js doughnut/bar renderers ---------- */
  function renderDoughnut(canvas, labels, values) {
    if (!canvas || typeof Chart === 'undefined') return;
    new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: labels,
              datasets: [{ data: values, backgroundColor: ['#0d6efd','#198754','#ffc107','#dc3545','#0dcaf0','#6610f2','#6f42c1','#d73a49','#17a2b8','#fd7e14'] }]
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });
  }

  function initCharts() {
    var vp = document.getElementById('valueByCatChart');
    if (vp) {
      var d1 = JSON.parse(vp.getAttribute('data-values') || '[]');
      renderDoughnut(vp, d1.map(function (x) { return x.label; }), d1.map(function (x) { return parseFloat(x.value) || 0; }));
    }
    var rc = document.getElementById('reportCatChart');
    if (rc) {
      var d2 = JSON.parse(rc.getAttribute('data-values') || '[]');
      renderDoughnut(rc, d2.map(function (x) { return x.label; }), d2.map(function (x) { return parseFloat(x.value) || 0; }));
    }
  }

    /* ---------- Sidebar hide toggle (non-persistent, safe) ---------- */
  function initSidebarToggle() {
    var btn = document.getElementById('swSidebarToggle');
    if (!btn) return;
    var body = document.body;
    var icon = btn.querySelector('i');
    function apply(hidden) {
      if (hidden) {
        body.classList.add('sidebar-hidden');
        if (icon) icon.className = 'bi bi-layout-sidebar-reverse'; // collapsed
      } else {
        body.classList.remove('sidebar-hidden');
        if (icon) icon.className = 'bi bi-layout-sidebar'; // expanded
      }
    }
    // Always start with the sidebar VISIBLE so every menu item (e.g. Add Item) is
    // reachable - never restore a hidden state that could hide the navigation.
    apply(false);
    btn.addEventListener('click', function () {
      var hidden = !body.classList.contains('sidebar-hidden');
      apply(hidden);
      // If the menu search had hidden links, clear it so everything reappears.
      var search = document.getElementById('swSidebarSearch');
      if (search) { search.value = ''; search.dispatchEvent(new Event('input')); }
    });
  }

  /* ---------- Sidebar menu search (filters nav links) ---------- */
  function initSidebarSearch() {
    var input = document.getElementById('swSidebarSearch');
    if (!input) return;
    input.addEventListener('input', function () {
      var q = input.value.toLowerCase();
      var links = input.closest('.sidebar').querySelectorAll('.nav-link');
      links.forEach(function (a) {
        a.style.display = a.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
      });
    });
  }

  /* ---------- Generic table filters (data-filter-panel + data-filter-key) ---------- */
  function initTableFilters() {
    document.querySelectorAll('[data-filter-panel]').forEach(function (panel) {
      var tableSel = panel.getAttribute('data-filter-panel');
      var table = document.querySelector(tableSel);
      if (!table) return;
      var controls = Array.prototype.slice.call(panel.querySelectorAll('[data-filter-key]'));
      var resetBtn = panel.querySelector('[data-filter-reset]');
      function apply() {
        var rows = table.querySelectorAll('tbody tr');
        rows.forEach(function (row) {
          var show = true;
          controls.forEach(function (c) {
            var key = c.getAttribute('data-filter-key');
            if (key === 'date-from' || key === 'date-to') return; // handled below
            var val = (c.value || '').trim();
            if (val === '') return;
            var rv = row.getAttribute('data-' + key) || '';
            var mode = c.getAttribute('data-filter-mode') || 'eq';
            if (mode === 'contains') { if (rv.toLowerCase().indexOf(val.toLowerCase()) === -1) show = false; }
            else { if (rv !== val) show = false; }
          });
          if (show) {
            var d = (row.getAttribute('data-date') || '').slice(0, 10);
            var fr = (panel.querySelector('[data-filter-key="date-from"]') || { value: '' }).value;
            var to = (panel.querySelector('[data-filter-key="date-to"]') || { value: '' }).value;
            if (fr && d < fr) show = false;
            if (to && d > to) show = false;
          }
          row.style.display = show ? '' : 'none';
        });
      }
      controls.forEach(function (c) {
        c.addEventListener('change', apply);
        c.addEventListener('input', apply);
      });
      if (resetBtn) {
        resetBtn.addEventListener('click', function () {
          controls.forEach(function (c) { c.value = ''; });
          apply();
        });
      }
    });
  }

  /* ---------- Auth login/register swap animation ---------- */
  function initAuthSwap() {
    var split = document.getElementById('authSplit');
    if (!split) return;
    var toggling = false;
    split.querySelectorAll('.auth-switch').forEach(function (a) {
      a.addEventListener('click', function (e) {
        e.preventDefault();
        if (toggling) return;
        toggling = true;
        var to = a.getAttribute('data-switch');
        var goRegister = to === 'register';
        split.classList.add('is-swapping');
        setTimeout(function () {
          split.classList.toggle('register-mode', goRegister);
          split.classList.remove('is-swapping');
          toggling = false;
          // move focus into the now-active form
          var active = split.querySelector((goRegister ? '.auth-right .auth-register-panel' : '.auth-left .auth-login-panel') + ' input');
          if (active) { try { active.focus(); } catch (err) {} }
        }, 340);
      });
    });
  }

    /* ---------- Users: confirm status toggle via modal ---------- */
  function initUserToggleModal() {
    var btns = document.querySelectorAll('.toggle-user');
    if (!btns.length || !window.bootstrap) return;
    var modalEl = document.getElementById('userToggleModal');
    var confirmBtn = document.getElementById('toggleUserConfirm');
    if (!modalEl || !confirmBtn) return;
    var modal = new bootstrap.Modal(modalEl);
    var current = { id: null, name: '' };
    btns.forEach(function (b) {
      b.addEventListener('click', function () {
        current.id = b.dataset.uid;
        current.name = b.dataset.name;
        document.getElementById('toggleUserName').textContent = current.name;
        var active = b.dataset.active === '1';
        var action = document.getElementById('toggleUserAction');
        action.textContent = active
          ? 'Deactivating will prevent this user from logging in.'
          : 'Activating will restore login access for this user.';
        confirmBtn.classList.remove('btn-success', 'btn-warning');
        confirmBtn.classList.add(active ? 'btn-warning' : 'btn-success');
        confirmBtn.textContent = active ? 'Deactivate' : 'Activate';
        modal.show();
      });
    });
    confirmBtn.addEventListener('click', function () {
      var f = document.getElementById('toggleForm-' + current.id);
      if (f) { f.requestSubmit(); }
      modal.hide();
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initScanner();
    initCharts();
    initTableSearch();
    initTableSort();
    initClock();
    initSidebarToggle();
    initSidebarSearch();
    initTableFilters();
    initAuthSwap();
    initUserToggleModal();
  });

  /* ---------- Live clock (topbar timezone display) ---------- */
  function initClock() {
    var el = document.getElementById('appClock');
    if (!el) return;
    var serverLoaded = parseInt(el.getAttribute('data-server') || '0', 10);
    // Offset between browser clock and server clock; tick the server time forward.
    var offset = (serverLoaded * 1000) - Date.now();
    function fmt(n) { return (n < 10 ? '0' : '') + n; }
    function tick() {
      var d = new Date(Date.now() + offset);
      var days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
      var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      var h = d.getHours();
      var ampm = h < 12 ? 'AM' : 'PM';
      var h12 = h % 12 === 0 ? 12 : h % 12;
      el.textContent = days[d.getDay()] + ', ' + months[d.getMonth()] + ' ' + d.getDate() + ', ' +
        d.getFullYear() + ' ' + h12 + ':' + fmt(d.getMinutes()) + ' ' + ampm;
    }
    tick();
    setInterval(tick, 30000);
  }

  /* ---------- Generic table search (data-table-search -> table id) ---------- */
  function initTableSearch() {
    var inputs = document.querySelectorAll('[data-table-search]');
    inputs.forEach(function (input) {
      var table = document.getElementById(input.getAttribute('data-table-search'));
      if (!table) return;
      input.addEventListener('input', function () {
        var q = input.value.toLowerCase();
        var rows = table.querySelectorAll('tbody tr');
        rows.forEach(function (row) {
          row.style.display = row.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
        });
      });
    });
  }

  /* ---------- Sortable table headers (th[data-sort]) ---------- */
  function initTableSort() {
    var heads = document.querySelectorAll('th[data-sort]');
    heads.forEach(function (th) {
      th.addEventListener('click', function () {
        var table = th.closest('table');
        if (!table) return;
        var index = Array.prototype.indexOf.call(th.parentNode.children, th);
        var tbody = table.tBodies[0];
        if (!tbody) return;

        // Toggle direction; clear any other active sort on this table.
        var col = th.getAttribute('data-sort');
        var all = th.parentNode.querySelectorAll('th[data-sort]');
        all.forEach(function (h) { h.classList.remove('sort-asc', 'sort-desc'); });
        var isAsc = th.classList.contains('sort-asc');
        th.classList.add(isAsc ? 'sort-desc' : 'sort-asc');

        var rows = Array.prototype.slice.call(tbody.rows);
        rows.sort(function (a, b) {
          var av = a.cells[index] ? a.cells[index].textContent.trim() : '';
          var bv = b.cells[index] ? b.cells[index].textContent.trim() : '';
          // Currency / numbers: "?1,234.50" -> 1234.50
          var num = function (s) {
            var n = parseFloat(s.replace(/[^0-9.\-]/g, ''));
            return isNaN(n) ? null : n;
          };
          var an = num(av), bn = num(bv);
          var cmp;
          if (an !== null && bn !== null) {
            cmp = an - bn;
          } else {
            cmp = av.localeCompare(bv, undefined, { sensitivity: 'base' });
          }
          return isAsc ? -cmp : cmp;
        });
        rows.forEach(function (r) { tbody.appendChild(r); });
      });
    });
  }
})();
