export function init() {
  navigation();
}

// attributes
const NAV_OPEN_ATTRIBUTE = 'data-nav-open';
const DROPDOWN_OPEN_ATTRIBUTE = 'data-dropdown-open';

// selectors
const NAV_SELECTOR = '[data-nav]';
const NAV_WRAPPER_SELECTOR = '[data-nav-wrapper]';
const NAV_TOGGLE_SELECTOR = '[data-nav-trigger]';
const NAV_DROPDOWN_SELECTOR = '[data-nav-dropdown]';
const NAV_DROPDOWN_WRAPPER_SELECTOR = '[data-nav-dropdown-wrapper]';

let navOpen = false;

function navigation() {
  const nav = document.querySelector(NAV_SELECTOR);
  if (!nav) return;

  // disable tab indexing on nav elements
  setTabIndex(nav);

  // event handlers
  handleResize(nav);
  handleOpenClose(nav);
  handleDropdowns(nav);

  const debounceResize = debounce(() => handleResize(nav), 300);
  window.addEventListener('resize', debounceResize);
}

function handleOpenClose(nav) {
  // only target the top-level trigger (sits outside the nav)
  const navToggle = document.querySelector(`${NAV_TOGGLE_SELECTOR}[aria-controls="nav"]`);
  if (!navToggle) return;

  navToggle.addEventListener('click', () => {
    document.body.toggleAttribute(NAV_OPEN_ATTRIBUTE);
    isOpen();

    // set accessibility attributes
    nav.setAttribute('aria-hidden', !navOpen);
    navToggle.setAttribute('aria-expanded', navOpen);
    navToggle.setAttribute('aria-label', navOpen ? 'Close navigation menu' : 'Open navigation menu');

    // enable/disable tabbing on nav elements
    setTabIndex(nav);

    // close all dropdowns when the nav itself closes
    if (!navOpen) closeAllDropdowns(nav);

    // disable lenis smooth scroll when nav opened
    if (navOpen) {
      window.lenis.stop();
    } else {
      window.lenis.start();
    }
  });
}

function handleDropdowns(nav) {
  const triggers = nav.querySelectorAll(NAV_TOGGLE_SELECTOR);

  triggers.forEach(trigger => {
    const dropdown = trigger.nextElementSibling;
    if (!dropdown || !dropdown.matches(NAV_DROPDOWN_SELECTOR)) return;

    trigger.addEventListener('click', e => {
      e.preventDefault();
      e.stopPropagation();

      const isOpening = !dropdown.hasAttribute(DROPDOWN_OPEN_ATTRIBUTE);

      closeSiblingDropdowns(nav, dropdown);

      dropdown.toggleAttribute(DROPDOWN_OPEN_ATTRIBUTE, isOpening);
      trigger.setAttribute('aria-expanded', isOpening);

      if (!isOpening) closeDescendantDropdowns(dropdown);

      // recalculate nav height based on open dropdowns
      updateHeights(nav);
    });
  });
}

function closeSiblingDropdowns(dropdown) {
  const parent = dropdown.parentElement;
  if (!parent) return;

  const siblings = parent.parentElement?.querySelectorAll(`:scope > * > ${NAV_DROPDOWN_SELECTOR}[${DROPDOWN_OPEN_ATTRIBUTE}]`);
  siblings?.forEach(sibling => {
    if (sibling !== dropdown) {
      sibling.removeAttribute(DROPDOWN_OPEN_ATTRIBUTE);
      const trigger = sibling.previousElementSibling;
      if (trigger?.matches(NAV_TOGGLE_SELECTOR)) {
        trigger.setAttribute('aria-expanded', 'false');
      }
      closeDescendantDropdowns(sibling);
    }
  });
}

function closeDescendantDropdowns(dropdown) {
  dropdown.querySelectorAll(`[${DROPDOWN_OPEN_ATTRIBUTE}]`).forEach(child => {
    child.removeAttribute(DROPDOWN_OPEN_ATTRIBUTE);
    const trigger = child.previousElementSibling;
    if (trigger?.matches(NAV_TOGGLE_SELECTOR)) {
      trigger.setAttribute('aria-expanded', 'false');
    }
  });
}

function closeAllDropdowns(nav) {
  nav.querySelectorAll(`[${DROPDOWN_OPEN_ATTRIBUTE}]`).forEach(dropdown => {
    dropdown.removeAttribute(DROPDOWN_OPEN_ATTRIBUTE);
  });
  nav.querySelectorAll(`${NAV_TOGGLE_SELECTOR}[aria-expanded="true"]`).forEach(trigger => {
    // skip the top-level toggle (its outside the nav, but just in case)
    if (trigger.getAttribute('aria-controls') !== 'nav') {
      trigger.setAttribute('aria-expanded', 'false');
    }
  });

  updateHeights(nav);
}

function handleResize(nav) {
  // measure each dropdown wrapper and store its BASE height
  nav.querySelectorAll(NAV_DROPDOWN_SELECTOR).forEach(dropdown => {
    const wrapper = dropdown.querySelector(`:scope > ${NAV_DROPDOWN_WRAPPER_SELECTOR}, :scope > * > ${NAV_DROPDOWN_WRAPPER_SELECTOR}`);
    if (!wrapper) return;

    const { height } = wrapper.getBoundingClientRect();
    if (height > 0) {
      dropdown.style.setProperty('--dropdown-base-height', `${height}px`);
    }
  });

  // measure base nav wrapper
  const navWrapper = nav.querySelector(NAV_WRAPPER_SELECTOR);
  if (navWrapper) {
    const { width, height } = navWrapper.getBoundingClientRect();
    nav.style.setProperty('--nav-width', `${width}px`);
    nav.style.setProperty('--nav-base-height', `${height}px`);
  }

  updateHeights(nav);
}

function updateHeights(nav) {
  // update every dropdown's height from the innermost outward
  // querySelectorAll returns elements in document order; we reverse so deepest are processed first
  const dropdowns = Array.from(nav.querySelectorAll(NAV_DROPDOWN_SELECTOR)).reverse();

  dropdowns.forEach(dropdown => {
    const base = parseFloat(dropdown.style.getPropertyValue('--dropdown-base-height')) || 0;
    const childrenHeight = sumOpenChildren(dropdown);
    dropdown.style.setProperty('--dropdown-height', `${base + childrenHeight}px`);
  });

  // finally update the top-level nav height
  const base = parseFloat(nav.style.getPropertyValue('--nav-base-height')) || 0;
  const openHeight = sumOpenChildren(nav);
  nav.style.setProperty('--nav-height', `${base + openHeight}px`);
}

function sumOpenChildren(element) {
  // find dropdowns that are open AND don't have another dropdown between them and `element`
  const openDropdowns = element.querySelectorAll(`${NAV_DROPDOWN_SELECTOR}[${DROPDOWN_OPEN_ATTRIBUTE}]`);
  let total = 0;

  openDropdowns.forEach(dropdown => {
    // only count direct dropdown descendants (no other dropdown between this and `element`)
    if (dropdown.parentElement.closest(NAV_DROPDOWN_SELECTOR) !== element.closest(NAV_DROPDOWN_SELECTOR)) {
      return;
    }
    total += parseFloat(dropdown.style.getPropertyValue('--dropdown-height')) || 0;
  });

  return total;
}

function setTabIndex(nav) {
  nav.querySelectorAll('a, button, input').forEach(el => {
    el.setAttribute('tabindex', navOpen ? '0' : '-1');
  });
}

function isOpen() {
  navOpen = document.body.hasAttribute(NAV_OPEN_ATTRIBUTE);
}

function debounce(fn, delay) {
  let timer;
  return function (...args) {
    clearTimeout(timer);
    timer = setTimeout(() => fn.apply(this, args), delay);
  };
}