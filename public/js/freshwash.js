document.addEventListener('DOMContentLoaded', () => {
    const navButton = document.querySelector('.nav-toggle');
    const nav = document.querySelector('#main-nav');
    if (navButton && nav) {
        navButton.addEventListener('click', () => {
            const open = nav.classList.toggle('is-open');
            navButton.setAttribute('aria-expanded', String(open));
        });
        nav.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
            nav.classList.remove('is-open');
            navButton.setAttribute('aria-expanded', 'false');
        }));
    }

    const sidebarButton = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('#admin-sidebar');
    if (sidebarButton && sidebar) {
        sidebarButton.addEventListener('click', () => {
            const open = sidebar.classList.toggle('is-open');
            sidebarButton.setAttribute('aria-expanded', String(open));
        });
        document.addEventListener('click', (event) => {
            if (window.innerWidth <= 720 && !sidebar.contains(event.target) && !sidebarButton.contains(event.target)) {
                sidebar.classList.remove('is-open');
                sidebarButton.setAttribute('aria-expanded', 'false');
            }
        });
    }

    const orderForm = document.querySelector('#order-form');
    if (orderForm) {
        const serviceSelect = orderForm.querySelector('[data-service-select]');
        const weightField = orderForm.querySelector('[data-weight-field]');
        const weightInput = orderForm.querySelector('#weight_kg');
        const quantityField = orderForm.querySelector('[data-quantity-field]');
        const quantityInput = orderForm.querySelector('#quantity_items');
        const serviceSummary = orderForm.querySelector('[data-summary-service]');
        const quantitySummary = orderForm.querySelector('[data-summary-quantity]');
        const priceSummary = orderForm.querySelector('[data-summary-price]');
        const estimateNote = orderForm.querySelector('[data-estimate-note]');
        const pickupFields = orderForm.querySelector('[data-pickup-fields]');
        const pickupAddress = orderForm.querySelector('#delivery_address');
        const pickupSchedule = orderForm.querySelector('#pickup_schedule');

        const money = (amount) => new Intl.NumberFormat('id-ID', {
            style: 'currency', currency: 'IDR', maximumFractionDigits: 2,
        }).format(amount);

        const updateOrderSummary = () => {
            const option = serviceSelect.selectedOptions[0];
            const selected = option && option.value ? option : null;
            const unit = selected?.dataset.unit;
            const price = selected ? Number(selected.dataset.price) : 0;
            const itemMode = unit === 'item';

            weightField.hidden = !selected || itemMode;
            weightInput.disabled = !selected || itemMode;
            quantityField.hidden = !selected || !itemMode;
            quantityInput.disabled = !selected || !itemMode;
            quantityInput.required = itemMode && Boolean(selected);

            serviceSummary.textContent = selected ? selected.textContent.split('—')[0].trim() : 'Pilih layanan';
            const rawQuantity = itemMode ? quantityInput.value : weightInput.value;
            const quantity = Number(rawQuantity);
            quantitySummary.textContent = !selected || !quantity
                ? (itemMode ? 'Belum diisi' : 'Menunggu penimbangan')
                : `${quantity} ${itemMode ? 'item' : 'kg'}`;

            if (!selected) {
                priceSummary.textContent = '—';
                estimateNote.textContent = 'Pilih layanan untuk melihat estimasi harga.';
            } else if (!itemMode && !quantity) {
                priceSummary.textContent = 'Belum final';
                estimateNote.textContent = 'Estimasi final tersedia setelah pakaian ditimbang.';
            } else if (!quantity || quantity <= 0) {
                priceSummary.textContent = '—';
                estimateNote.textContent = 'Masukkan jumlah item untuk melihat estimasi harga.';
            } else {
                priceSummary.textContent = money(price * quantity);
                estimateNote.textContent = itemMode
                    ? 'Estimasi berdasarkan jumlah item yang Anda masukkan.'
                    : 'Estimasi ini dapat berubah setelah penimbangan aktual.';
            }
        };

        const updatePickupFields = () => {
            const pickupSelected = orderForm.querySelector('input[name="delivery_method"]:checked')?.value === 'pickup_delivery';
            pickupFields.hidden = !pickupSelected;
            pickupAddress.required = pickupSelected;
            pickupSchedule.required = pickupSelected;
            if (!pickupSchedule.min) {
                const now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                pickupSchedule.min = now.toISOString().slice(0, 16);
            }
        };

        serviceSelect.addEventListener('change', updateOrderSummary);
        weightInput.addEventListener('input', updateOrderSummary);
        quantityInput.addEventListener('input', updateOrderSummary);
        orderForm.querySelectorAll('input[name="delivery_method"]').forEach((input) => input.addEventListener('change', updatePickupFields));
        updateOrderSummary();
        updatePickupFields();
    }

    document.querySelectorAll('[data-confirm-reject]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm('Tolak pesanan ini? Pastikan alasan penolakan sudah benar.')) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-confirm-delete]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm('Hapus data ini? Data yang memiliki riwayat pesanan akan tetap dilindungi.')) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-confirm-verify]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm('Konfirmasi bahwa pembayaran ini sudah diterima?')) {
                event.preventDefault();
            }
        });
    });
});
