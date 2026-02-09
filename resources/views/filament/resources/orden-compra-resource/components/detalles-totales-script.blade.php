<script>
    (() => {
        const formatMoney = (value, decimals = 2) => {
            const number = Number.isFinite(value) ? value : 0;
            return number.toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            });
        };

        const parseNumber = (value) => {
            if (typeof value !== 'string') {
                return Number(value) || 0;
            }

            const normalized = value.replace(/,/g, '').trim();
            const parsed = Number.parseFloat(normalized);

            return Number.isFinite(parsed) ? parsed : 0;
        };

        const updateRow = (row) => {
            const cantidad = parseNumber(row.querySelector('[data-detalle-field="cantidad"]')?.value);
            const costo = parseNumber(row.querySelector('[data-detalle-field="costo"]')?.value);
            const descuento = parseNumber(row.querySelector('[data-detalle-field="descuento"]')?.value);
            const impuesto = parseNumber(row.querySelector('[data-detalle-field="impuesto"]')?.value);

            const subtotal = cantidad * costo;
            const valorIva = subtotal * (impuesto / 100);
            const total = (subtotal + valorIva) - descuento;

            const subtotalEl = row.querySelector('[data-subtotal-linea-value]');
            if (subtotalEl) {
                subtotalEl.textContent = `$${formatMoney(subtotal, 4)}`;
            }

            const totalEl = row.querySelector('[data-total-linea-value]');
            if (totalEl) {
                totalEl.textContent = `$${formatMoney(total, 4)}`;
            }

            return { subtotal, descuento, impuesto };
        };

        const updateTotals = (form) => {
            const rows = Array.from(form.querySelectorAll('[data-detalle-row]'));
            const basePorIva = new Map();
            const ivaPorIva = new Map();

            let subtotalGeneral = 0;
            let descuentoGeneral = 0;
            let ivaGeneral = 0;

            rows.forEach((row) => {
                const { subtotal, descuento, impuesto } = updateRow(row);
                const rateKey = Number.parseFloat(impuesto || 0);

                subtotalGeneral += subtotal;
                descuentoGeneral += descuento;

                const baseNeta = Math.max(0, subtotal - descuento);
                const iva = baseNeta * (rateKey / 100);
                ivaGeneral += iva;

                basePorIva.set(rateKey, (basePorIva.get(rateKey) || 0) + subtotal);
                ivaPorIva.set(rateKey, (ivaPorIva.get(rateKey) || 0) + iva);
            });

            const totalGeneral = subtotalGeneral - descuentoGeneral + ivaGeneral;

            const resumen = form.querySelector('[data-resumen-totales]');
            if (resumen) {
                const subtotalEl = resumen.querySelector('[data-resumen-subtotal]');
                if (subtotalEl) {
                    subtotalEl.textContent = `$ ${formatMoney(subtotalGeneral, 2)}`;
                }

                const descuentoEl = resumen.querySelector('[data-resumen-descuento]');
                if (descuentoEl) {
                    descuentoEl.textContent = `$ ${formatMoney(descuentoGeneral, 2)}`;
                }

                const totalEl = resumen.querySelector('[data-resumen-total]');
                if (totalEl) {
                    totalEl.textContent = `$ ${formatMoney(totalGeneral, 2)}`;
                }

                const rateRows = resumen.querySelectorAll('[data-resumen-tarifa-row]');
                rateRows.forEach((row) => {
                    const rate = Number.parseFloat(row.dataset.resumenTarifaRow || 0);
                    const base = basePorIva.get(rate) || 0;
                    const iva = ivaPorIva.get(rate) || 0;
                    const ivaRow = resumen.querySelector(`[data-resumen-iva-row="${rate}"]`);

                    const baseEl = row.querySelector(`[data-resumen-base="${rate}"]`);
                    if (baseEl) {
                        baseEl.textContent = `$ ${formatMoney(base, 2)}`;
                    }

                    if (ivaRow) {
                        const ivaEl = ivaRow.querySelector(`[data-resumen-iva="${rate}"]`);
                        if (ivaEl) {
                            ivaEl.textContent = `$ ${formatMoney(iva, 2)}`;
                        }
                    }

                    if (base > 0) {
                        row.classList.remove('hidden');
                        ivaRow?.classList.remove('hidden');
                    } else {
                        row.classList.add('hidden');
                        ivaRow?.classList.add('hidden');
                    }
                });
            }

            const hiddenFields = form.querySelectorAll('[data-total-field]');
            hiddenFields.forEach((input) => {
                switch (input.dataset.totalField) {
                    case 'subtotal':
                        input.value = subtotalGeneral.toFixed(2);
                        break;
                    case 'total_descuento':
                        input.value = descuentoGeneral.toFixed(2);
                        break;
                    case 'total_impuesto':
                        input.value = ivaGeneral.toFixed(2);
                        break;
                    case 'total':
                        input.value = totalGeneral.toFixed(2);
                        break;
                    default:
                        break;
                }
            });
        };

        const initForm = (form) => {
            if (form.dataset.detallesJsReady === 'true') {
                return;
            }

            form.dataset.detallesJsReady = 'true';

            const handler = (event) => {
                if (event.target?.matches('[data-detalle-field]')) {
                    updateTotals(form);
                }
            };

            form.addEventListener('input', handler);
            form.addEventListener('change', handler);

            const repeater = form.querySelector('[data-detalles-repeater]');
            if (repeater) {
                const observer = new MutationObserver(() => updateTotals(form));
                observer.observe(repeater, { childList: true, subtree: true });
            }

            updateTotals(form);
        };

        const boot = () => {
            document
                .querySelectorAll('[data-orden-compra-form]')
                .forEach(initForm);
        };

        document.addEventListener('DOMContentLoaded', boot);
        document.addEventListener('livewire:navigated', boot);
    })();
</script>
