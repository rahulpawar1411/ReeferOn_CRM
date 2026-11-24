// ReeferON CRM – shared loader + lazy list loading
(function () {
  var loaderEl = null;
  var lazyBusy = false;

  function loader() {
    if (!loaderEl) {
      loaderEl = document.getElementById('app-loader');
    }
    return loaderEl;
  }

  function showLoader(message) {
    var el = loader();
    if (!el) return;
    var text = el.querySelector('.app-loader-text');
    if (text) {
      text.textContent = message || 'Loading…';
    }
    el.hidden = false;
    document.body.classList.add('is-loading');
  }

  function hideLoader() {
    var el = loader();
    if (!el) return;
    el.hidden = true;
    document.body.classList.remove('is-loading');
  }

  window.CRM = window.CRM || {};
  window.CRM.showLoader = showLoader;
  window.CRM.hideLoader = hideLoader;

  function bindConfirm() {
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        var msg = el.getAttribute('data-confirm') || 'Are you sure?';
        if (!window.confirm(msg)) {
          e.preventDefault();
        }
      });
    });
  }

  function bindNavLoader() {
    document.querySelectorAll('.sidebar nav a, a.brand').forEach(function (a) {
      a.addEventListener('click', function (e) {
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || a.target === '_blank') {
          return;
        }
        showLoader('Loading…');
      });
    });

    document.querySelectorAll('form').forEach(function (form) {
      form.addEventListener('submit', function () {
        // Skip if already handled as lazy; still show loader for normal submits
        if (form.classList.contains('js-no-loader')) return;
        showLoader(form.getAttribute('data-loading-text') || 'Loading…');
      });
    });
  }

  function updatePager(pager, data) {
    pager.dataset.page = String(data.page);
    pager.dataset.pages = String(data.pages);
    pager.dataset.total = String(data.total);
    var fromEl = pager.querySelector('.js-lazy-from');
    var toEl = pager.querySelector('.js-lazy-to');
    var totalEl = pager.querySelector('.js-lazy-total');
    if (fromEl) fromEl.textContent = '1';
    if (toEl) toEl.textContent = String(data.to);
    if (totalEl) totalEl.textContent = String(data.total);

    if (!data.has_more) {
      pager.dataset.nextUrl = '';
      var btn = pager.querySelector('.js-lazy-more');
      var sentinel = pager.querySelector('.js-lazy-sentinel');
      var links = pager.querySelector('.pagination-links');
      if (btn) btn.remove();
      if (sentinel) sentinel.remove();
      if (links) links.remove();
      var info = pager.querySelector('.js-lazy-info');
      if (info) {
        info.textContent =
          'Showing 1–' + data.to + ' of ' + data.total + ' (' + data.per_page + ' per page)';
      }
    } else {
      pager.dataset.nextUrl = data.next_url || '';
    }
  }

  function loadMore(table, pager, manual) {
    if (lazyBusy) return;
    var nextUrl = pager.getAttribute('data-next-url');
    if (!nextUrl) return;

    lazyBusy = true;
    table.classList.add('is-lazy-loading');
    showLoader(manual ? 'Loading more…' : 'Loading…');

    fetch(nextUrl, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
      .then(function (res) {
        if (!res.ok) throw new Error('Load failed');
        return res.json();
      })
      .then(function (data) {
        if (!data || !data.ok) throw new Error((data && data.error) || 'Load failed');
        var tbody = table.tBodies[0] || table.querySelector('tbody');
        if (tbody && data.rows_html) {
          tbody.insertAdjacentHTML('beforeend', data.rows_html);
        }
        updatePager(pager, data);
      })
      .catch(function () {
        // Keep UI usable; user can click Load more again
        alert('Could not load more rows. Please try again.');
      })
      .finally(function () {
        lazyBusy = false;
        table.classList.remove('is-lazy-loading');
        hideLoader();
      });
  }

  function bindLazyTables() {
    document.querySelectorAll('table.data[data-lazy="1"]').forEach(function (table) {
      var wrap = table.closest('.card') || table.parentElement;
      if (!wrap) return;
      var pager = wrap.querySelector('.js-lazy-pager');
      if (!pager) return;

      var btn = pager.querySelector('.js-lazy-more');
      if (btn) {
        btn.addEventListener('click', function () {
          loadMore(table, pager, true);
        });
      }

      var sentinel = pager.querySelector('.js-lazy-sentinel');
      if (sentinel && 'IntersectionObserver' in window) {
        var io = new IntersectionObserver(
          function (entries) {
            entries.forEach(function (entry) {
              if (entry.isIntersecting && pager.getAttribute('data-next-url')) {
                loadMore(table, pager, false);
              }
            });
          },
          { root: null, rootMargin: '120px', threshold: 0 }
        );
        io.observe(sentinel);
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    bindConfirm();
    bindNavLoader();
    bindLazyTables();
  });

  window.addEventListener('pageshow', function (e) {
    if (e.persisted) hideLoader();
  });
})();
