@php
    $rows = $detalles ?? [];
@endphp

<div x-data="ordenCompraProductosTable(@js($rows))" x-init="init()" x-on:oc-detalles-sync.window="if ($event.detail?.detalles) applyServerRows($event.detail.detalles)" class="space-y-4">
    <div class="overflow-x-auto border rounded-xl border-gray-200 dark:border-gray-700">
        <table class="w-full text-xs">
            <thead class="bg-gray-100 dark:bg-gray-800">
                <tr>
                    <th class="p-2 w-10"></th>
                    <th class="p-2 min-w-52">Bodega</th>
                    <th class="p-2 min-w-72">Producto</th>
                    <th class="p-2 w-36">IVA</th>
                    <th class="p-2 w-28">Unidad</th>
                    <th class="p-2 w-40">Código</th>
                    <th class="p-2 min-w-72">Descripción</th>
                    <th class="p-2 w-28">Cant.</th>
                    <th class="p-2 w-32">Costo</th>
                    <th class="p-2 w-32">Desc.</th>
                    <th class="p-2 w-32 text-right">Subtotal</th>
                    <th class="p-2 w-32 text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <template x-if="rows.length === 0">
                    <tr><td colspan="12" class="p-4 text-center text-gray-500">Sin productos</td></tr>
                </template>

                <template x-for="(row, idx) in rows" :key="row._key">
                    <tr class="border-t border-gray-200 dark:border-gray-700 align-top">
                        <td class="p-1 text-center align-middle">
                            <button type="button" class="text-danger-600 font-bold" @click="removeRow(idx)" title="Quitar ítem">✕</button>
                        </td>

                        <td class="p-1">
                            <select class="fi-select w-full" x-model="row.id_bodega" @change="onBodegaChange(row)">
                                <option value="">Seleccione...</option>
                                <template x-for="b in bodegas" :key="b.id">
                                    <option :value="String(b.id)" x-text="b.nombre"></option>
                                </template>
                            </select>
                        </td>

                        <td class="p-1">
                            <div class="space-y-1">
                                <input class="fi-input w-full" type="search" placeholder="Buscar producto..." x-model="row.product_search" @input.debounce.200ms="searchProductos(row)">
                                <select class="fi-select w-full" x-model="row.selected_producto_codigo" @change="applySelectedProduct(row)">
                                    <option value="">Seleccione producto...</option>
                                    <template x-for="p in (productosPorFila[row._key] || [])" :key="`${row._key}-${p.codigo}`">
                                        <option :value="p.codigo" x-text="p.label"></option>
                                    </template>
                                </select>
                            </div>
                        </td>

                        <td class="p-1">
                            <select class="fi-select w-full" x-model="row.impuesto" @change="sync()">
                                <option value="0">0%</option>
                                <option value="5">5%</option>
                                <option value="8">8%</option>
                                <option value="15">15%</option>
                                <option value="18">18%</option>
                            </select>
                        </td>

                        <td class="p-1">
                            <input class="fi-input w-full bg-gray-50 dark:bg-gray-900" x-model="row.unidad" readonly>
                        </td>

                        <td class="p-1">
                            <input class="fi-input w-full bg-gray-50 dark:bg-gray-900" x-model="row.codigo_producto" readonly>
                        </td>

                        <td class="p-1">
                            <input class="fi-input w-full bg-gray-50 dark:bg-gray-900" :value="descripcionRow(row)" readonly>
                        </td>

                        <td class="p-1">
                            <input type="number" step="0.000001" class="fi-input w-full" x-model="row.cantidad" @input="sync()">
                        </td>

                        <td class="p-1">
                            <input type="number" step="0.000001" class="fi-input w-full" x-model="row.costo" @input="sync()">
                        </td>

                        <td class="p-1">
                            <input type="number" step="0.000001" class="fi-input w-full" x-model="row.descuento" @input="sync()">
                        </td>

                        <td class="p-1 text-right" x-text="money4(lineSubtotal(row))"></td>
                        <td class="p-1 text-right font-semibold" x-text="money4(lineTotal(row))"></td>
                    </tr>
                </template>

                <tr class="border-t border-gray-300 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-900/40">
                    <td colspan="12" class="p-2">
                        <button type="button" class="fi-btn fi-btn-size-sm fi-btn-color-primary" @click="addRow()">+ Agregar nuevo ítem</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="ml-auto w-full max-w-md border rounded-xl border-gray-200 dark:border-gray-700 p-3">
        <table class="w-full text-sm">
            <tr>
                <th class="text-right font-semibold pr-3">Subtotal</th>
                <td class="text-right" x-text="money2(summary.subtotal)"></td>
            </tr>
            <tr>
                <th class="text-right font-semibold pr-3">Total Descuento</th>
                <td class="text-right" x-text="money2(summary.descuento)"></td>
            </tr>
            <template x-for="item in summary.tarifas" :key="`rate-${item.rate}`">
                <template>
                    <tr x-show="item.base > 0">
                        <th class="text-right font-semibold pr-3" x-text="`Tarifa ${formatRate(item.rate)} %`"></th>
                        <td class="text-right" x-text="money2(item.base)"></td>
                    </tr>
                    <tr x-show="item.base > 0">
                        <th class="text-right font-semibold pr-3" x-text="`IVA ${formatRate(item.rate)} %`"></th>
                        <td class="text-right" x-text="money2(item.iva)"></td>
                    </tr>
                </template>
            </template>
            <tr class="border-t border-gray-300 dark:border-gray-700">
                <th class="text-right font-bold pr-3 text-primary-600">Total</th>
                <td class="text-right font-bold text-primary-600" x-text="money2(summary.total)"></td>
            </tr>
        </table>
    </div>
</div>

<script>
window.ordenCompraProductosTable = window.ordenCompraProductosTable || function (initialRows) {
    return {
        rows: [],
        bodegas: [],
        productosPorFila: {},
        summary: { subtotal: 0, descuento: 0, impuesto: 0, total: 0, tarifas: [] },

        init() {
            this.applyServerRows(initialRows || []);
            this.loadBodegas();
            this.sync();
        },

        get livewire() {
            const componentId = this.$root.closest('[wire\\:id]')?.getAttribute('wire:id');
            return componentId && window.Livewire ? window.Livewire.find(componentId) : null;
        },

        normalizeRow(row) {
            const codigo = String(row.codigo_producto ?? '');
            const nombre = String(row.producto ?? '');
            return {
                _key: row._key || (crypto.randomUUID ? crypto.randomUUID() : String(Date.now() + Math.random())),
                pedido_codigo: row.pedido_codigo ?? null,
                pedido_detalle_id: row.pedido_detalle_id ?? null,
                es_auxiliar: !!row.es_auxiliar,
                es_servicio: !!row.es_servicio,
                detalle: row.detalle ?? null,
                detalle_pedido: row.detalle_pedido ?? '',
                producto_auxiliar: row.producto_auxiliar ?? '',
                producto_servicio: row.producto_servicio ?? '',
                id_bodega: String(row.id_bodega ?? ''),
                bodega: String(row.bodega ?? row.id_bodega ?? ''),
                product_search: '',
                selected_producto_codigo: codigo,
                codigo_producto: codigo,
                producto: nombre,
                unidad: String(row.unidad ?? 'UN'),
                cantidad: Number(row.cantidad ?? 1),
                costo: Number(row.costo ?? 0),
                descuento: Number(row.descuento ?? 0),
                impuesto: String(row.impuesto ?? '0'),
            };
        },

        descripcionRow(row) {
            if (row.es_auxiliar) {
                const aux = String(row.producto_auxiliar || '');
                const match = aux.match(/Nombre:\s*([^|]+)/i);
                if (match?.[1]) return match[1].trim();
                return aux || row.producto || '';
            }
            return row.producto || '';
        },

        async loadBodegas() {
            if (this.bodegas.length || !this.livewire) return;
            try {
                this.bodegas = await this.livewire.call('fetchBodegas');
            } catch (_) {
                this.bodegas = [];
            }
        },

        async onBodegaChange(row) {
            const b = this.bodegas.find((x) => String(x.id) === String(row.id_bodega));
            row.bodega = b ? b.nombre : String(row.id_bodega || '');
            row.product_search = '';
            row.selected_producto_codigo = '';
            row.codigo_producto = '';
            row.producto = '';
            row.unidad = 'UN';
            row.costo = 0;
            await this.searchProductos(row);
            this.sync();
        },

        async searchProductos(row) {
            if (!this.livewire || !row.id_bodega) {
                this.productosPorFila[row._key] = [];
                return;
            }

            try {
                const term = row.product_search || '';
                const list = await this.livewire.call('searchProductosPorBodega', row.id_bodega, term);
                this.productosPorFila[row._key] = list || [];

                if (row.selected_producto_codigo && !(list || []).some((p) => String(p.codigo) === String(row.selected_producto_codigo))) {
                    const fallback = await this.livewire.call('searchProductosPorBodega', row.id_bodega, row.codigo_producto || '');
                    this.productosPorFila[row._key] = fallback || this.productosPorFila[row._key];
                }
            } catch (_) {
                this.productosPorFila[row._key] = [];
            }
        },

        applySelectedProduct(row) {
            const list = this.productosPorFila[row._key] || [];
            const found = list.find((p) => String(p.codigo) === String(row.selected_producto_codigo));
            if (!found) {
                this.sync();
                return;
            }

            row.codigo_producto = String(found.codigo || '');
            row.producto = String(found.nombre || '');
            row.unidad = String(found.unidad || 'UN');
            row.costo = Number(found.costo || 0);
            row.impuesto = String(Math.round(Number(found.impuesto || 0)));
            row.es_auxiliar = false;
            row.producto_auxiliar = '';
            this.sync();
        },

        addRow() {
            this.rows.push(this.normalizeRow({}));
            this.sync();
        },

        removeRow(index) {
            this.rows.splice(index, 1);
            this.sync();
        },

        n(v) {
            const x = Number(v);
            return Number.isFinite(x) ? x : 0;
        },

        lineSubtotal(row) {
            return this.n(row.cantidad) * this.n(row.costo);
        },

        lineTotal(row) {
            const subtotal = this.lineSubtotal(row);
            const base = Math.max(0, subtotal - this.n(row.descuento));
            return base + (base * (this.n(row.impuesto) / 100));
        },

        money2(v) {
            return '$ ' + this.n(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        money4(v) {
            return '$ ' + this.n(v).toLocaleString('en-US', { minimumFractionDigits: 4, maximumFractionDigits: 4 });
        },

        formatRate(rate) {
            const n = this.n(rate);
            return Number.isInteger(n) ? String(n) : n.toFixed(2).replace(/\.00$/, '');
        },

        recomputeTarifas() {
            const baseByRate = {};
            const descByRate = {};
            const ivaByRate = {};

            for (const row of this.rows) {
                const rate = this.n(row.impuesto);
                const key = String(rate);
                const subtotal = this.lineSubtotal(row);
                const descuento = this.n(row.descuento);
                const baseNeta = Math.max(0, subtotal - descuento);

                baseByRate[key] = (baseByRate[key] || 0) + subtotal;
                descByRate[key] = (descByRate[key] || 0) + descuento;
                ivaByRate[key] = (ivaByRate[key] || 0) + (baseNeta * (rate / 100));
            }

            const rates = Object.keys(baseByRate).map((x) => this.n(x)).filter((rate) => this.n(baseByRate[String(rate)]) > 0);
            const preferred = [15, 0, 5, 8, 18];
            const ordered = [...preferred.filter((r) => rates.includes(r)), ...rates.filter((r) => !preferred.includes(r)).sort((a, b) => a - b)];

            return ordered.map((rate) => ({
                rate,
                base: this.n(baseByRate[String(rate)] || 0),
                descuento: this.n(descByRate[String(rate)] || 0),
                iva: this.n(ivaByRate[String(rate)] || 0),
            }));
        },

        applyServerRows(serverRows) {
            this.rows = (serverRows || []).map((r) => this.normalizeRow(r));
            this.rows.forEach((row) => {
                if (row.id_bodega && row.codigo_producto) {
                    this.searchProductos(row);
                }
            });
            this.sync();
        },

        sync() {
            const subtotal = this.rows.reduce((a, r) => a + this.lineSubtotal(r), 0);
            const descuento = this.rows.reduce((a, r) => a + this.n(r.descuento), 0);
            const impuesto = this.rows.reduce((a, r) => {
                const subtotalRow = this.lineSubtotal(r);
                const base = Math.max(0, subtotalRow - this.n(r.descuento));
                return a + (base * (this.n(r.impuesto) / 100));
            }, 0);

            const tarifas = this.recomputeTarifas();
            this.summary = {
                subtotal,
                descuento,
                impuesto,
                total: subtotal - descuento + impuesto,
                tarifas,
            };

            const payload = this.rows.map(({ _key, product_search, selected_producto_codigo, ...r }) => {
                const subtotalRow = this.lineSubtotal(r);
                const base = Math.max(0, subtotalRow - this.n(r.descuento));
                const valorImpuesto = base * (this.n(r.impuesto) / 100);

                return {
                    ...r,
                    id_bodega: this.n(r.id_bodega),
                    bodega: r.bodega || String(r.id_bodega || ''),
                    detalle_pedido: null,
                    valor_impuesto: Number(valorImpuesto.toFixed(6)),
                };
            });

            if (!this.livewire) return;

            this.livewire.set('data.detalles', payload, false);
            this.livewire.set('data.subtotal', subtotal.toFixed(2), false);
            this.livewire.set('data.total_descuento', descuento.toFixed(2), false);
            this.livewire.set('data.total_impuesto', impuesto.toFixed(2), false);
            this.livewire.set('data.total', (subtotal - descuento + impuesto).toFixed(2), false);
            this.livewire.set('data.resumen_totales', {
                subtotalGeneral: subtotal,
                descuentoGeneral: descuento,
                ivaGeneral: impuesto,
                totalGeneral: subtotal - descuento + impuesto,
                tarifas,
            }, false);
        },
    }
}
</script>
