@php
    $rows = $detalles ?? [];
@endphp

<div
    x-data="ordenCompraProductosTable(@js($rows))"
    x-init="init()"
    x-on:oc-detalles-sync.window="if ($event.detail?.detalles) { applyServerRows($event.detail.detalles) }"
    class="space-y-4"
>
    <div class="flex justify-end">
        <button type="button" class="fi-btn fi-btn-size-sm fi-btn-color-primary" @click="addRow()">Agregar producto</button>
    </div>

    <div class="overflow-x-auto border rounded-xl border-gray-200 dark:border-gray-700">
        <table class="w-full text-xs">
            <thead class="bg-gray-100 dark:bg-gray-800">
                <tr>
                    <th class="p-2">Código</th>
                    <th class="p-2">Descripción</th>
                    <th class="p-2">Bodega</th>
                    <th class="p-2">Unidad</th>
                    <th class="p-2">Detalle</th>
                    <th class="p-2">Aux/Serv</th>
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
                        <td class="p-1 min-w-44">
                            <input class="fi-input w-full" x-model="row.codigo_producto" @input.debounce.300ms="onCodigoInput(idx)" placeholder="Código producto">
                            <template x-if="row.product_matches.length">
                                <div class="mt-1 border rounded-md bg-white dark:bg-gray-900 max-h-40 overflow-auto">
                                    <template x-for="item in row.product_matches" :key="item.codigo + '-' + (item.id_bodega ?? '')">
                                        <button type="button" class="block text-left w-full px-2 py-1 hover:bg-gray-100 dark:hover:bg-gray-800" @click="selectProductMatch(idx, item)">
                                            <span x-text="item.codigo"></span> - <span x-text="item.nombre"></span>
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </td>

                        <td class="p-1"><input class="fi-input min-w-56" x-model="row.producto" @input="sync()"></td>

                        <td class="p-1 min-w-40">
                            <select class="fi-select w-full" x-model="row.id_bodega" @change="onBodegaChange(idx)">
                                <option value="">Seleccionar</option>
                                <template x-for="bod in row.bodega_options" :key="String(bod.codigo)">
                                    <option :value="String(bod.codigo)" x-text="bod.nombre"></option>
                                </template>
                            </select>
                        </td>

                        <td class="p-1"><input class="fi-input w-16" x-model="row.unidad" @input="sync()"></td>
                        <td class="p-1"><input class="fi-input min-w-48" x-model="row.detalle_pedido" @input="sync()"></td>

                        <td class="p-1 min-w-56">
                            <template x-if="row.producto_auxiliar"><div class="text-[11px] text-warning-700" x-text="row.producto_auxiliar"></div></template>
                            <template x-if="row.producto_servicio"><div class="text-[11px] text-primary-700" x-text="row.producto_servicio"></div></template>
                        </td>

                        <td class="p-1"><input type="number" step="0.000001" class="fi-input w-20" x-model="row.cantidad" @input="sync()"></td>
                        <td class="p-1"><input type="number" step="0.000001" class="fi-input w-24" x-model="row.costo" @input="sync()"></td>
                        <td class="p-1"><input type="number" step="0.000001" class="fi-input w-20" x-model="row.descuento" @input="sync()"></td>
                        <td class="p-1">
                            <select class="fi-select w-20" x-model="row.impuesto" @change="sync()">
                                <option value="0">0%</option>
                                <option value="5">5%</option>
                                <option value="8">8%</option>
                                <option value="15">15%</option>
                                <option value="18">18%</option>
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
            summary: { subtotal: 0, descuento: 0, impuesto: 0, total: 0 },
            init() {
                this.applyServerRows(initialRows || []);
            },
            normalizeRow(row) {
                return {
                    _key: row._key || (crypto.randomUUID ? crypto.randomUUID() : String(Date.now() + Math.random())),
                    pedido_codigo: row.pedido_codigo ?? null,
                    pedido_detalle_id: row.pedido_detalle_id ?? null,
                    es_auxiliar: !!row.es_auxiliar,
                    es_servicio: !!row.es_servicio,
                    detalle: row.detalle ?? null,
                    codigo_producto: row.codigo_producto ?? '',
                    producto: row.producto ?? '',
                    id_bodega: row.id_bodega !== null && row.id_bodega !== undefined ? String(row.id_bodega) : '',
                    bodega: row.bodega ?? null,
                    bodega_options: row.bodega_options ?? [],
                    unidad: row.unidad ?? 'UN',
                    detalle_pedido: row.detalle_pedido ?? '',
                    producto_auxiliar: row.producto_auxiliar ?? null,
                    producto_servicio: row.producto_servicio ?? null,
                    cantidad: Number(row.cantidad ?? 1),
                    costo: Number(row.costo ?? 0),
                    descuento: Number(row.descuento ?? 0),
                    impuesto: String(row.impuesto ?? '0'),
                    product_matches: [],
                };
            },
            getLivewire() {
                const componentId = this.$root.closest('[wire\\:id]')?.getAttribute('wire:id');
                if (!componentId || !window.Livewire) return null;
                return window.Livewire.find(componentId);
            },
            async callLivewire(method, ...args) {
                const livewire = this.getLivewire();
                if (!livewire || typeof livewire.call !== 'function') return null;
                try {
                    return await livewire.call(method, ...args);
                } catch (_) {
                    return null;
                }
            },
            async hydrateBodegasForRow(index) {
                const row = this.rows[index];
                if (!row || !row.codigo_producto) return;
                const response = await this.callLivewire('obtenerBodegasProducto', row.codigo_producto);
                row.bodega_options = Array.isArray(response) ? response : [];
                if (row.id_bodega && !row.bodega_options.some((x) => String(x.codigo) === String(row.id_bodega))) {
                    row.bodega_options.push({ codigo: String(row.id_bodega), nombre: row.bodega || row.id_bodega });
                }
            },
            addRow() { this.rows.push(this.normalizeRow({})); this.sync(); },
            removeRow(index) { this.rows.splice(index, 1); this.sync(); },
            lineSubtotal(row) { return this.n(row.cantidad) * this.n(row.costo); },
            lineTotal(row) {
                const subtotal = this.lineSubtotal(row);
                const descuento = this.n(row.descuento);
                const base = Math.max(0, subtotal - descuento);
                return base + (base * (this.n(row.impuesto) / 100));
            },
            n(v) { const x = Number(v); return Number.isFinite(x) ? x : 0; },
            money(v) { return '$ ' + this.n(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            async applyServerRows(serverRows) {
                this.rows = (serverRows || []).map((r) => this.normalizeRow(r));
                for (let i = 0; i < this.rows.length; i++) {
                    await this.hydrateBodegasForRow(i);
                }
                this.sync();
            },
            async onCodigoInput(index) {
                const row = this.rows[index];
                if (!row) return;
                this.sync();

                if ((row.codigo_producto || '').length < 2) {
                    row.product_matches = [];
                    return;
                }

                const matches = await this.callLivewire('buscarProductos', row.codigo_producto, row.id_bodega || null);
                row.product_matches = Array.isArray(matches) ? matches : [];
                await this.hydrateBodegasForRow(index);
            },
            async selectProductMatch(index, item) {
                const row = this.rows[index];
                if (!row) return;

                row.codigo_producto = item.codigo ?? row.codigo_producto;
                row.producto = item.nombre ?? row.producto;
                row.product_matches = [];
                await this.hydrateBodegasForRow(index);

                if (row.id_bodega) {
                    await this.onBodegaChange(index);
                }
                this.sync();
            },
            async onBodegaChange(index) {
                const row = this.rows[index];
                if (!row || !row.codigo_producto || !row.id_bodega) {
                    this.sync();
                    return;
                }

                const response = await this.callLivewire('obtenerProductoPorBodega', row.codigo_producto, row.id_bodega);
                if (response) {
                    row.unidad = response.unidad ?? row.unidad;
                    row.costo = this.n(response.costo ?? row.costo);
                    row.impuesto = String(response.impuesto ?? row.impuesto ?? '0');
                    row.producto = response.nombre ?? row.producto;
                    row.bodega = response.bodega_nombre ?? row.bodega;
                }
                this.sync();
            },
            sync() {
                const subtotal = this.rows.reduce((a, r) => a + this.lineSubtotal(r), 0);
                const descuento = this.rows.reduce((a, r) => a + this.n(r.descuento), 0);
                const impuesto = this.rows.reduce((a, r) => {
                    const base = Math.max(0, this.lineSubtotal(r) - this.n(r.descuento));
                    return a + (base * (this.n(r.impuesto) / 100));
                }, 0);
                this.summary = {
                    subtotal,
                    descuento,
                    impuesto,
                    total: subtotal - descuento + impuesto,
                };

                const payload = this.rows.map(({ _key, product_matches, bodega_options, ...r }) => ({
                    ...r,
                    id_bodega: r.id_bodega === '' ? null : r.id_bodega,
                    valor_impuesto: (Math.max(0, this.lineSubtotal(r) - this.n(r.descuento)) * (this.n(r.impuesto) / 100)).toFixed(6),
                }));

                const livewire = this.getLivewire();
                if (!livewire) return;
                livewire.set('data.detalles', payload, false);
                livewire.set('data.subtotal', subtotal.toFixed(2), false);
                livewire.set('data.total_descuento', descuento.toFixed(2), false);
                livewire.set('data.total_impuesto', impuesto.toFixed(2), false);
                livewire.set('data.total', (subtotal - descuento + impuesto).toFixed(2), false);
            },
        }
    }
</script>
