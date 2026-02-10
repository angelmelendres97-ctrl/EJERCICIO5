<div
    x-data="{
        subtotalGeneral: 0,
        descuentoGeneral: 0,
        totalGeneral: 0,
        basePorIva: {},
        ivaPorIva: {},
        tarifas: [],
        parse(value) {
            const normalized = String(value ?? '').replace(/,/g, '.').trim();
            const parsed = Number.parseFloat(normalized);
            return Number.isFinite(parsed) ? parsed : 0;
        },
        money(value) {
            return Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        fmtRate(rate) {
            const n = Number(rate || 0);
            return Number.isInteger(n) ? `${n}` : `${n}`.replace(/\.0+$/, '');
        },
        compute() {
            const rows = Array.from(document.querySelectorAll('[data-oc-item="1"]'));
            const base = {};
            const iva = {};
            let subtotal = 0;
            let descuento = 0;

            rows.forEach((row) => {
                const cantidad = this.parse(row.querySelector('[data-oc-field="cantidad"]')?.value);
                const costo = this.parse(row.querySelector('[data-oc-field="costo"]')?.value);
                const desc = this.parse(row.querySelector('[data-oc-field="descuento"]')?.value);
                const rate = this.parse(row.querySelector('[data-oc-field="impuesto"]')?.value);

                const key = `${rate}`;
                const lineBase = cantidad * costo;
                const baseNeta = Math.max(0, lineBase - desc);
                const lineIva = baseNeta * (rate / 100);

                subtotal += lineBase;
                descuento += desc;
                base[key] = (base[key] || 0) + lineBase;
                iva[key] = (iva[key] || 0) + lineIva;
            });

            const ordered = [15, 0, 5, 8, 18];
            const keys = Object.keys(base)
                .map((rate) => Number(rate))
                .filter((rate) => Number.isFinite(rate) && Math.round((base[`${rate}`] || 0) * 1_000_000) > 0);

            const inPreferred = ordered.filter((rate) => keys.includes(rate));
            const rest = keys.filter((rate) => !ordered.includes(rate)).sort((a, b) => a - b);

            this.basePorIva = base;
            this.ivaPorIva = iva;
            this.tarifas = [...inPreferred, ...rest];
            this.subtotalGeneral = subtotal;
            this.descuentoGeneral = descuento;

            const ivaGeneral = Object.values(iva).reduce((acc, value) => acc + (Number(value) || 0), 0);
            this.totalGeneral = subtotal - descuento + ivaGeneral;
        },
        init() {
            this.compute();
            window.addEventListener('oc-recalculate-summary', () => this.compute());
            this.$nextTick(() => {
                this.compute();
                const observer = new MutationObserver(() => this.compute());
                observer.observe(document.body, { childList: true, subtree: true });
            });
        },
    }"
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
                <template>
                    <tr>
                        <th class="text-right font-semibold pr-4">Tarifa <span x-text="fmtRate(rate)"></span> %</th>
                        <td class="text-right font-bold w-32">$ <span x-text="money(basePorIva[`${rate}`] || 0)"></span></td>
                    </tr>
                    <tr>
                        <th class="text-right font-semibold pr-4">IVA <span x-text="fmtRate(rate)"></span> %</th>
                        <td class="text-right font-bold w-32">$ <span x-text="money(ivaPorIva[`${rate}`] || 0)"></span></td>
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
