<div class="hidden" aria-hidden="true"></div>

@once
    @push('scripts')
        <script>
            (() => {
                const parseValue = (value) => {
                    if (value === null || value === undefined || value === '') {
                        return 0;
                    }

                    const normalized = String(value).replace(',', '.').replace(/[^0-9.\-]/g, '');
                    const parsed = Number.parseFloat(normalized);

                    return Number.isFinite(parsed) ? parsed : 0;
                };

                const formatMoney = (amount) => `$${amount.toFixed(4)}`;

                const findRow = (field) => field?.closest?.('[x-data*="repeaterItem"]') ?? null;

                const resolveValue = (row, fieldName) => {
                    const input = row?.querySelector?.(`[data-oc-field="${fieldName}"]`);
                    if (input) {
                        return parseValue(input.value);
                    }

                    return 0;
                };

                window.ocActualizarFilaCompra = (field) => {
                    const row = findRow(field);
                    if (!row) {
                        return;
                    }

                    const cantidad = resolveValue(row, 'cantidad');
                    const costo = resolveValue(row, 'costo');
                    const descuento = resolveValue(row, 'descuento');
                    const impuesto = resolveValue(row, 'impuesto');

                    const subtotal = cantidad * costo;
                    const valorIva = subtotal * (impuesto / 100);
                    const total = (subtotal + valorIva) - descuento;

                    const subtotalTarget = row.querySelector('[data-oc-subtotal-display]');
                    if (subtotalTarget) {
                        subtotalTarget.textContent = formatMoney(subtotal);
                    }

                    const totalTarget = row.querySelector('[data-oc-total-display]');
                    if (totalTarget) {
                        totalTarget.textContent = formatMoney(total);
                    }
                };

                const refreshVisibleRows = () => {
                    document
                        .querySelectorAll('[data-oc-field="cantidad"], [data-oc-field="costo"], [data-oc-field="descuento"], [data-oc-field="impuesto"]')
                        .forEach((field) => window.ocActualizarFilaCompra(field));
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', refreshVisibleRows, { once: true });
                } else {
                    refreshVisibleRows();
                }

                document.addEventListener('livewire:navigated', refreshVisibleRows);
            })();
        </script>
    @endpush
@endonce
