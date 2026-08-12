(() => {
  const ROOT_SELECTOR = '[data-fb-submissions]';
  const ROW_SELECTOR = '[data-fb-submission]';
  const OPEN_SELECTOR = '[data-fb-open]';
  const TOGGLE_SELECTOR = '[data-fb-toggle-read]';
  const MARK_ALL_SELECTOR = '[data-fb-mark-all]';
  const DETAIL_SELECTOR = '[data-fb-detail]';
  const BADGE_SELECTOR = '.fb-unread-badge';

  const UNREAD_CLASS = 'fb-submission--unread';

  function init() {
    document.querySelectorAll(ROOT_SELECTOR).forEach(setup);
  }

  function setup(root) {
    if (root.dataset.fbReady) return;
    root.dataset.fbReady = '1';

    const formId = root.dataset.fbSubmissions;
    const detail = root.querySelector(DETAIL_SELECTOR);

    root.addEventListener('click', (e) => {
      if (e.target.closest(OPEN_SELECTOR)) {
        e.preventDefault();
        open(e.target.closest(ROW_SELECTOR), detail);
        return;
      }

      if (e.target.closest(TOGGLE_SELECTOR)) {
        e.preventDefault();
        toggleRead(e.target.closest(ROW_SELECTOR), e.target.closest(TOGGLE_SELECTOR));
        return;
      }

      if (e.target.closest(MARK_ALL_SELECTOR)) {
        e.preventDefault();
        markAllRead(root, formId, e.target.closest(MARK_ALL_SELECTOR));
      }
    });
  }

  async function open(row, detail) {
    if (!row || !detail) return;

    try {
      const { data } = await Craft.sendActionRequest('GET', 'form-builder/submissions/detail', {
        params: { id: row.dataset.fbSubmission },
      });

      detail.innerHTML = data.html;
      detail.hidden = false;
      detail.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

      // opening a submission marks it read server-side, so mirror that here
      markRowRead(row, true);
      setBadge(data.unread);
    } catch (e) {
      Craft.cp.displayError(Craft.t('form-builder', 'Could not load that submission.'));
    }
  }

  async function toggleRead(row, button) {
    if (!row || !button) return;

    const makeRead = button.dataset.fbToggleRead !== '1';

    try {
      const { data } = await Craft.sendActionRequest('POST', 'form-builder/submissions/toggle-read', {
        data: { id: row.dataset.fbSubmission, read: makeRead ? 1 : 0 },
      });

      markRowRead(row, makeRead);
      setBadge(data.unread);
    } catch (e) {
      Craft.cp.displayError(Craft.t('form-builder', 'Could not update that submission.'));
    }
  }

  async function markAllRead(root, formId, button) {
    try {
      await Craft.sendActionRequest('POST', 'form-builder/submissions/mark-all-read', {
        data: { formId },
      });

      root.querySelectorAll(`.${UNREAD_CLASS}`).forEach((row) => markRowRead(row, true));
      button.remove();
      setBadge(0);
      Craft.cp.displayNotice(Craft.t('form-builder', 'All submissions marked as read.'));
    } catch (e) {
      Craft.cp.displayError(Craft.t('form-builder', 'Could not mark submissions as read.'));
    }
  }

  function markRowRead(row, read) {
    row.classList.toggle(UNREAD_CLASS, !read);
    row.querySelector('.status')?.classList.toggle('enabled', !read);

    const button = row.querySelector(TOGGLE_SELECTOR);

    if (button) {
      button.dataset.fbToggleRead = read ? '1' : '0';
      button.textContent = read
        ? Craft.t('form-builder', 'Mark unread')
        : Craft.t('form-builder', 'Mark read');
    }
  }

  // keeps the tab badge in step with whatever the server reports
  function setBadge(unread) {
    const badge = document.querySelector(BADGE_SELECTOR);
    if (!badge) return;

    if (unread > 0) {
      badge.textContent = unread > 99 ? '99+' : String(unread);
    } else {
      badge.remove();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
