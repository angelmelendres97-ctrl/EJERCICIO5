@php
    $fmtRate = fn($rate) => rtrim(rtrim(number_format((float) $rate, 2, '.', ''), '0'), '.');
    $subtotalGeneral = $subtotalGeneral ?? 0;
    $descuentoGeneral = $descuentoGeneral ?? 0;
    $totalGeneral = $totalGeneral ?? 0;
    $tarifasDisponibles = [15, 0, 5, 8, 18];
@endphp

<div data-oc-resumen="true">
    <table class="w-full text-sm">
        <tbody>
            <tr>
                <th class="text-right font-semibold pr-4">Subtotal</th>
                <td class="text-right font-bold w-32">
                    <span data-oc-resumen-subtotal>$ {{ number_format($subtotalGeneral, 2, '.', ',') }}</span>
                </td>
            </tr>
            <tr>
                <th class="text-right font-semibold pr-4">Total Descuento</th>
                <td class="text-right font-bold w-32">
                    <span data-oc-resumen-descuento>$ {{ number_format($descuentoGeneral, 2, '.', ',') }}</span>
                </td>
            </tr>
            @foreach ($tarifasDisponibles as $rate)
                @php
                    $rateKey = (string) $rate;
                    $base = $basePorIva[$rateKey] ?? 0;
                    $iva = $ivaPorIva[$rateKey] ?? 0;
                    $isVisible = round($base, 6) > 0;
                @endphp
                <tr data-oc-rate-row="{{ $rate }}" data-oc-rate-kind="base" @if (! $isVisible) style="display: none;" @endif>
                    <th class="text-right font-semibold pr-4">Tarifa {{ $fmtRate($rate) }} %</th>
                    <td class="text-right font-bold w-32">
                        <span data-oc-rate-base="{{ $rate }}">$ {{ number_format($base, 2, '.', ',') }}</span>
                    </td>
                </tr>
                <tr data-oc-rate-row="{{ $rate }}" data-oc-rate-kind="iva" @if (! $isVisible) style="display: none;" @endif>
                    <th class="text-right font-semibold pr-4">IVA {{ $fmtRate($rate) }} %</th>
                    <td class="text-right font-bold w-32">
                        <span data-oc-rate-iva="{{ $rate }}">$ {{ number_format($iva, 2, '.', ',') }}</span>
                    </td>
                </tr>
            @endforeach
            <tr class="border-t border-gray-300 dark:border-gray-700">
                <th class="text-right font-extrabold text-lg text-primary-600 pr-4">Total</th>
                <td class="text-right font-extrabold text-xl text-primary-600 w-32">
                    <span data-oc-resumen-total>$ {{ number_format($totalGeneral, 2, '.', ',') }}</span>
                </td>
            </tr>
        </tbody>
    </table>
</div>

@once
    @push('scripts')
        <script>
            (function () {
                const formatNumber = (value, decimals, useGrouping) => {
                    const safeValue = Number.isFinite(value) ? value : 0;
                    if (useGrouping) {
                        return safeValue.toLocaleString('en-US', {
                            minimumFractionDigits: decimals,
                            maximumFractionDigits: decimals,
                        });
                    }
                    return safeValue.toFixed(decimals);
                };

                const parseNumber = (value) => {
                    if (value === null || value === undefined) {
                        return 0;
                    }
                    const cleaned = String(value).replace(/[^0-9.-]/g, '');
                    const parsed = parseFloat(cleaned);
                    return Number.isFinite(parsed) ? parsed : 0;
                };

                const updateResumen = (resumen, container) => {
                    const subtotalEl = container.querySelector('[data-oc-resumen-subtotal]');
                    const descuentoEl = container.querySelector('[data-oc-resumen-descuento]');
                    const totalEl = container.querySelector('[data-oc-resumen-total]');

                    if (subtotalEl) {
                        subtotalEl.textContent = `$ ${formatNumber(resumen.subtotalGeneral, 2, true)}`;
                    }
                    if (descuentoEl) {
                        descuentoEl.textContent = `$ ${formatNumber(resumen.descuentoGeneral, 2, true)}`;
                    }
                    if (totalEl) {
                        totalEl.textContent = `$ ${formatNumber(resumen.totalGeneral, 2, true)}`;
                    }

                    const rates = resumen.tarifasDisponibles;
                    rates.forEach((rate) => {
                        const base = resumen.basePorIva[rate] ?? 0;
                        const iva = resumen.ivaPorIva[rate] ?? 0;
                        const visible = Math.round(base * 1e6) / 1e6 > 0;

                        const baseRow = container.querySelector(`[data-oc-rate-row="${rate}"][data-oc-rate-kind="base"]`);
                        const ivaRow = container.querySelector(`[data-oc-rate-row="${rate}"][data-oc-rate-kind="iva"]`);
                        if (baseRow) {
                            baseRow.style.display = visible ? '' : 'none';
                        }
                        if (ivaRow) {
                            ivaRow.style.display = visible ? '' : 'none';
                        }

                        const baseEl = container.querySelector(`[data-oc-rate-base="${rate}"]`);
                        const ivaEl = container.querySelector(`[data-oc-rate-iva="${rate}"]`);
                        if (baseEl) {
                            baseEl.textContent = `$ ${formatNumber(base, 2, true)}`;
                        }
                        if (ivaEl) {
                            ivaEl.textContent = `$ ${formatNumber(iva, 2, true)}`;
                        }
                    });
                };

                const calculateResumen = (items, rateOrder) => {
                    const basePorIva = {};
                    const ivaPorIva = {};
                    const descPorIva = {};

                    items.forEach((item) => {
                        const cantidad = parseNumber(item.cantidad);
                        const costo = parseNumber(item.costo);
                        const descuento = parseNumber(item.descuento);
                        const rate = parseNumber(item.impuesto);
                        const rateKey = String(rate);
                        const base = cantidad * costo;

                        basePorIva[rateKey] = (basePorIva[rateKey] ?? 0) + base;
                        descPorIva[rateKey] = (descPorIva[rateKey] ?? 0) + descuento;

                        const baseNeta = Math.max(0, base - descuento);
                        ivaPorIva[rateKey] = (ivaPorIva[rateKey] ?? 0) + (baseNeta * (rate / 100));
                    });

                    const subtotalGeneral = Object.values(basePorIva).reduce((acc, val) => acc + val, 0);
                    const descuentoGeneral = Object.values(descPorIva).reduce((acc, val) => acc + val, 0);
                    const ivaGeneral = Object.values(ivaPorIva).reduce((acc, val) => acc + val, 0);
                    const totalGeneral = subtotalGeneral - descuentoGeneral + ivaGeneral;

                    return {
                        basePorIva,
                        ivaPorIva,
                        subtotalGeneral,
                        descuentoGeneral,
                        ivaGeneral,
                        totalGeneral,
                        tarifasDisponibles: rateOrder,
                    };
                };

                const readItems = (form) => {
                    const items = [];
                    const repeaterItems = form.querySelectorAll('[data-repeater-item]');

                    repeaterItems.forEach((item) => {
                        const cantidad = item.querySelector('[data-oc-field="cantidad"]')?.value ?? 0;
                        const costo = item.querySelector('[data-oc-field="costo"]')?.value ?? 0;
                        const descuento = item.querySelector('[data-oc-field="descuento"]')?.value ?? 0;
                        const impuesto = item.querySelector('[data-oc-field="impuesto"]')?.value ?? 0;

                        items.push({
                            item,
                            cantidad,
                            costo,
                            descuento,
                            impuesto,
                        });
                    });

                    return items;
                };

                const updateLineTotals = (items) => {
                    items.forEach((entry) => {
                        const { item } = entry;
                        const cantidad = parseNumber(entry.cantidad);
                        const costo = parseNumber(entry.costo);
                        const descuento = parseNumber(entry.descuento);
                        const impuesto = parseNumber(entry.impuesto);
                        const subtotal = cantidad * costo;
                        const iva = subtotal * (impuesto / 100);
                        const total = subtotal + iva - descuento;

                        const subtotalEl = item.querySelector('[data-oc-subtotal] [data-oc-subtotal-value]');
                        const totalEl = item.querySelector('[data-oc-total-linea] [data-oc-total-linea-value]');

                        if (subtotalEl) {
                            subtotalEl.textContent = `$${formatNumber(subtotal, 4, false)}`;
                        }
                        if (totalEl) {
                            totalEl.textContent = `$${formatNumber(total, 4, false)}`;
                        }
                    });
                };

                const updateHiddenTotals = (form, resumen) => {
                    const subtotalInput = form.querySelector('[data-oc-hidden="subtotal"]');
                    const descuentoInput = form.querySelector('[data-oc-hidden="total_descuento"]');
                    const ivaInput = form.querySelector('[data-oc-hidden="total_impuesto"]');
                    const totalInput = form.querySelector('[data-oc-hidden="total"]');

                    if (subtotalInput) {
                        subtotalInput.value = formatNumber(resumen.subtotalGeneral, 2, false);
                    }
                    if (descuentoInput) {
                        descuentoInput.value = formatNumber(resumen.descuentoGeneral, 2, false);
                    }
                    if (ivaInput) {
                        ivaInput.value = formatNumber(resumen.ivaGeneral, 2, false);
                    }
                    if (totalInput) {
                        totalInput.value = formatNumber(resumen.totalGeneral, 2, false);
                    }
                };

                const init = () => {
                    const form = document.querySelector('[data-orden-compra-form]');
                    const resumenContainer = document.querySelector('[data-oc-resumen="true"]');
                    if (!form || !resumenContainer) {
                        return;
                    }

                    if (form.dataset.ocInitialized === 'true') {
                        return;
                    }
                    form.dataset.ocInitialized = 'true';

                    const rateOrder = [15, 0, 5, 8, 18];
                    const scheduleUpdate = (() => {
                        let raf = null;
                        return () => {
                            if (raf) {
                                return;
                            }
                            raf = requestAnimationFrame(() => {
                                raf = null;
                                const items = readItems(form);
                                updateLineTotals(items);
                                const resumen = calculateResumen(items, rateOrder);
                                updateResumen(resumen, resumenContainer);
                                updateHiddenTotals(form, resumen);
                            });
                        };
                    })();

                    scheduleUpdate();

                    form.addEventListener('input', (event) => {
                        if (event.target?.matches('[data-oc-field]')) {
                            scheduleUpdate();
                        }
                    });

                    form.addEventListener('change', (event) => {
                        if (event.target?.matches('[data-oc-field]')) {
                            scheduleUpdate();
                        }
                    });

                    const observer = new MutationObserver(() => scheduleUpdate());
                    observer.observe(form, { childList: true, subtree: true });
                };

                document.addEventListener('DOMContentLoaded', init);
                document.addEventListener('livewire:navigated', init);
            })();
        </script>
    @endpush
@endonce
