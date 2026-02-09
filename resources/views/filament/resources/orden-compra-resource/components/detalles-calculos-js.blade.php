<script>
    document.addEventListener('DOMContentLoaded', () => {
        const scope = document.querySelector('[data-oc-scope="orden-compra"]');

        if (!scope) {
            return;
        }

        const formatNumber = (value, decimals = 2) => {
            const number = Number.isFinite(value) ? value : 0;
            return number.toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            });
        };

        const parseNumber = (value) => {
            if (typeof value === 'number') {
                return value;
            }

            const normalized = String(value ?? '')
                .replace(/[^0-9.-]/g, '')
                .trim();

            return normalized === '' ? 0 : Number.parseFloat(normalized) || 0;
        };

        const setInputValue = (input, value) => {
            if (!input) {
                return;
            }

            input.value = value;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        };

        const updateResumen = (resumen) => {
            const summaryBody = scope.querySelector('[data-oc-summary-body]');
            if (!summaryBody) {
                return;
            }

            const subtotalCell = summaryBody.querySelector('[data-oc-summary-value="subtotal"]');
            const descuentoCell = summaryBody.querySelector('[data-oc-summary-value="descuento"]');
            const totalCell = summaryBody.querySelector('[data-oc-summary-value="total"]');

            if (subtotalCell) {
                subtotalCell.textContent = `$ ${formatNumber(resumen.subtotalGeneral, 2)}`;
            }
            if (descuentoCell) {
                descuentoCell.textContent = `$ ${formatNumber(resumen.descuentoGeneral, 2)}`;
            }
            if (totalCell) {
                totalCell.textContent = `$ ${formatNumber(resumen.totalGeneral, 2)}`;
            }

            const existingRateRows = summaryBody.querySelectorAll('[data-oc-summary-rate]');
            existingRateRows.forEach((row) => row.remove());

            const anchor = summaryBody.querySelector('[data-oc-summary-anchor]');
            if (!anchor) {
                return;
            }

            resumen.tarifas.forEach((rate) => {
                const rateKey = String(rate);
                const base = resumen.basePorIva[rateKey] ?? 0;
                const iva = resumen.ivaPorIva[rateKey] ?? 0;

                if (Math.round(base * 1000000) <= 0) {
                    return;
                }

                const rateLabel = Number(rate).toFixed(2).replace(/\.00$/, '');

                const baseRow = document.createElement('tr');
                baseRow.setAttribute('data-oc-summary-rate', '');
                baseRow.innerHTML = `
                    <th class="text-right font-semibold pr-4">Tarifa ${rateLabel} %</th>
                    <td class="text-right font-bold w-32">$ ${formatNumber(base, 2)}</td>
                `;

                const ivaRow = document.createElement('tr');
                ivaRow.setAttribute('data-oc-summary-rate', '');
                ivaRow.innerHTML = `
                    <th class="text-right font-semibold pr-4">IVA ${rateLabel} %</th>
                    <td class="text-right font-bold w-32">$ ${formatNumber(iva, 2)}</td>
                `;

                summaryBody.insertBefore(baseRow, anchor);
                summaryBody.insertBefore(ivaRow, anchor);
            });
        };

        const recalculate = () => {
            const detalleRows = scope.querySelectorAll('[data-oc-repeater="detalles"] .fi-repeater-item');

            let subtotalGeneral = 0;
            let descuentoGeneral = 0;
            let ivaGeneral = 0;
            const basePorIva = {};
            const ivaPorIva = {};

            detalleRows.forEach((row) => {
                const cantidadInput = row.querySelector('[data-oc-input="cantidad"]');
                const costoInput = row.querySelector('[data-oc-input="costo"]');
                const descuentoInput = row.querySelector('[data-oc-input="descuento"]');
                const impuestoInput = row.querySelector('[data-oc-input="impuesto"]');

                const cantidad = parseNumber(cantidadInput?.value);
                const costo = parseNumber(costoInput?.value);
                const descuento = parseNumber(descuentoInput?.value);
                const impuesto = parseNumber(impuestoInput?.value);

                const subtotal = cantidad * costo;
                const baseNeta = Math.max(0, subtotal - descuento);
                const iva = baseNeta * (impuesto / 100);
                const total = subtotal + iva - descuento;

                subtotalGeneral += subtotal;
                descuentoGeneral += descuento;
                ivaGeneral += iva;

                const rateKey = String(impuesto);
                basePorIva[rateKey] = (basePorIva[rateKey] ?? 0) + subtotal;
                ivaPorIva[rateKey] = (ivaPorIva[rateKey] ?? 0) + iva;

                const subtotalOutput = row.querySelector('[data-oc-output="subtotal"]');
                const totalOutput = row.querySelector('[data-oc-output="total"]');

                if (subtotalOutput) {
                    subtotalOutput.value = `$ ${formatNumber(subtotal, 4)}`;
                }
                if (totalOutput) {
                    totalOutput.value = `$ ${formatNumber(total, 4)}`;
                }
            });

            const totalGeneral = subtotalGeneral - descuentoGeneral + ivaGeneral;

            setInputValue(scope.querySelector('[data-oc-total="subtotal"]'), subtotalGeneral.toFixed(2));
            setInputValue(scope.querySelector('[data-oc-total="descuento"]'), descuentoGeneral.toFixed(2));
            setInputValue(scope.querySelector('[data-oc-total="impuesto"]'), ivaGeneral.toFixed(2));
            setInputValue(scope.querySelector('[data-oc-total="total"]'), totalGeneral.toFixed(2));

            const tarifas = Object.keys(basePorIva)
                .map((rate) => Number.parseFloat(rate))
                .filter((rate) => Number.isFinite(rate))
                .sort((a, b) => a - b);

            const ordenPreferido = [15, 0, 5, 8, 18];
            const tarifasOrdenadas = [
                ...ordenPreferido.filter((rate) => tarifas.includes(rate)),
                ...tarifas.filter((rate) => !ordenPreferido.includes(rate)),
            ];

            updateResumen({
                subtotalGeneral,
                descuentoGeneral,
                ivaGeneral,
                totalGeneral,
                basePorIva,
                ivaPorIva,
                tarifas: tarifasOrdenadas,
            });
        };

        let rafHandle = null;
        const scheduleRecalculate = () => {
            if (rafHandle) {
                return;
            }

            rafHandle = window.requestAnimationFrame(() => {
                rafHandle = null;
                recalculate();
            });
        };

        scope.addEventListener('input', (event) => {
            if (event.target.matches('[data-oc-input]')) {
                scheduleRecalculate();
            }
        });

        scope.addEventListener('change', (event) => {
            if (event.target.matches('[data-oc-input]')) {
                scheduleRecalculate();
            }
        });

        const observer = new MutationObserver(() => {
            scheduleRecalculate();
        });

        observer.observe(scope, {
            childList: true,
            subtree: true,
        });

        if (window.Livewire?.hook) {
            window.Livewire.hook('message.processed', () => {
                scheduleRecalculate();
            });
        }

        scheduleRecalculate();
    });
</script>
