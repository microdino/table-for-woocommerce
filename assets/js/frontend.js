/* Table selection and automatic filter submission, scoped to each shortcode. */
(function () {
    'use strict';
    function init() {
        document.querySelectorAll('.prta-wrapper').forEach(function (wrapper) {
            wrapper.querySelectorAll('.prta-filters select, .prta-pagination select').forEach(function (select) {
                select.addEventListener('change', function () {
                    const form = select.form;
                    if (!form) return;
                    // Submit the entire form so category, tag and search stay combined.
                    if (typeof form.requestSubmit === 'function') form.requestSubmit();
                    else form.submit();
                });
            });
            const boxes = Array.from(wrapper.querySelectorAll('.prta-checkbox'));
            const toggles = wrapper.querySelectorAll('.prta-select-all');
            const statuses = wrapper.querySelectorAll('.prta-selection-status');
            function update() {
                const count = boxes.filter(function (box) { return box.checked; }).length;
                boxes.forEach(function (box) {
                    box.closest('tr').classList.toggle('prta-is-selected', box.checked);
                });
                toggles.forEach(function (toggle) {
                    toggle.checked = boxes.length > 0 && count === boxes.length;
                    toggle.indeterminate = count > 0 && count < boxes.length;
                    toggle.disabled = boxes.length === 0;
                });
                wrapper.querySelectorAll('.prta-bulk-submit').forEach(function (button) {
                    button.disabled = count === 0;
                });
                statuses.forEach(function (status) {
                    status.textContent = count ? status.dataset.template.replace('%s', String(count)) : '';
                });
            }
            toggles.forEach(function (toggle) {
                toggle.hidden = false;
                toggle.addEventListener('change', function () {
                    boxes.forEach(function (box) { box.checked = toggle.checked; });
                    update();
                });
            });
            boxes.forEach(function (box) { box.addEventListener('change', update); });
            const scroll = wrapper.querySelector('.prta-table-scroll');
            const hint = wrapper.querySelector('.prta-scroll-hint');
            function updateOverflow() {
                if (!scroll || !hint) return;
                const overflows = scroll.scrollWidth > scroll.clientWidth + 1;
                hint.hidden = !overflows;
                scroll.tabIndex = overflows ? 0 : -1;
                if (overflows) scroll.setAttribute('aria-describedby', hint.id);
                else scroll.removeAttribute('aria-describedby');
            }
            if (typeof ResizeObserver !== 'undefined' && scroll) {
                const observer = new ResizeObserver(updateOverflow);
                observer.observe(scroll);
                const table = scroll.querySelector('table');
                if (table) observer.observe(table);
            }
            window.addEventListener('resize', updateOverflow);
            window.addEventListener('pageshow', update);
            updateOverflow();
            update();
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
