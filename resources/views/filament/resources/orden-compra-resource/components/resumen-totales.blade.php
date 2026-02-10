@php
    $fmtRate = fn($rate) => rtrim(rtrim(number_format((float) $rate, 2, '.', ''), '0'), '.');
    $subtotalGeneral = (float) ($subtotalGeneral ?? 0);
    $descuentoGeneral = (float) ($descuentoGeneral ?? 0);
    $totalGeneral = (float) ($totalGeneral ?? 0);
    $ivaGeneral = (float) ($ivaGeneral ?? 0);
@endphp

<div
    x-data='{
        subtotalGeneral: @js($subtotalGeneral),
        descuentoGeneral: @js($descuentoGeneral),
        ivaGeneral: @js($ivaGeneral),
        totalGeneral: @js($totalGeneral),
        basePorIva: @js($basePorIva ?? []),
        ivaPorIva: @js($ivaPorIva ?? []),
        tarifas: @js($tarifas ?? []),
        parse(value) {
            const normalized = String(value ?? "").replace(/,/g, ".").trim();
            const parsed = Number.parseFloat(normalized);
            return Number.isFinite(parsed) ? parsed : 0;
        },
        money(value) {
            return Number(value || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        loadRows() {
            const quantityInputs = document.querySelectorAll("input[name*='detalles'][name$='[cantidad]']");
            const baseByRate = {};
            const ivaByRate = {};
            let subtotal = 0;
            let descuento = 0;
            let iva = 0;

            quantityInputs.forEach((cantidadInput) => {
                const prefix = cantidadInput.name.replace(/\[cantidad\]$/, '');
                const costoInput = document.querySelector(`input[name="${prefix}[costo]"]`);
                const descuentoInput = document.querySelector(`input[name="${prefix}[descuento]"]`);
                const impuestoInput = document.querySelector(`select[name="${prefix}[impuesto]"]`);

                const cantidad = this.parse(cantidadInput?.value);
                const costo = this.parse(costoInput?.value);
                const descuentoItem = this.parse(descuentoInput?.value);
                const tasa = this.parse(impuestoInput?.value);

                const base = cantidad * costo;
                const baseNeta = Math.max(0, base - descuentoItem);
                const ivaItem = baseNeta * (tasa / 100);

                subtotal += base;
                descuento += descuentoItem;
                iva += ivaItem;

                const key = String(tasa);
                baseByRate[key] = (baseByRate[key] || 0) + base;
                ivaByRate[key] = (ivaByRate[key] || 0) + ivaItem;
            });

            const preferred = [15, 0, 5, 8, 18];
            const currentRates = Object.keys(baseByRate)
                .filter((rate) => Math.round((baseByRate[rate] || 0) * 1000000) > 0)
                .map((rate) => Number.parseFloat(rate))
                .filter((rate) => Number.isFinite(rate));
            const merged = [...preferred.filter((r) => currentRates.includes(r)), ...currentRates.filter((r) => !preferred.includes(r)).sort((a, b) => a - b)];

            this.subtotalGeneral = subtotal;
            this.descuentoGeneral = descuento;
            this.ivaGeneral = iva;
            this.totalGeneral = (subtotal - descuento) + iva;
            this.basePorIva = baseByRate;
            this.ivaPorIva = ivaByRate;
            this.tarifas = merged;
        },
        init() {
            this.$nextTick(() => this.loadRows());
            window.addEventListener("oc-detalles-updated", () => this.loadRows());
            document.addEventListener("input", (event) => {
                if (event.target?.name?.includes("detalles")) {
                    this.loadRows();
                }
            });
            document.addEventListener("change", (event) => {
                if (event.target?.name?.includes("detalles")) {
                    this.loadRows();
                }
            });
        }
    }'
>
    <table class="w-full text-sm">
        <tbody>
            <tr>
                <th class="text-right font-semibold pr-4">Subtotal</th>
                <td class="text-right font-bold w-32">$ <span x-text="money(subtotalGeneral)"></span></td>
            </tr>
            <tr>
                <th class="text-right font-semibold pr-4">Total Descuento</th>
                <td class="text-right font-bold w-32">$ <span x-text="money(descuentoGeneral)"></span></td>
            </tr>
            <template x-for="rate in tarifas" :key="rate">
                <template x-if="(basePorIva[String(rate)] || 0) > 0">
                    <tr>
                            <th class="text-right font-semibold pr-4" x-text="`Tarifa ${rate} %`"></th>
                            <td class="text-right font-bold w-32">$ <span x-text="money(basePorIva[String(rate)] || 0)"></span></td>
                        </tr>
                        <tr>
                            <th class="text-right font-semibold pr-4" x-text="`IVA ${rate} %`"></th>
                            <td class="text-right font-bold w-32">$ <span x-text="money(ivaPorIva[String(rate)] || 0)"></span></td>
                        </tr>
                </template>
            </template>
            <tr class="border-t border-gray-300 dark:border-gray-700">
                <th class="text-right font-extrabold text-lg text-primary-600 pr-4">Total</th>
                <td class="text-right font-extrabold text-xl text-primary-600 w-32">$ <span x-text="money(totalGeneral)"></span></td>
            </tr>
        </tbody>
    </table>
</div>
