/* scaffold.js — small client glue for the htmx playground.
   htmx itself does the heavy lifting (swaps, boosting). This only wires the
   three things htmx can't do declaratively here: EventSource streaming, the
   server-fired toast, and showing a non-2xx return (the int→418 demo). */
(function () {
  'use strict';

  // ── SSE demo: open an EventSource, append events to the target ──
  function wireSse(root) {
    (root || document).querySelectorAll('[data-sse-start]').forEach(function (btn) {
      if (btn.__wired) return;
      btn.__wired = true;
      btn.addEventListener('click', function () {
        var target = document.querySelector(btn.getAttribute('data-sse-target'));
        if (!target || btn.disabled) return;
        target.textContent = '';
        btn.disabled = true;
        var es = new EventSource(btn.getAttribute('data-sse-url'));
        es.addEventListener('tick', function (e) {
          var line = document.createElement('div');
          line.textContent = '▸ ' + e.data;
          target.appendChild(line);
          target.scrollTop = target.scrollHeight;
        });
        var stop = function () { es.close(); btn.disabled = false; };
        es.addEventListener('close', stop);
        es.onerror = stop;
      });
    });
  }

  // ── Toast: the server fires HX-Trigger: {"toast":"…"} → htmx dispatches a
  //    `toast` event on <body> with the message in event.detail.value ──
  function showToast(msg) {
    var t = document.getElementById('toast');
    if (!t) return;
    t.textContent = msg || '⚡';
    t.classList.add('show');
    clearTimeout(t.__timer);
    t.__timer = setTimeout(function () { t.classList.remove('show'); }, 2600);
  }
  document.body.addEventListener('toast', function (e) {
    showToast(e.detail && (e.detail.value || e.detail.message || e.detail));
  });

  // ── Return-contract demo: let #ret-out display non-2xx responses so the
  //    int→418 button shows the status instead of htmx treating it as an error ──
  document.body.addEventListener('htmx:beforeSwap', function (e) {
    var d = e.detail;
    if (d && d.target && d.target.id === 'ret-out' && d.xhr && d.xhr.status >= 400) {
      d.shouldSwap = true;
      d.isError = false;
      if (!d.xhr.responseText) {
        d.serverResponse = 'HTTP ' + d.xhr.status + " — handler returned the int " + d.xhr.status;
      }
    }
  });

  // Initial wire + re-wire after every htmx swap (boosted nav or fragment swap).
  document.addEventListener('DOMContentLoaded', function () { wireSse(document); });
  document.body.addEventListener('htmx:afterSettle', function (e) { wireSse(e.target); });
})();
