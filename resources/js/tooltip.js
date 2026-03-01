/**
 * Tooltip global para elementos com data-tooltip.
 * Usado em todo o sistema (layout app, login, formulário público, etc.).
 */
function initGlobalTooltip() {
  var tip = document.getElementById('global-tooltip');
  if (!tip) {
    tip = document.createElement('div');
    tip.id = 'global-tooltip';
    document.body.appendChild(tip);
  }
  var timer = null;
  var delay = 400;

  function show(el) {
    if (el.classList.contains('nav-link') && document.body.classList.contains('sidebar-collapsed')) return;
    var text = el.getAttribute('data-tooltip');
    if (!text) return;
    clearTimeout(timer);
    timer = setTimeout(function () {
      tip.textContent = text;
      tip.classList.remove('is-visible');
      var rect = el.getBoundingClientRect();
      var tipRect = tip.getBoundingClientRect();
      var gap = 8;
      var top = rect.top - tipRect.height - gap;
      var left = rect.left + (rect.width / 2) - (tipRect.width / 2);
      if (top < 8) top = rect.bottom + gap;
      if (left < 8) left = 8;
      if (left + tipRect.width > window.innerWidth - 8) left = window.innerWidth - tipRect.width - 8;
      tip.style.top = top + 'px';
      tip.style.left = left + 'px';
      tip.classList.add('is-visible');
    }, delay);
  }

  function hide() {
    clearTimeout(timer);
    timer = null;
    tip.classList.remove('is-visible');
  }

  document.querySelectorAll('[data-tooltip]').forEach(function (el) {
    el.addEventListener('mouseenter', function () { show(this); });
    el.addEventListener('mouseleave', hide);
    el.addEventListener('focus', function () { show(this); });
    el.addEventListener('blur', hide);
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initGlobalTooltip);
} else {
  initGlobalTooltip();
}
