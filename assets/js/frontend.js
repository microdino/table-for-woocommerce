/* Table selection and automatic filter submission, scoped to each shortcode. */
(function () {
    'use strict';
    function init() {
        document.querySelectorAll('.prta-wrapper').forEach(function (wrapper) {
            function formatPrice(amount) {
                const format = window.prtaPriceFormat;
                if (!format) return String(amount);
                const decimals = Math.max(0, Number(format.decimals) || 0);
                const parts = Number(amount).toFixed(decimals).split('.');
                const integer = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, format.thousandSeparator);
                const number = integer + (parts[1] ? format.decimalSeparator + parts[1] : '');
                const space = format.position.endsWith('_space') ? '\u00a0' : '';
                return format.position.startsWith('right') ? number + space + format.symbol : format.symbol + space + number;
            }
            function updateTotals() {
                wrapper.querySelectorAll('.prta-cart-form tbody tr').forEach(function (row) {
                    const total = row.querySelector('.prta-line-total');
                    if (!total) return;
                    const quantity = row.querySelector('.prta-qty');
                    const count = quantity ? Math.max(1, Math.floor(Number(quantity.value) || 1)) : 1;
                    total.textContent = formatPrice((Number(total.dataset.price) || 0) * count);
                });
            }
            wrapper.querySelectorAll('.prta-qty').forEach(function (input) {
                input.addEventListener('input', function () { updateTotals(); update(); });
            });
            const search = wrapper.querySelector('.prta-search input[type="search"]');
            if (search) {
                let searchTimer;
            search.addEventListener('input', function () {
                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(function () {
                    if (typeof search.form.requestSubmit === 'function') search.form.requestSubmit();
                    else search.form.submit();
                }, 400);
            });
            }
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
			wrapper.querySelectorAll('.prta-variable-controls').forEach(function (controls) {
				const selects = Array.from(controls.querySelectorAll('.prta-attribute'));
				const variation = controls.querySelector('.prta-variation');
				const availableVariations = JSON.parse(controls.dataset.variations || '[]');
				const button = controls.querySelector('.prta-cart');
				function syncVariation() {
					const complete = selects.length > 0 && selects.every(function (select) { return Boolean(select.value); });
					const match = complete && availableVariations.find(function (item) {
						return selects.every(function (select) {
							const value = item.attributes[select.dataset.attribute];
							return value === '' || value === select.value;
						});
					});
					variation.value = match ? String(match.id) : '';
					const rowTotal = controls.closest('tr').querySelector('.prta-line-total');
					const row = controls.closest('tr');
					if (!row.dataset.basePrice) row.dataset.basePrice = row.dataset.price;
					row.dataset.price = match ? String(match.price) : row.dataset.basePrice;
					if (rowTotal) {
						if (!rowTotal.dataset.basePrice) rowTotal.dataset.basePrice = rowTotal.dataset.price;
						rowTotal.dataset.price = match ? String(match.price) : rowTotal.dataset.basePrice;
					}
					const available = Boolean(match);
					button.disabled = !available;
					const checkbox = controls.querySelector('.prta-checkbox');
					if (checkbox) {
						checkbox.disabled = !available;
						if (!available) checkbox.checked = false;
					}
					updateTotals();
					update();
				}
				selects.forEach(function (select) { select.addEventListener('change', syncVariation); });
				window.addEventListener('pageshow', syncVariation);
				syncVariation();
			});
            function update() {
                const availableBoxes = boxes.filter(function (box) { return !box.disabled; });
                const count = availableBoxes.filter(function (box) { return box.checked; }).length;
                let items = 0;
                let total = 0;
                availableBoxes.filter(function (box) { return box.checked; }).forEach(function (box) {
                    const row = box.closest('tr');
                    const quantity = row.querySelector('.prta-qty');
                    const amount = quantity ? Math.max(1, Math.floor(Number(quantity.value) || 1)) : 1;
                    items += amount;
                    total += (Number(row.dataset.price) || 0) * amount;
                });
                boxes.forEach(function (box) {
                    box.closest('tr').classList.toggle('prta-is-selected', box.checked);
                });
                toggles.forEach(function (toggle) {
                    toggle.checked = availableBoxes.length > 0 && count === availableBoxes.length;
                    toggle.indeterminate = count > 0 && count < availableBoxes.length;
                    toggle.disabled = availableBoxes.length === 0;
                });
                wrapper.querySelectorAll('.prta-bulk-submit').forEach(function (button) {
                    button.disabled = count === 0;
                    const template = items === 1 ? button.dataset.singularTemplate : button.dataset.pluralTemplate;
                    button.textContent = template.replace(/\{items\}/g, String(items)).replace(/\{total\}/g, formatPrice(total));
                });
            }
            toggles.forEach(function (toggle) {
                toggle.addEventListener('change', function () {
                    const availableBoxes = boxes.filter(function (box) { return !box.disabled; });
                    availableBoxes.forEach(function (box) { box.checked = toggle.checked; });
                    update();
                });
            });
            boxes.forEach(function (box) { box.addEventListener('change', update); });
            const scroll = wrapper.querySelector('.prta-table-scroll');
            function updateOverflow() {
                if (!scroll) return;
                const overflows = scroll.scrollWidth > scroll.clientWidth + 1;
                scroll.tabIndex = overflows ? 0 : -1;
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
            updateTotals();
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
