(function() {
    // delegate from the document so dynamically added fields work too
    // (Craft injects Matrix/module block HTML over Ajax long after this file has run)
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.heading-tag-field .btn');
        if (!btn) return;

        const wrapper = btn.closest('.heading-tag-field');
        const hiddenTag = wrapper.querySelector('.htf-hidden-tag');
        if (!hiddenTag) return;

        e.preventDefault();

        // remove submit class from all buttons before adding to clicked one
        // set the value on the hidden input
        wrapper.querySelectorAll('.btn').forEach(b => b.classList.remove('submit'));
        btn.classList.add('submit');
        hiddenTag.value = btn.dataset.value;

        // only dispatch change event on this specific hidden input
        hiddenTag.dispatchEvent(new Event('change', { bubbles: false }));
    });
})();
