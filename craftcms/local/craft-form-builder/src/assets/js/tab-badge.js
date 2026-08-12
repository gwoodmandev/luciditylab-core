(() => {
  const SOURCE_SELECTOR = '[data-fb-badge]';
  const BADGE_CLASS = 'fb-unread-badge';
  const OBSERVE_TIMEOUT = 10000;

  function init() {
    const source = document.querySelector(SOURCE_SELECTOR);
    if (!source) return;

    const label = source.dataset.fbBadgeLabel || '';
    const count = parseInt(source.dataset.fbBadge, 10) || 0;

    if (!label) return;

    if (!decorate(label, count)) {
      // the tab bar may not exist yet on first paint
      const observer = new MutationObserver(() => {
        if (decorate(label, count)) observer.disconnect();
      });

      observer.observe(document.body, { childList: true, subtree: true });
      setTimeout(() => observer.disconnect(), OBSERVE_TIMEOUT);
    }
  }

  function decorate(label, count) {
    const tab = findTab(label);
    if (!tab) return false;

    // Craft nests the text in .tab-label - fall back to the anchor itself
    const target = tab.querySelector('.tab-label') || tab;
    const existing = target.querySelector(`.${BADGE_CLASS}`);

    if (!count) {
      existing?.remove();
      return true;
    }

    const text = count > 99 ? '99+' : String(count);

    if (existing) {
      existing.textContent = text;
      return true;
    }

    const badge = document.createElement('span');
    badge.className = `badge ${BADGE_CLASS}`;
    badge.textContent = text;
    badge.setAttribute('aria-label', `${text} unread`);
    target.appendChild(badge);

    return true;
  }

  // matches on the tab's visible text, ignoring any badge already appended
  function findTab(label) {
    return Array.from(document.querySelectorAll('#tabs a, .tabs a')).find(
      (a) => a.textContent.trim().replace(/\s*\d+\+?$/, '') === label
    );
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
