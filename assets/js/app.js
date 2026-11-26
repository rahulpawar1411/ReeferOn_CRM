/**
 * ReeferON CRM – shared front-end helpers
 *
 * Loaded on every page (see includes/footer.php).
 * Handles:
 * - Full-page loading overlay
 * - India +91 phone inputs (10 digits only)
 * - Right-side Filters drawer
 * - Dashboard Global Search live suggestions
 * - Inactive / Active confirm popup (type the confirm word)
 * - Top-right success toasts
 *
 * List pages use server-side Prev / Next pagination (PHP).
 * Optional data-lazy tables can still append rows via AJAX.
 */
(function () {
  var loaderEl = null;
  var lazyBusy = false;

  // --- Loading overlay ---
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

  // Ask "Are you sure?" when a link/button has data-confirm="..."
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

  // Show loader when user clicks a sidebar link or submits a normal form
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

  // --- Optional lazy tables (data-lazy="1"); most lists use Prev / Next ---
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
        // Keep UI usable so the user can retry
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

  // --- Phone fields: force exactly 10 digits (ignore +91 / leading 0) ---
  function bindPhoneInputs() {
    document.querySelectorAll('input.js-phone-10').forEach(function (input) {
      input.setAttribute('maxlength', '10');
      input.setAttribute('inputmode', 'numeric');
      input.addEventListener('input', function () {
        var digits = (input.value || '').replace(/\D+/g, '').slice(0, 10);
        if (input.value !== digits) {
          input.value = digits;
        }
      });
      input.addEventListener('paste', function (e) {
        e.preventDefault();
        var text = (e.clipboardData || window.clipboardData).getData('text') || '';
        var digits = text.replace(/\D+/g, '');
        if (digits.indexOf('91') === 0 && digits.length >= 12) {
          digits = digits.slice(2);
        }
        if (digits.charAt(0) === '0' && digits.length === 11) {
          digits = digits.slice(1);
        }
        input.value = digits.slice(0, 10);
      });
      input.addEventListener('keypress', function (e) {
        if (e.ctrlKey || e.metaKey || e.altKey || e.key.length !== 1) return;
        if (!/[0-9]/.test(e.key)) {
          e.preventDefault();
        }
      });
    });
  }

  // --- Filters drawer (slides in from the right) ---
  function openFilterFlip(id) {
    var panel = document.getElementById(id);
    if (!panel) return;
    document.querySelectorAll('.filter-flip.open').forEach(function (p) {
      p.classList.remove('open');
      p.setAttribute('aria-hidden', 'true');
    });
    document.querySelectorAll('.filter-flip-backdrop').forEach(function (b) {
      b.hidden = true;
    });
    panel.classList.add('open');
    panel.setAttribute('aria-hidden', 'false');
    document.querySelectorAll('.filter-flip-backdrop[data-filter-target="' + id + '"]').forEach(function (b) {
      b.hidden = false;
    });
    document.body.classList.add('filter-flip-open');
  }

  function closeFilterFlip(id) {
    var panel = id ? document.getElementById(id) : null;
    if (panel) {
      panel.classList.remove('open');
      panel.setAttribute('aria-hidden', 'true');
      document.querySelectorAll('.filter-flip-backdrop[data-filter-target="' + id + '"]').forEach(function (b) {
        b.hidden = true;
      });
    } else {
      document.querySelectorAll('.filter-flip.open').forEach(function (p) {
        p.classList.remove('open');
        p.setAttribute('aria-hidden', 'true');
      });
      document.querySelectorAll('.filter-flip-backdrop').forEach(function (b) {
        b.hidden = true;
      });
    }
    if (!document.querySelector('.filter-flip.open')) {
      document.body.classList.remove('filter-flip-open');
    }
  }

  function bindFilterFlip() {
    document.querySelectorAll('.js-filter-open').forEach(function (btn) {
      btn.addEventListener('click', function () {
        openFilterFlip(btn.getAttribute('data-filter-target'));
      });
    });
    document.querySelectorAll('.js-filter-close, .js-filter-backdrop').forEach(function (el) {
      el.addEventListener('click', function () {
        closeFilterFlip(el.getAttribute('data-filter-target'));
      });
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeFilterFlip();
    });
  }

  // Excel: require From + To dates in the export URL (set via Filters → Apply)
  function bindExportExcel() {
    document.querySelectorAll('.js-export-excel').forEach(function (a) {
      a.addEventListener('click', function (e) {
        var fromParam = a.getAttribute('data-from-param') || 'date_from';
        var toParam = a.getAttribute('data-to-param') || 'date_to';
        var href;
        try {
          href = new URL(a.href, window.location.origin);
        } catch (err) {
          return;
        }
        var from = (href.searchParams.get(fromParam) || '').trim();
        var to = (href.searchParams.get(toParam) || '').trim();
        if (!from || !to) {
          e.preventDefault();
          alert('Select From and To dates in Filters, click Apply Filters, then click Excel (CSV).');
          var openBtn = document.querySelector('.js-filter-open');
          if (openBtn) {
            openFilterFlip(openBtn.getAttribute('data-filter-target'));
          }
        }
      });
    });
  }

  // Live search beside Filters button — typing updates ?q= and reloads list
  function bindLiveSearch() {
    var FOCUS_KEY = 'crmLiveSearchFocus';

    // After reload: put cursor back in the search box
    try {
      var saved = sessionStorage.getItem(FOCUS_KEY);
      if (saved) {
        sessionStorage.removeItem(FOCUS_KEY);
        var info = JSON.parse(saved);
        var restore = document.querySelector(
          '.js-live-search[data-param="' + (info.param || 'q') + '"]'
        );
        if (restore) {
          // Keep what user typed if server echoed a trimmed value
          if (typeof info.value === 'string') {
            restore.value = info.value;
          }
          restore.focus();
          var pos = typeof info.caret === 'number' ? info.caret : restore.value.length;
          try {
            restore.setSelectionRange(pos, pos);
          } catch (err) {
            // some input types may not support selection
          }
        }
      }
    } catch (e) {
      // ignore storage errors
    }

    document.querySelectorAll('.js-live-search').forEach(function (input) {
      var timer = null;
      var param = input.getAttribute('data-param') || 'q';
      var lastSent = (input.value || '').trim();

      function applySearch() {
        var raw = input.value || '';
        var val = raw.trim();
        // Skip reload if search text did not change
        if (val === lastSent) {
          return;
        }
        lastSent = val;

        var url = new URL(window.location.href);
        if (val === '') {
          url.searchParams.delete(param);
        } else {
          url.searchParams.set(param, val);
        }
        url.searchParams.delete('page'); // reset to first page on new search

        try {
          sessionStorage.setItem(
            FOCUS_KEY,
            JSON.stringify({
              param: param,
              value: raw,
              caret: input.selectionStart != null ? input.selectionStart : raw.length,
            })
          );
        } catch (err) {
          // ignore
        }

        showLoader('Searching…');
        window.location.href = url.toString();
      }

      input.addEventListener('input', function () {
        if (timer) clearTimeout(timer);
        timer = setTimeout(applySearch, 400);
      });

      input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          if (timer) clearTimeout(timer);
          applySearch();
        }
      });
    });
  }

  // --- Dashboard Global Search: suggestions while typing ---
  function escapeHtml(str) {
    return String(str == null ? '' : str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function bindGlobalSearchSuggest() {
    var wrap = document.querySelector('.global-search-wrap');
    if (!wrap) return;
    var input = wrap.querySelector('.global-search-input');
    var box = wrap.querySelector('.global-search-suggest');
    var url = wrap.getAttribute('data-suggest-url');
    if (!input || !box || !url) return;

    var timer = null;
    var seq = 0;
    var active = -1;
    var items = [];

    function setExpanded(open) {
      input.setAttribute('aria-expanded', open ? 'true' : 'false');
      box.hidden = !open;
    }

    function hide() {
      active = -1;
      items = [];
      box.innerHTML = '';
      setExpanded(false);
    }

    function markActive(idx) {
      active = idx;
      var nodes = box.querySelectorAll('.gs-item');
      nodes.forEach(function (n, i) {
        n.classList.toggle('is-active', i === active);
      });
    }

    function render(list, q, moreUrl) {
      items = list || [];
      active = -1;
      if (!q || q.length < 2) {
        hide();
        return;
      }
      if (!items.length) {
        box.innerHTML = '<div class="gs-empty">No matches for “' + escapeHtml(q) + '”</div>';
        setExpanded(true);
        return;
      }
      var html = items
        .map(function (it, i) {
          return (
            '<a class="gs-item" role="option" href="' +
            escapeHtml(it.url) +
            '" data-idx="' +
            i +
            '">' +
            '<div class="gs-item-top">' +
            '<span class="gs-type">' +
            escapeHtml(it.type) +
            '</span>' +
            '<span class="gs-label">' +
            escapeHtml(it.label) +
            '</span>' +
            '</div>' +
            (it.sub
              ? '<div class="gs-sub">' + escapeHtml(it.sub) + '</div>'
              : '') +
            '</a>'
          );
        })
        .join('');
      if (moreUrl) {
        html +=
          '<div class="gs-footer"><a href="' +
          escapeHtml(moreUrl) +
          '">See all results →</a></div>';
      }
      box.innerHTML = html;
      setExpanded(true);
    }

    function fetchSuggest(q) {
      var my = ++seq;
      fetch(url + '?q=' + encodeURIComponent(q), {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      })
        .then(function (res) {
          if (!res.ok) throw new Error('fail');
          return res.json();
        })
        .then(function (data) {
          if (my !== seq) return;
          if (!data || !data.ok) {
            hide();
            return;
          }
          render(data.items || [], data.q || q, data.more_url || '');
        })
        .catch(function () {
          if (my === seq) hide();
        });
    }

    input.addEventListener('input', function () {
      var q = (input.value || '').trim();
      if (timer) clearTimeout(timer);
      if (q.length < 2) {
        hide();
        return;
      }
      timer = setTimeout(function () {
        fetchSuggest(q);
      }, 180);
    });

    input.addEventListener('keydown', function (e) {
      if (box.hidden) return;
      var nodes = box.querySelectorAll('.gs-item');
      if (!nodes.length) {
        if (e.key === 'Escape') hide();
        return;
      }
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        markActive(active < nodes.length - 1 ? active + 1 : 0);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        markActive(active > 0 ? active - 1 : nodes.length - 1);
      } else if (e.key === 'Enter' && active >= 0 && nodes[active]) {
        e.preventDefault();
        showLoader('Opening…');
        window.location.href = nodes[active].getAttribute('href');
      } else if (e.key === 'Escape') {
        e.preventDefault();
        hide();
      }
    });

    box.addEventListener('mousedown', function (e) {
      var a = e.target.closest('.gs-item');
      if (a) {
        e.preventDefault();
        showLoader('Opening…');
        window.location.href = a.getAttribute('href');
      }
    });

    input.addEventListener('blur', function () {
      setTimeout(hide, 140);
    });

    input.addEventListener('focus', function () {
      var q = (input.value || '').trim();
      if (q.length >= 2) fetchSuggest(q);
    });
  }

  // --- Top-right toast (5s); Undo is acknowledgement only (no DB reverse) ---
  function toastTitles(action) {
    if (action === 'create') return 'Created';
    if (action === 'edit') return 'Updated';
    if (action === 'delete') return 'Inactive'; // flash action key stays "delete"
    if (action === 'activate') return 'Active';
    if (action === 'login') return 'Welcome';
    return 'Saved';
  }

  function showToast(opts) {
    var host = document.getElementById('toast-host');
    if (!host) return;

    var action = (opts && opts.action) || '';
    var message = (opts && opts.message) || '';
    var showUndo = action === 'create' || action === 'edit' || action === 'delete' || action === 'activate';

    var el = document.createElement('div');
    el.className = 'toast' + (action ? ' toast-action-' + action : '');
    el.innerHTML =
      '<div class="toast-body">' +
      '<div class="toast-icon">' + (action === 'delete' ? '!' : '✓') + '</div>' +
      '<div class="toast-text">' +
      '<p class="toast-title">' + escapeHtml(toastTitles(action)) + '</p>' +
      '<p class="toast-msg">' + escapeHtml(message) + '</p>' +
      '</div>' +
      '<div class="toast-actions">' +
      (showUndo ? '<button type="button" class="toast-undo" data-toast-undo>Undo</button>' : '') +
      '<button type="button" class="toast-close" data-toast-close aria-label="Close">&times;</button>' +
      '</div>' +
      '</div>' +
      '<div class="toast-timer"><span></span></div>';

    host.appendChild(el);

    var closed = false;
    function closeToast(ack) {
      if (closed) return;
      closed = true;
      if (ack) {
        var msg = el.querySelector('.toast-msg');
        var title = el.querySelector('.toast-title');
        if (title) title.textContent = 'Acknowledged';
        if (msg) msg.textContent = 'Noted. No further action.';
        var undo = el.querySelector('[data-toast-undo]');
        if (undo) undo.remove();
        setTimeout(function () {
          el.classList.add('is-leaving');
          setTimeout(function () {
            if (el.parentNode) el.parentNode.removeChild(el);
          }, 180);
        }, 700);
        return;
      }
      el.classList.add('is-leaving');
      setTimeout(function () {
        if (el.parentNode) el.parentNode.removeChild(el);
      }, 180);
    }

    var timer = setTimeout(function () {
      closeToast(false);
    }, 5000);

    el.querySelector('[data-toast-close]').addEventListener('click', function () {
      clearTimeout(timer);
      closeToast(false);
    });
    var undoBtn = el.querySelector('[data-toast-undo]');
    if (undoBtn) {
      undoBtn.addEventListener('click', function () {
        clearTimeout(timer);
        // Acknowledgement only — does not reverse create/edit/Inactive in DB
        closeToast(true);
      });
    }
  }

  function bindToasts() {
    var host = document.getElementById('toast-host');
    if (!host) return;
    var raw = host.getAttribute('data-flash');
    if (!raw || raw === 'null' || raw === '') return;
    try {
      var data = JSON.parse(raw);
      if (data && data.message) {
        showToast(data);
      }
    } catch (e) {
      // ignore bad payload
    }
    host.removeAttribute('data-flash');
  }

  window.CRM = window.CRM || {};
  window.CRM.showToast = showToast;

  // --- Inactive popup: must type confirm word (default "inactive") ---
  function bindDeleteConfirm() {
    var modal = document.querySelector('.js-delete-modal');
    if (!modal) return;

    document.body.classList.add('delete-modal-open');
    var confirmWord = (modal.getAttribute('data-confirm-word') || 'inactive').toLowerCase();

    function closeDeleteModal() {
      var url = modal.getAttribute('data-cancel-url') || '';
      document.body.classList.remove('delete-modal-open');
      if (url) {
        window.location.href = url;
      } else if (window.history.length > 1) {
        window.history.back();
      } else {
        modal.remove();
      }
    }

    // Close when clicking outside the white popup (on dark backdrop)
    modal.addEventListener('click', function (e) {
      if (e.target === modal) {
        closeDeleteModal();
      }
    });

    // Close icon (×) and Cancel
    modal.querySelectorAll('.js-delete-modal-close').forEach(function (el) {
      el.addEventListener('click', function (e) {
        // Let real Cancel links navigate normally; buttons / × use JS close
        if (el.tagName === 'A' && el.getAttribute('href')) {
          return;
        }
        e.preventDefault();
        closeDeleteModal();
      });
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        closeDeleteModal();
      }
    });

    document.querySelectorAll('.js-delete-confirm-form').forEach(function (form) {
      var input = form.querySelector('.js-delete-confirm-input');
      var btn = form.querySelector('.js-delete-confirm-btn');
      if (!input || !btn) return;

      function sync() {
        var ok = (input.value || '').trim().toLowerCase() === confirmWord;
        btn.disabled = !ok;
      }

      input.addEventListener('input', sync);
      input.addEventListener('keyup', sync);
      sync();

      form.addEventListener('submit', function (e) {
        if ((input.value || '').trim().toLowerCase() !== confirmWord) {
          e.preventDefault();
          input.focus();
          return;
        }
        showLoader('Saving…');
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    bindConfirm();
    bindNavLoader();
    bindLazyTables();
    bindPhoneInputs();
    bindFilterFlip();
    bindExportExcel();
    bindLiveSearch();
    bindGlobalSearchSuggest();
    bindDeleteConfirm();
    bindToasts();
  });

  window.addEventListener('pageshow', function (e) {
    if (e.persisted) hideLoader();
  });
})();
