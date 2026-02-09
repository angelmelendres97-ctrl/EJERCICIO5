<script>
    (() => {
        if (window.__ordenCompraCalculadoraIniciada) {
            return;
        }

        window.__ordenCompraCalculadoraIniciada = true;

        const preferredRates = [15, 0, 5, 8, 18];
        const formatter = new Intl.NumberFormat('es-EC', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
        const formatterItem = new Intl.NumberFormat('es-EC', {
            minimumFractionDigits: 4,
            maximumFractionDigits: 4,
        });

        const parseValue = (value) => {
            const normalized = String(value ?? '')
                .replace(/[^0-9.-]/g, '')
                .replace(',', '.');
            const parsed = parseFloat(normalized);
            return Number.isFinite(parsed) ? parsed : 0;
        };

        const getFieldValue = (row, field) => {
            const element = row.querySelector(`[data-oc-field="${field}"]`);
            if (!element) {
                return 0;
            }

            if (element.tagName === 'INPUT' || element.tagName === 'SELECT') {
                return parseValue(element.value);
            }

            const input = element.querySelector('input, select');
            return parseValue(input?.value);
        };

        const updateHidden = (name, value) => {
            const element = document.querySelector(`[data-oc-hidden="${name}"]`);
            if (!element) {
                return;
            }

            const input = element.tagName === 'INPUT' ? element : element.querySelector('input');
            if (!input) {
                return;
            }

            const formatted = Number(value).toFixed(2);
            if (input.value !== formatted) {
                input.value = formatted;
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
        };

        const buildRateRows = (basePorIva, ivaPorIva) => {
            const rates = Object.entries(basePorIva)
                .filter(([rate, base]) => Math.round(base * 1e6) / 1e6 > 0)
                .map(([rate]) => Number(rate))
                .filter((rate) => Number.isFinite(rate));

            const orderedRates = [
                ...preferredRates.filter((rate) => rates.includes(rate)),
                ...rates.filter((rate) => !preferredRates.includes(rate)).sort((a, b) => a - b),
            ];

            return orderedRates
                .map((rate) => {
                    const rateKey = String(rate);
                    const base = basePorIva[rateKey] ?? 0;
                    const iva = ivaPorIva[rateKey] ?? 0;
                    const labelRate = formatter.format(rate).replace(/\.00$/, '');

                    return `
                        <tr>
                            <th class="text-right font-semibold pr-4">Tarifa ${labelRate} %</th>
                            <td class="text-right font-bold w-32">$ ${formatter.format(base)}</td>
                        </tr>
                        <tr>
                            <th class="text-right font-semibold pr-4">IVA ${labelRate} %</th>
                            <td class="text-right font-bold w-32">$ ${formatter.format(iva)}</td>
                        </tr>
                    `;
                })
                .join('');
        };

        const updateSummary = (subtotalGeneral, descuentoGeneral, ivaGeneral, totalGeneral, basePorIva, ivaPorIva) => {
            const body = document.querySelector('[data-oc-summary-body]');
            if (!body) {
                return;
            }

            body.innerHTML = `
                <tr>
                    <th class="text-right font-semibold pr-4">Subtotal</th>
                    <td class="text-right font-bold w-32" data-oc-summary-subtotal>$ ${formatter.format(subtotalGeneral)}</td>
                </tr>
                <tr>
                    <th class="text-right font-semibold pr-4">Total Descuento</th>
                    <td class="text-right font-bold w-32" data-oc-summary-descuento>$ ${formatter.format(descuentoGeneral)}</td>
                </tr>
                ${buildRateRows(basePorIva, ivaPorIva)}
                <tr class="border-t border-gray-300 dark:border-gray-700">
                    <th class="text-right font-extrabold text-lg text-primary-600 pr-4">Total</th>
                    <td class="text-right font-extrabold text-xl text-primary-600 w-32" data-oc-summary-total>$ ${formatter.format(totalGeneral)}</td>
                </tr>
            `;
        };

        const recalculate = () => {
            const rows = document.querySelectorAll('[data-repeater-item]');
            const basePorIva = {};
            const descuentoPorIva = {};
            const ivaPorIva = {};

            rows.forEach((row) => {
                const cantidad = getFieldValue(row, 'cantidad');
                const costo = getFieldValue(row, 'costo');
                const descuento = getFieldValue(row, 'descuento');
                const impuesto = getFieldValue(row, 'impuesto');

                const subtotal = cantidad * costo;
                const baseNeta = Math.max(0, subtotal - descuento);
                const iva = baseNeta * (impuesto / 100);
                const total = baseNeta + iva;

                const subtotalEl = row.querySelector('[data-oc-subtotal]');
                if (subtotalEl) {
                    subtotalEl.textContent = `$ ${formatterItem.format(subtotal)}`;
                }

                const totalEl = row.querySelector('[data-oc-total]');
                if (totalEl) {
                    totalEl.textContent = `$ ${formatterItem.format(total)}`;
                }

                const rateKey = String(impuesto || 0);
                basePorIva[rateKey] = (basePorIva[rateKey] ?? 0) + subtotal;
                descuentoPorIva[rateKey] = (descuentoPorIva[rateKey] ?? 0) + descuento;
                ivaPorIva[rateKey] = (ivaPorIva[rateKey] ?? 0) + iva;
            });

            const subtotalGeneral = Object.values(basePorIva).reduce((acc, value) => acc + value, 0);
            const descuentoGeneral = Object.values(descuentoPorIva).reduce((acc, value) => acc + value, 0);
            const ivaGeneral = Object.values(ivaPorIva).reduce((acc, value) => acc + value, 0);
            const totalGeneral = subtotalGeneral - descuentoGeneral + ivaGeneral;

            updateHidden('subtotal', subtotalGeneral);
            updateHidden('total_descuento', descuentoGeneral);
            updateHidden('total_impuesto', ivaGeneral);
            updateHidden('total', totalGeneral);
            updateSummary(subtotalGeneral, descuentoGeneral, ivaGeneral, totalGeneral, basePorIva, ivaPorIva);
        };

        const handleInput = (event) => {
            const target = event.target;
            if (!target) {
                return;
            }

            if (target.closest('[data-repeater-item]')) {
                recalculate();
            }
        };

        document.addEventListener('input', handleInput);
        document.addEventListener('change', handleInput);
        document.addEventListener('submit', () => recalculate(), true);

        document.addEventListener('livewire:init', () => {
            if (window.Livewire?.hook) {
                window.Livewire.hook('message.processed', () => {
                    recalculate();
                });
            }
            recalculate();
        });

        document.addEventListener('DOMContentLoaded', () => {
            recalculate();
        });
    })();
</script>
