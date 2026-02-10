@php
    $subtotalGeneral = (float) ($subtotalGeneral ?? 0);
    $descuentoGeneral = (float) ($descuentoGeneral ?? 0);
    $totalGeneral = (float) ($totalGeneral ?? 0);
    $ivaGeneral = (float) ($ivaGeneral ?? 0);
@endphp

<div
    x-data="{
        subtotalGeneral: {{ $subtotalGeneral }},
        descuentoGeneral: {{ $descuentoGeneral }},
        ivaGeneral: {{ $ivaGeneral }},
        totalGeneral: {{ $totalGeneral }},
        basePorIva: { '0': 0, '5': 0, '8': 0, '15': 0, '18': 0 },
        ivaPorIva: { '0': 0, '5': 0, '8': 0, '15': 0, '18': 0 },
        parse(value) {
            const normalized = String(value ?? '').replace(/,/g, '.').trim();
            const parsed = Number.parseFloat(normalized);
            return Number.isFinite(parsed) ? parsed : 0;
        },
        money(value, decimals = 2) {
            return `$ ${Number(value || 0).toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            })}`;
        },
        recalculate() {
            const rows = Array.from(document.querySelectorAll('[data-detalle-row="true"]'));

            this.subtotalGeneral = 0;
            this.descuentoGeneral = 0;
            this.ivaGeneral = 0;
            this.totalGeneral = 0;
            this.basePorIva = { '0': 0, '5': 0, '8': 0, '15': 0, '18': 0 };
            this.ivaPorIva = { '0': 0, '5': 0, '8': 0, '15': 0, '18': 0 };

            rows.forEach((row) => {
                const cantidad = this.parse(row.querySelector('[data-campo="cantidad"]')?.value);
                const costo = this.parse(row.querySelector('[data-campo="costo"]')?.value);
                const descuento = this.parse(row.querySelector('[data-campo="descuento"]')?.value);
                const impuesto = this.parse(row.querySelector('[data-campo="impuesto"]')?.value);

                const subtotal = cantidad * costo;
                const baseNeta = Math.max(0, subtotal - descuento);
                const iva = baseNeta * (impuesto / 100);
                const rateKey = String(impuesto);

                this.subtotalGeneral += subtotal;
                this.descuentoGeneral += descuento;
                this.ivaGeneral += iva;

                if (this.basePorIva[rateKey] === undefined) {
                    this.basePorIva[rateKey] = 0;
                    this.ivaPorIva[rateKey] = 0;
                }

                this.basePorIva[rateKey] += subtotal;
                this.ivaPorIva[rateKey] += iva;
            });

            this.totalGeneral = (this.subtotalGeneral - this.descuentoGeneral) + this.ivaGeneral;
        },
        orderedRates() {
            const preferred = ['15', '0', '5', '8', '18'];
            const dynamic = Object.keys(this.basePorIva)
                .filter((rate) => Math.round((this.basePorIva[rate] || 0) * 1000000) > 0);

            return [...new Set([...preferred, ...dynamic])]
                .filter((rate) => Math.round((this.basePorIva[rate] || 0) * 1000000) > 0);
        },
    }"
    x-init="setTimeout(() => recalculate(), 0)"
    x-on:input.window.debounce.0ms="recalculate()"
    x-on:change.window="recalculate()"
    x-on:oc-detalles-updated.window.debounce.0ms="recalculate()"
>
    <table class="w-full text-sm">
        <tbody>
            <tr>
                <th class="text-right font-semibold pr-4">Subtotal</th>
                <td class="text-right font-bold w-32" x-text="money(subtotalGeneral)"></td>
            </tr>
            <tr>
                <th class="text-right font-semibold pr-4">Total Descuento</th>
                <td class="text-right font-bold w-32" x-text="money(descuentoGeneral)"></td>
            </tr>

            <template x-for="rate in orderedRates()" :key="rate">
                <template>
                    <tr>
                        <th class="text-right font-semibold pr-4" x-text="`Tarifa ${rate} %`"></th>
                        <td class="text-right font-bold w-32" x-text="money(basePorIva[rate] || 0)"></td>
                    </tr>
                    <tr>
                        <th class="text-right font-semibold pr-4" x-text="`IVA ${rate} %`"></th>
                        <td class="text-right font-bold w-32" x-text="money(ivaPorIva[rate] || 0)"></td>
                    </tr>
                </template>
            </template>

            <tr class="border-t border-gray-300 dark:border-gray-700">
                <th class="text-right font-extrabold text-lg text-primary-600 pr-4">Total</th>
                <td class="text-right font-extrabold text-xl text-primary-600 w-32" x-text="money(totalGeneral)"></td>
            </tr>
        </tbody>
    </table>
</div>
