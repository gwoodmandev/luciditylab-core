export function init() {
  navigation();
}

//const HEADER_SELECTOR = '[data-header]';
const NAV_SELECTOR = '[data-nav]';
const NAV_WRAPPER_SELECTOR = '[data-nav-wrapper]';
const NAV_TOGGLE_SELECTOR = '[data-nav-trigger]';
const NAV_OPEN_ATTRIBUTE = 'data-nav-open';

let navOpen = false;

function navigation() {
  const nav = document.querySelector(NAV_SELECTOR);
  if (!nav) return;

  handleOpenClose(nav);
  handleResize(nav);
  const debounceResize = debounce(() => handleResize(nav), 300);
  window.addEventListener('resize', debounceResize);
}

function handleOpenClose(nav) {
  const navToggle = document.querySelector(NAV_TOGGLE_SELECTOR);
  if (!navToggle) return;

  navToggle.addEventListener('click', () => {
    document.body.toggleAttribute(NAV_OPEN_ATTRIBUTE);
    isOpen();
    nav.setAttribute('aria-hidden', !navOpen ? 'true' : 'false');
    navToggle.setAttribute('aria-expanded', navOpen ? 'true' : 'false');
    navToggle.setAttribute('aria-label', navOpen ? 'Close navigation menu' : 'Open navigation menu');
  });
}

function handleResize(nav) {
  const navWrapper = nav.querySelector(NAV_WRAPPER_SELECTOR);
  if (!navWrapper) return;

  const wrapperWidth = navWrapper.getBoundingClientRect().width;
  const wrapperHeight = navWrapper.getBoundingClientRect().height;
  nav.style.setProperty('--nav-width', `${wrapperWidth}px`);
  nav.style.setProperty('--nav-height', `${wrapperHeight}px`);
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