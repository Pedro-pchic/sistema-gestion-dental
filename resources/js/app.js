const sidebar = document.querySelector('[data-admin-sidebar]');
const sidebarOverlay = document.querySelector('[data-sidebar-overlay]');
const sidebarOpenButton = document.querySelector('[data-sidebar-open]');
const sidebarCloseButton = document.querySelector('[data-sidebar-close]');

if (sidebar && sidebarOverlay && sidebarOpenButton && sidebarCloseButton) {
    const desktopSidebar = window.matchMedia('(min-width: 64rem)');

    const setSidebarOpen = (isOpen, restoreFocus = false) => {
        const isSidebarVisible = desktopSidebar.matches || isOpen;
        const isDrawerOpen = !desktopSidebar.matches && isOpen;

        sidebar.classList.toggle('-translate-x-full', !isSidebarVisible);
        sidebar.classList.toggle('translate-x-0', isSidebarVisible);
        sidebarOverlay.classList.toggle('pointer-events-none', !isDrawerOpen);
        sidebarOverlay.classList.toggle('opacity-0', !isDrawerOpen);
        sidebar.setAttribute('aria-hidden', String(!isSidebarVisible));
        sidebar.toggleAttribute('inert', !isSidebarVisible);
        sidebarOpenButton.setAttribute('aria-expanded', String(isDrawerOpen));
        document.body.classList.toggle('overflow-hidden', isDrawerOpen);

        if (isDrawerOpen) {
            sidebarCloseButton.focus();
        } else if (restoreFocus && !desktopSidebar.matches) {
            sidebarOpenButton.focus();
        }
    };

    sidebarOpenButton.addEventListener('click', () => setSidebarOpen(true));
    sidebarCloseButton.addEventListener('click', () => setSidebarOpen(false, true));
    sidebarOverlay.addEventListener('click', () => setSidebarOpen(false, true));

    sidebar.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setSidebarOpen(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sidebarOpenButton.getAttribute('aria-expanded') === 'true') {
            setSidebarOpen(false, true);
            return;
        }

        if (event.key !== 'Tab' || sidebarOpenButton.getAttribute('aria-expanded') !== 'true') {
            return;
        }

        const focusableElements = [...sidebar.querySelectorAll('a[href], button:not([disabled])')]
            .filter((element) => element.getClientRects().length > 0);
        const firstFocusableElement = focusableElements[0];
        const lastFocusableElement = focusableElements.at(-1);

        if (event.shiftKey && document.activeElement === firstFocusableElement) {
            event.preventDefault();
            lastFocusableElement.focus();
        } else if (!event.shiftKey && document.activeElement === lastFocusableElement) {
            event.preventDefault();
            firstFocusableElement.focus();
        }
    });

    desktopSidebar.addEventListener('change', () => setSidebarOpen(false));
    setSidebarOpen(false);
}

document.querySelectorAll('[data-purchase-form]').forEach((form) => {
    const lines = form.querySelector('[data-purchase-lines]');
    const template = form.querySelector('[data-purchase-line-template]');
    const addButton = form.querySelector('[data-add-purchase-line]');
    const totalOutput = form.querySelector('[data-purchase-total]');
    const money = new Intl.NumberFormat('es-GT', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    const purchaseLines = () => [...lines.querySelectorAll('[data-purchase-line]')];

    const updateNames = () => {
        purchaseLines().forEach((line, index) => {
            line.querySelectorAll('[data-purchase-field]').forEach((field) => {
                const fieldName = field.dataset.purchaseField;
                const fieldId = 'detail-' + index + '-' + fieldName.replace(/_/g, '-');

                field.name = 'details[' + index + '][' + fieldName + ']';
                field.id = fieldId;
                line.querySelector('[data-purchase-label="' + fieldName + '"]').htmlFor = fieldId;
            });
        });
    };

    const updateTotals = () => {
        let total = 0;

        purchaseLines().forEach((line) => {
            const quantity = Number(line.querySelector('[data-purchase-field="quantity"]').value);
            const unitCost = Number(line.querySelector('[data-purchase-field="unit_cost"]').value);
            const subtotal = Number.isFinite(quantity * unitCost) ? quantity * unitCost : 0;

            line.querySelector('[data-purchase-subtotal]').textContent = money.format(subtotal);
            total += subtotal;
        });

        totalOutput.textContent = money.format(total);
    };

    const addLine = () => {
        lines.append(template.content.cloneNode(true));
        updateNames();
        updateTotals();
    };

    addButton.addEventListener('click', addLine);

    lines.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-purchase-line]');

        if (!removeButton) {
            return;
        }

        const line = removeButton.closest('[data-purchase-line]');

        if (purchaseLines().length === 1) {
            line.querySelectorAll('[data-purchase-field]').forEach((field) => {
                field.value = '';
            });
        } else {
            line.remove();
        }

        updateNames();
        updateTotals();
    });

    lines.addEventListener('input', updateTotals);
    lines.addEventListener('change', updateTotals);
    updateNames();
    updateTotals();
});

document.querySelectorAll('[data-quote-form]').forEach((form) => {
    const lines = form.querySelector('[data-quote-lines]');
    const template = form.querySelector('[data-quote-line-template]');
    const addButton = form.querySelector('[data-add-quote-line]');
    const totalOutput = form.querySelector('[data-quote-total]');
    const money = new Intl.NumberFormat('es-GT', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    const quoteLines = () => [...lines.querySelectorAll('[data-quote-line]')];

    const updateNames = () => {
        quoteLines().forEach((line, index) => {
            line.querySelectorAll('[data-quote-field]').forEach((field) => {
                const fieldName = field.dataset.quoteField;
                const fieldId = 'quote-detail-' + index + '-' + fieldName.replace(/_/g, '-');

                field.name = 'details[' + index + '][' + fieldName + ']';
                field.id = fieldId;
                line.querySelector('[data-quote-label="' + fieldName + '"]').htmlFor = fieldId;
            });
        });
    };

    const updateTotals = () => {
        let total = 0;

        quoteLines().forEach((line) => {
            const quantity = Number(line.querySelector('[data-quote-field="quantity"]').value);
            const unitPrice = Number(line.querySelector('[data-quote-field="unit_price"]').value);
            const subtotal = Number.isFinite(quantity * unitPrice) ? quantity * unitPrice : 0;

            line.querySelector('[data-quote-subtotal]').textContent = 'Q ' + money.format(subtotal);
            total += subtotal;
        });

        totalOutput.textContent = 'Q ' + money.format(total);
    };

    const addLine = () => {
        lines.append(template.content.cloneNode(true));
        updateNames();
        updateTotals();
    };

    addButton.addEventListener('click', addLine);

    lines.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-quote-line]');

        if (!removeButton) {
            return;
        }

        const line = removeButton.closest('[data-quote-line]');

        if (quoteLines().length === 1) {
            line.querySelectorAll('[data-quote-field]').forEach((field) => {
                field.value = field.dataset.quoteField === 'quantity' ? '1' : '';
            });
        } else {
            line.remove();
        }

        updateNames();
        updateTotals();
    });

    lines.addEventListener('change', (event) => {
        const productField = event.target.closest('[data-quote-field="product_id"]');

        if (productField) {
            const unitPriceField = productField.closest('[data-quote-line]').querySelector('[data-quote-field="unit_price"]');
            const selectedOption = productField.selectedOptions[0];

            unitPriceField.value = selectedOption.dataset.price ?? '';
        }

        updateTotals();
    });

    lines.addEventListener('input', updateTotals);
    updateNames();
    updateTotals();
});

document.querySelectorAll('[data-order-form]').forEach((form) => {
    const lines = form.querySelector('[data-order-lines]');
    const template = form.querySelector('[data-order-line-template]');
    const addButton = form.querySelector('[data-add-order-line]');
    const totalOutput = form.querySelector('[data-order-total]');
    const money = new Intl.NumberFormat('es-GT', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    const orderLines = () => [...lines.querySelectorAll('[data-order-line]')];

    const updateNames = () => {
        orderLines().forEach((line, index) => {
            line.querySelectorAll('[data-order-field]').forEach((field) => {
                const fieldName = field.dataset.orderField;
                const fieldId = 'order-detail-' + index + '-' + fieldName.replace(/_/g, '-');

                field.name = 'details[' + index + '][' + fieldName + ']';
                field.id = fieldId;
                line.querySelector('[data-order-label="' + fieldName + '"]').htmlFor = fieldId;
            });
        });
    };

    const updateTotals = () => {
        let total = 0;

        orderLines().forEach((line) => {
            const quantity = Number(line.querySelector('[data-order-field="quantity"]').value);
            const unitPrice = Number(line.querySelector('[data-order-field="unit_price"]').value);
            const subtotal = Number.isFinite(quantity * unitPrice) ? quantity * unitPrice : 0;

            line.querySelector('[data-order-subtotal]').textContent = 'Q ' + money.format(subtotal);
            total += subtotal;
        });

        totalOutput.textContent = 'Q ' + money.format(total);
    };

    addButton.addEventListener('click', () => {
        lines.append(template.content.cloneNode(true));
        updateNames();
        updateTotals();
    });

    lines.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-order-line]');

        if (!removeButton) {
            return;
        }

        const line = removeButton.closest('[data-order-line]');

        if (orderLines().length === 1) {
            line.querySelectorAll('[data-order-field]').forEach((field) => {
                field.value = field.dataset.orderField === 'quantity' ? '1' : '';
            });
        } else {
            line.remove();
        }

        updateNames();
        updateTotals();
    });

    lines.addEventListener('change', (event) => {
        const productField = event.target.closest('[data-order-field="product_id"]');

        if (productField) {
            const unitPriceField = productField.closest('[data-order-line]').querySelector('[data-order-field="unit_price"]');
            const selectedOption = productField.selectedOptions[0];

            unitPriceField.value = selectedOption.dataset.price ?? '';
        }

        updateTotals();
    });

    lines.addEventListener('input', updateTotals);
    updateNames();
    updateTotals();
});

document.querySelectorAll('[data-product-image-input]').forEach((input) => {
    const form = input.closest('form');
    const preview = form.querySelector('[data-product-image-preview]');
    const currentImage = form.querySelector('[data-product-image-current]');
    const removeImage = form.querySelector('#remove_image');
    let objectUrl = null;

    removeImage?.addEventListener('change', () => {
        if (!removeImage.checked) {
            return;
        }

        input.value = '';

        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }

        preview.classList.add('hidden');
        currentImage?.classList.remove('hidden');
    });

    input.addEventListener('change', () => {
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }

        const image = input.files?.[0];

        if (!image) {
            preview.classList.add('hidden');
            currentImage?.classList.remove('hidden');
            return;
        }

        objectUrl = URL.createObjectURL(image);
        preview.src = objectUrl;
        preview.classList.remove('hidden');
        currentImage?.classList.add('hidden');

        if (removeImage) {
            removeImage.checked = false;
        }
    });
});
