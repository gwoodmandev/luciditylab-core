// ============================================================================== //
// accordion module
// ============================================================================== //

export function init() {
  accordion();
}

// attributes
const OPEN_ATTRIBUTE = 'data-accordion-open';
const READY_ATTRIBUTE = 'data-accordion-ready';

// selectors
const ACCORDION_SELECTOR = '[data-accordion]';
const ITEM_SELECTOR = '[data-accordion-item]';
const TRIGGER_SELECTOR = '[data-accordion-trigger]';
const PANEL_SELECTOR = '[data-accordion-panel]';
const INNER_SELECTOR = '[data-accordion-inner]';

// custom properties
const HEIGHT_PROPERTY = '--accordion-panel-height';

function accordion() {
  const accordions = document.querySelectorAll(ACCORDION_SELECTOR);
  if (!accordions.length) return;

  accordions.forEach(setupAccordion);
}

function setupAccordion(accordion) {
  // init() may run twice (self-init plus a main.js call); a second delegated
  // listener would toggle every click straight back to where it started
  if (accordion.hasAttribute(READY_ATTRIBUTE)) return;

  const items = accordion.querySelectorAll(ITEM_SELECTOR);
  if (!items.length) return;

  accordion.setAttribute(READY_ATTRIBUTE, '');

  // measure every panel up front so the first open animates from a real height
  updateHeights(accordion);

  accordion.addEventListener('click', (e) => {
    const trigger = e.target.closest(TRIGGER_SELECTOR);
    if (!trigger || !accordion.contains(trigger)) return;

    e.preventDefault();
    toggleItem(trigger.closest(ITEM_SELECTOR));
  });

  // re-measure when the panel contents reflow (font loading, responsive images,
  // viewport changes) so an open panel never clips its copy
  observeResize(accordion);
}

function toggleItem(item) {
  if (!item) return;

  const trigger = item.querySelector(TRIGGER_SELECTOR);
  const panel = item.querySelector(PANEL_SELECTOR);
  if (!trigger || !panel) return;

  const isOpen = item.hasAttribute(OPEN_ATTRIBUTE);

  // measure immediately before opening: the content may have changed height
  // since the last measurement
  if (!isOpen) updateItemHeight(item);

  item.toggleAttribute(OPEN_ATTRIBUTE, !isOpen);
  trigger.setAttribute('aria-expanded', String(!isOpen));

  // inert keeps collapsed content out of the tab order and the accessibility
  // tree without making it unmeasurable the way [hidden] would
  panel.toggleAttribute('inert', isOpen);
}

function updateHeights(accordion) {
  accordion.querySelectorAll(ITEM_SELECTOR).forEach(updateItemHeight);
}

function updateItemHeight(item) {
  const panel = item.querySelector(PANEL_SELECTOR);
  const inner = item.querySelector(INNER_SELECTOR);
  if (!panel || !inner) return;

  // scrollHeight is unaffected by the panel's own collapsed height, so the
  // measurement is valid whether the item is open or closed
  const height = inner.scrollHeight;

  if (height > 0) panel.style.setProperty(HEIGHT_PROPERTY, `${height}px`);
}

function observeResize(accordion) {
  if (typeof ResizeObserver === 'undefined') return;

  const observer = new ResizeObserver((entries) => {
    entries.forEach((entry) => {
      const item = entry.target.closest(ITEM_SELECTOR);
      if (item) updateItemHeight(item);
    });
  });

  accordion.querySelectorAll(INNER_SELECTOR).forEach((inner) => observer.observe(inner));
}

// Modular scripts are injected as standalone <script type="module"> tags by the
// asset registry, so nothing imports this file or calls init() for us.
// Self-initialise, while keeping the named export so main.js can drive it too.
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init, { once: true });
} else {
  init();
}
