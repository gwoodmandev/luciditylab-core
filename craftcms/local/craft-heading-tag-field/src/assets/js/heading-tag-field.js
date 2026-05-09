(function() {
    document.querySelectorAll('.heading-tag-field').forEach(wrapper => {
        const hiddenTag = wrapper.querySelector('.htf-hidden-tag');
        if (!hiddenTag) return;

        // loop through buttons and add event listener
        // skip if already initialised to prevent duplicate event listeners
        wrapper.querySelectorAll('.btn').forEach(btn => {
            if (btn.classList.contains('htf-initialised')) return;

            btn.addEventListener('click', (e) => {
                e.preventDefault();

                // remove submit class from all buttons before adding to clicked one
                // set the value on the hidden input
                wrapper.querySelectorAll('.btn').forEach(b => b.classList.remove('submit'));
                btn.classList.add('submit');
                hiddenTag.value = btn.dataset.value;

                // only dispatch change event on this specific hidden input
                hiddenTag.dispatchEvent(new Event('change', { bubbles: false }));
            });

            // mark button as initialised
            btn.classList.add('htf-initialised');
        });
    });
})();