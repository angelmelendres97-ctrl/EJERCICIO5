@php
    $subtotalGeneral = $subtotalGeneral ?? 0;
    $descuentoGeneral = $descuentoGeneral ?? 0;
    $totalGeneral = $totalGeneral ?? 0;
@endphp

<div
    x-data="{
        subtotalGeneral: {{ json_encode((float) $subtotalGeneral) }},
        descuentoGeneral: {{ json_encode((float) $descuentoGeneral) }},
        totalGeneral: {{ json_encode((float) $totalGeneral) }},
        tarifas: [],
        basePorIva: {},
        ivaPorIva: {},
        parse(value) {
            const normalized = String(value ?? '').replace(/,/g, '.').trim();
            const parsed = Number.parseFloat(normalized);
            return Number.isFinite(parsed) ? parsed : 0;
        },
        formatMoney(value) {
            return Number(value || 0).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },
        fmtRate(rate) {
            const formatted = Number(rate || 0).toFixed(2);
            return formatted.replace(/\.00$/, '').replace(/(\.\d*[1-9])0+$/, '$1');
        },
        recalculate() {
            const rows = Array.from(document.querySelectorAll('[data-oc-detalle-row="true"]'));
            const basePorIva = {};
            const descPorIva = {};
            const ivaPorIva = {};

            rows.forEach((row) => {
                const cantidad = this.parse(row.querySelector('[data-detalle-cantidad="true"]')?.value);
                const costo = this.parse(row.querySelector('[data-detalle-costo="true"]')?.value);
                const descuento = this.parse(row.querySelector('[data-detalle-descuento="true"]')?.value);
                const impuesto = this.parse(row.querySelector('[data-detalle-impuesto="true"]')?.value);

                const key = String(impuesto);
                const subtotal = cantidad * costo;
                const baseNeta = Math.max(0, subtotal - descuento);
                const iva = baseNeta * (impuesto / 100);

                basePorIva[key] = (basePorIva[key] || 0) + subtotal;
                descPorIva[key] = (descPorIva[key] || 0) + descuento;
                ivaPorIva[key] = (ivaPorIva[key] || 0) + iva;
            });

            const tarifasSet = Object.keys(basePorIva)
                .map((rate) => Number.parseFloat(rate))
                .filter((rate) => Number.isFinite(rate) && ((basePorIva[String(rate)] || 0) > 0));

            const preferredOrder = [15, 0, 5, 8, 18];
            const existingPreferred = preferredOrder.filter((rate) => tarifasSet.includes(rate));
            const others = tarifasSet.filter((rate) => !preferredOrder.includes(rate)).sort((a, b) => a - b);

            this.basePorIva = basePorIva;
            this.ivaPorIva = ivaPorIva;
            this.tarifas = [...existingPreferred, ...others];
            this.subtotalGeneral = Object.values(basePorIva).reduce((acc, value) => acc + value, 0);
            this.descuentoGeneral = Object.values(descPorIva).reduce((acc, value) => acc + value, 0);
            const ivaGeneral = Object.values(ivaPorIva).reduce((acc, value) => acc + value, 0);
            this.totalGeneral = this.subtotalGeneral - this.descuentoGeneral + ivaGeneral;
        },
    }"
    x-init="recalculate(); window.addEventListener('oc-detalles-updated', () => recalculate())"
    class="w-full"
>
<table class="w-full text-sm">
    <tbody>
        <tr>
            <th class="text-right font-semibold pr-4">Subtotal</th>
            <td class="text-right font-bold w-32">$ <span x-text="formatMoney(subtotalGeneral)"></span></td>
        </tr>
        <tr>
            <th class="text-right font-semibold pr-4">Total Descuento</th>
            <td class="text-right font-bold w-32">$ <span x-text="formatMoney(descuentoGeneral)"></span></td>
        </tr>
        <template x-for="rate in tarifas" :key="`rate-${rate}`">
            <template>
                <tr>
                    <th class="text-right font-semibold pr-4">Tarifa <span x-text="fmtRate(rate)"></span> %</th>
                    <td class="text-right font-bold w-32">$ <span x-text="formatMoney(basePorIva[String(rate)] || 0)"></span></td>
                </tr>
                <tr>
                    <th class="text-right font-semibold pr-4">IVA <span x-text="fmtRate(rate)"></span> %</th>
                    <td class="text-right font-bold w-32">$ <span x-text="formatMoney(ivaPorIva[String(rate)] || 0)"></span></td>
                </tr>
            </template>
        </template>
        <tr class="border-t border-gray-300 dark:border-gray-700">
            <th class="text-right font-extrabold text-lg text-primary-600 pr-4">Total</th>
            <td class="text-right font-extrabold text-xl text-primary-600 w-32">$ <span x-text="formatMoney(totalGeneral)"></span></td>
        </tr>
    </tbody>
</table>
</div>
