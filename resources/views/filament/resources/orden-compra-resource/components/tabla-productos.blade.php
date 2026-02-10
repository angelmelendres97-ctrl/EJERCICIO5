@php
    $rows = $detalles ?? [];
@endphp

<div x-data="ordenCompraProductosTable(@js($rows))" x-init="init()" x-on:oc-detalles-sync.window="if ($event.detail?.detalles) applyServerRows($event.detail.detalles)" class="space-y-4">
    <div class="flex justify-end">
        <button type="button" class="fi-btn fi-btn-size-sm fi-btn-color-primary" @click="addRow()">Agregar producto</button>
    </div>

    <div class="overflow-x-auto border rounded-xl border-gray-200 dark:border-gray-700">
        <table class="w-full text-xs">
            <thead class="bg-gray-100 dark:bg-gray-800">
                <tr>
                    <th class="p-2">Bodega</th>
                    <th class="p-2">Producto</th>
                    <th class="p-2">Código</th>
                    <th class="p-2">Descripción</th>
                    <th class="p-2">Unidad</th>
                    <th class="p-2">Detalle pedido / observación</th>
                    <th class="p-2">Cant.</th>
                    <th class="p-2">Costo</th>
                    <th class="p-2">Desc.</th>
                    <th class="p-2">IVA %</th>
                    <th class="p-2 text-right">Subtotal</th>
                    <th class="p-2 text-right">Total</th>
                    <th class="p-2"></th>
                </tr>
            </thead>
            <tbody>
                <template x-if="rows.length === 0">
                    <tr><td colspan="13" class="p-4 text-center text-gray-500">Sin productos</td></tr>
                </template>
                <template x-for="(row, idx) in rows" :key="row._key">
                    <tr class="border-t border-gray-200 dark:border-gray-700 align-top">
                        <td class="p-1 min-w-52">
                            <input class="fi-input" list="oc-bodegas-list" x-model="row.bodega_label" @focus="loadBodegas()" @change="applyBodegaByLabel(row)">
                            <datalist id="oc-bodegas-list">
                                <template x-for="b in bodegas" :key="b.id"><option :value="b.nombre"></option></template>
                            </datalist>
                        </td>
                        <td class="p-1 min-w-64">
                            <input class="fi-input" :list="`oc-products-${row._key}`" x-model="row.producto_busqueda" @input.debounce.250ms="searchProductos(row)">
                            <datalist :id="`oc-products-${row._key}`">
                                <template x-for="p in (productosPorFila[row._key] || [])" :key="`${p.codigo}-${p.label}`"><option :value="p.label"></option></template>
                            </datalist>
                        </td>
                        <td class="p-1"><input class="fi-input w-28" x-model="row.codigo_producto" @input="sync()"></td>
                        <td class="p-1"><input class="fi-input min-w-56" x-model="row.producto" @input="sync()"></td>
                        <td class="p-1"><input class="fi-input w-16" x-model="row.unidad" @input="sync()"></td>
                        <td class="p-1">
                            <textarea class="fi-input min-w-64" rows="2" x-model="row.detalle_pedido" @input="sync()"></textarea>
                            <div class="text-[10px] text-gray-500 mt-1" x-show="row.es_auxiliar || row.es_servicio" x-text="(row.producto_auxiliar || row.producto_servicio || '')"></div>
                        </td>
                        <td class="p-1"><input type="number" step="0.000001" class="fi-input w-20" x-model="row.cantidad" @input="sync()"></td>
                        <td class="p-1"><input type="number" step="0.000001" class="fi-input w-24" x-model="row.costo" @input="sync()"></td>
                        <td class="p-1"><input type="number" step="0.000001" class="fi-input w-20" x-model="row.descuento" @input="sync()"></td>
                        <td class="p-1">
                            <select class="fi-select w-20" x-model="row.impuesto" @change="sync()">
                                <option value="0">0%</option><option value="5">5%</option><option value="8">8%</option><option value="15">15%</option><option value="18">18%</option>
                            </select>
                        </td>
                        <td class="p-1 text-right" x-text="money(lineSubtotal(row))"></td>
                        <td class="p-1 text-right font-semibold" x-text="money(lineTotal(row))"></td>
                        <td class="p-1"><button type="button" class="text-danger-600" @click="removeRow(idx)">✕</button></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <div class="ml-auto w-full max-w-sm border rounded-xl border-gray-200 dark:border-gray-700 p-3">
        <table class="w-full text-sm">
            <tr><th class="text-right pr-2">Subtotal</th><td class="text-right" x-text="money(summary.subtotal)"></td></tr>
            <tr><th class="text-right pr-2">Descuento</th><td class="text-right" x-text="money(summary.descuento)"></td></tr>
            <tr><th class="text-right pr-2">Impuesto</th><td class="text-right" x-text="money(summary.impuesto)"></td></tr>
            <tr class="border-t border-gray-200 dark:border-gray-700"><th class="text-right pr-2 text-primary-600">Total</th><td class="text-right text-primary-600 font-bold" x-text="money(summary.total)"></td></tr>
        </table>
    </div>
</div>

<script>
window.ordenCompraProductosTable = window.ordenCompraProductosTable || function (initialRows) {
    return {
        rows: [],
        bodegas: [],
        productosPorFila: {},
        summary: { subtotal: 0, descuento: 0, impuesto: 0, total: 0 },
        init() { this.applyServerRows(initialRows || []); this.loadBodegas(); },
        get livewire() {
            const componentId = this.$root.closest('[wire\\:id]')?.getAttribute('wire:id');
            return componentId && window.Livewire ? window.Livewire.find(componentId) : null;
        },
        normalizeRow(row) {
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
                bodega: row.bodega ?? '',
                bodega_label: row.bodega && row.id_bodega ? `${row.bodega} (${row.id_bodega})` : String(row.bodega ?? row.id_bodega ?? ''),
                producto_busqueda: row.producto ? `${row.producto} (${row.codigo_producto ?? ''})` : '',
                codigo_producto: row.codigo_producto ?? '',
                producto: row.producto ?? '',
                unidad: row.unidad ?? 'UN',
                cantidad: Number(row.cantidad ?? 1),
                costo: Number(row.costo ?? 0),
                descuento: Number(row.descuento ?? 0),
                impuesto: String(row.impuesto ?? '0'),
            };
        },
        async loadBodegas() {
            if (this.bodegas.length || !this.livewire) return;
            try { this.bodegas = await this.livewire.call('fetchBodegas'); } catch (_) { this.bodegas = []; }
        },
        applyBodegaByLabel(row) {
            const found = this.bodegas.find((b) => b.nombre === row.bodega_label);
            if (found) {
                row.id_bodega = String(found.id);
                row.bodega = found.nombre;
                row.producto_busqueda = '';
            }
            this.sync();
        },
        async searchProductos(row) {
            if (!this.livewire || !row.id_bodega) return;
            const term = row.producto_busqueda || '';
            const list = await this.livewire.call('searchProductosPorBodega', row.id_bodega, term);
            this.productosPorFila[row._key] = list || [];
            const found = (list || []).find((p) => p.label === row.producto_busqueda);
            if (found) {
                row.codigo_producto = found.codigo;
                row.producto = found.nombre;
                row.costo = Number(found.costo || 0);
                row.impuesto = String(Math.round(Number(found.impuesto || 0)));
                if (!row.unidad) row.unidad = found.unidad || 'UN';
                this.sync();
            }
        },
        addRow() { this.rows.push(this.normalizeRow({})); this.sync(); },
        removeRow(index) { this.rows.splice(index, 1); this.sync(); },
        n(v) { const x = Number(v); return Number.isFinite(x) ? x : 0; },
        lineSubtotal(row) { return this.n(row.cantidad) * this.n(row.costo); },
        lineTotal(row) { const base = Math.max(0, this.lineSubtotal(row) - this.n(row.descuento)); return base + (base * (this.n(row.impuesto) / 100)); },
        money(v) { return '$ ' + this.n(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        applyServerRows(serverRows) { this.rows = (serverRows || []).map((r) => this.normalizeRow(r)); this.sync(); },
        sync() {
            const subtotal = this.rows.reduce((a, r) => a + this.lineSubtotal(r), 0);
            const descuento = this.rows.reduce((a, r) => a + this.n(r.descuento), 0);
            const impuesto = this.rows.reduce((a, r) => a + (Math.max(0, this.lineSubtotal(r) - this.n(r.descuento)) * (this.n(r.impuesto) / 100)), 0);
            this.summary = { subtotal, descuento, impuesto, total: subtotal - descuento + impuesto };
            const payload = this.rows.map(({ _key, producto_busqueda, bodega_label, ...r }) => {
                const base = Math.max(0, this.lineSubtotal(r) - this.n(r.descuento));
                return {
                    ...r,
                    id_bodega: this.n(r.id_bodega),
                    bodega: r.bodega || String(r.id_bodega || ''),
                    valor_impuesto: (base * (this.n(r.impuesto) / 100)).toFixed(6),
                };
            });
            if (!this.livewire) return;
            this.livewire.set('data.detalles', payload, false);
            this.livewire.set('data.subtotal', subtotal.toFixed(2), false);
            this.livewire.set('data.total_descuento', descuento.toFixed(2), false);
            this.livewire.set('data.total_impuesto', impuesto.toFixed(2), false);
            this.livewire.set('data.total', (subtotal - descuento + impuesto).toFixed(2), false);
        },
    }
}
</script>
