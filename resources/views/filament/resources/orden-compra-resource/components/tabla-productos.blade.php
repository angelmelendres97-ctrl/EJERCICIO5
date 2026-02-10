@php
    $initialDetalles = $get('detalles') ?? [];
    $rates = $impuestos ?? ['0', '5', '8', '15', '18'];
@endphp

<div
    x-data="ordenCompraTablaProductos({
        initialRows: @js($initialDetalles),
        impuestos: @js($rates),
    })"
    x-init="init()"
    class="space-y-4"
>
    <div class="flex justify-end">
        <x-filament::button size="sm" type="button" color="gray" x-on:click="addRow()">
            Agregar producto
        </x-filament::button>
    </div>

    <div class="overflow-x-auto border border-gray-200 rounded-lg">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-2 py-2 text-left">Bodega</th>
                    <th class="px-2 py-2 text-left">Código</th>
                    <th class="px-2 py-2 text-left">Producto / detalle</th>
                    <th class="px-2 py-2 text-left">Unidad</th>
                    <th class="px-2 py-2 text-right">Cant.</th>
                    <th class="px-2 py-2 text-right">Costo</th>
                    <th class="px-2 py-2 text-right">Desc.</th>
                    <th class="px-2 py-2 text-right">IVA %</th>
                    <th class="px-2 py-2 text-right">Subtotal</th>
                    <th class="px-2 py-2 text-right">Total</th>
                    <th class="px-2 py-2"></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(row, index) in rows" :key="row._key">
                    <tr class="border-t align-top">
                        <td class="p-1"><input class="fi-input block w-32" x-model="row.id_bodega" @input="scheduleSync()" /></td>
                        <td class="p-1"><input class="fi-input block w-32" x-model="row.codigo_producto" @input="scheduleSync()" /></td>
                        <td class="p-1">
                            <input class="fi-input block min-w-64" x-model="row.producto" @input="scheduleSync()" />
                            <input class="fi-input mt-1 block min-w-64" placeholder="Detalle" x-model="row.detalle_pedido" @input="scheduleSync()" />
                        </td>
                        <td class="p-1"><input class="fi-input block w-24" x-model="row.unidad" @input="scheduleSync()" /></td>
                        <td class="p-1"><input class="fi-input block w-24 text-right" type="number" step="0.000001" min="0" x-model.number="row.cantidad" @input="scheduleSync()" /></td>
                        <td class="p-1"><input class="fi-input block w-28 text-right" type="number" step="0.000001" min="0" x-model.number="row.costo" @input="scheduleSync()" /></td>
                        <td class="p-1"><input class="fi-input block w-28 text-right" type="number" step="0.000001" min="0" x-model.number="row.descuento" @input="scheduleSync()" /></td>
                        <td class="p-1">
                            <select class="fi-input block w-20 text-right" x-model="row.impuesto" @change="scheduleSync()">
                                <template x-for="rate in impuestos" :key="rate">
                                    <option :value="String(rate)" x-text="rate + '%'" />
                                </template>
                            </select>
                        </td>
                        <td class="p-1 text-right font-medium" x-text="money(lineSubtotal(row))"></td>
                        <td class="p-1 text-right font-semibold" x-text="money(lineTotal(row))"></td>
                        <td class="p-1"><button type="button" class="text-danger-600" @click="removeRow(index)">Quitar</button></td>
                    </tr>
                </template>
                <tr x-show="rows.length === 0" class="border-t">
                    <td colspan="11" class="p-4 text-center text-gray-500">No hay productos agregados.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="ml-auto w-full max-w-sm border rounded-lg p-3 space-y-1 bg-gray-50">
        <div class="flex justify-between"><span>Subtotal</span><span x-text="money(resumen.subtotal)"></span></div>
        <div class="flex justify-between"><span>Descuento</span><span x-text="money(resumen.descuento)"></span></div>
        <div class="flex justify-between"><span>IVA</span><span x-text="money(resumen.impuesto)"></span></div>
        <div class="flex justify-between text-base font-semibold border-t pt-2"><span>Total</span><span x-text="money(resumen.total)"></span></div>
    </div>
</div>

<script>
    function ordenCompraTablaProductos(config) {
        return {
            rows: [],
            impuestos: config.impuestos || ['0', '5', '8', '15', '18'],
            resumen: { subtotal: 0, descuento: 0, impuesto: 0, total: 0 },
            syncTimer: null,
            init() {
                this.rows = (config.initialRows || []).map((row, idx) => this.normalizeRow(row, idx));
                this.syncToLivewire();
                this.$watch('rows', () => this.scheduleSync(), { deep: true });
                this.$wire.$watch('data.detalles', (value) => {
                    const incoming = Array.isArray(value) ? value : [];
                    if (JSON.stringify(this.cleanRows(this.rows)) === JSON.stringify(this.cleanRows(incoming))) {
                        return;
                    }
                    this.rows = incoming.map((row, idx) => this.normalizeRow(row, idx));
                    this.recalculate();
                });
            },
            addRow() {
                this.rows.push(this.normalizeRow({}, Date.now()));
                this.scheduleSync();
            },
            removeRow(index) {
                this.rows.splice(index, 1);
                this.scheduleSync();
            },
            normalizeRow(row, idx) {
                return {
                    _key: row._key ?? `${Date.now()}-${idx}-${Math.random()}`,
                    id_bodega: row.id_bodega ?? '',
                    codigo_producto: row.codigo_producto ?? '',
                    producto: row.producto ?? '',
                    unidad: row.unidad ?? 'UN',
                    cantidad: Number(row.cantidad ?? 1),
                    costo: Number(row.costo ?? 0),
                    descuento: Number(row.descuento ?? 0),
                    impuesto: String(row.impuesto ?? '0'),
                    detalle: row.detalle ?? null,
                    detalle_pedido: row.detalle_pedido ?? null,
                    pedido_codigo: row.pedido_codigo ?? null,
                    pedido_detalle_id: row.pedido_detalle_id ?? null,
                    es_auxiliar: Boolean(row.es_auxiliar ?? false),
                    es_servicio: Boolean(row.es_servicio ?? false),
                    producto_auxiliar: row.producto_auxiliar ?? null,
                    producto_servicio: row.producto_servicio ?? null,
                };
            },
            lineSubtotal(row) {
                return (Number(row.cantidad) || 0) * (Number(row.costo) || 0);
            },
            lineTotal(row) {
                const subtotal = this.lineSubtotal(row);
                const descuento = Number(row.descuento) || 0;
                const iva = subtotal * ((Number(row.impuesto) || 0) / 100);
                return subtotal - descuento + iva;
            },
            money(value) {
                return `$${Number(value || 0).toFixed(2)}`;
            },
            cleanRows(rows) {
                return (rows || []).map((row) => ({
                    id_bodega: row.id_bodega ?? '',
                    codigo_producto: row.codigo_producto ?? '',
                    producto: row.producto ?? '',
                    unidad: row.unidad ?? 'UN',
                    cantidad: Number(row.cantidad ?? 0),
                    costo: Number(row.costo ?? 0),
                    descuento: Number(row.descuento ?? 0),
                    impuesto: String(row.impuesto ?? '0'),
                    detalle: row.detalle ?? null,
                    detalle_pedido: row.detalle_pedido ?? null,
                    pedido_codigo: row.pedido_codigo ?? null,
                    pedido_detalle_id: row.pedido_detalle_id ?? null,
                    es_auxiliar: Boolean(row.es_auxiliar ?? false),
                    es_servicio: Boolean(row.es_servicio ?? false),
                    producto_auxiliar: row.producto_auxiliar ?? null,
                    producto_servicio: row.producto_servicio ?? null,
                }));
            },
            recalculate() {
                let subtotal = 0;
                let descuento = 0;
                let impuesto = 0;

                this.rows.forEach((row) => {
                    const lineSubtotal = this.lineSubtotal(row);
                    const lineDesc = Number(row.descuento) || 0;
                    const baseNeta = Math.max(0, lineSubtotal - lineDesc);
                    subtotal += lineSubtotal;
                    descuento += lineDesc;
                    impuesto += baseNeta * ((Number(row.impuesto) || 0) / 100);
                });

                this.resumen = {
                    subtotal,
                    descuento,
                    impuesto,
                    total: subtotal - descuento + impuesto,
                };
            },
            scheduleSync() {
                clearTimeout(this.syncTimer);
                this.syncTimer = setTimeout(() => this.syncToLivewire(), 80);
            },
            syncToLivewire() {
                this.recalculate();
                const cleaned = this.cleanRows(this.rows);
                this.$wire.set('data.detalles', cleaned, false);
                this.$wire.set('data.subtotal', this.resumen.subtotal.toFixed(2), false);
                this.$wire.set('data.total_descuento', this.resumen.descuento.toFixed(2), false);
                this.$wire.set('data.total_impuesto', this.resumen.impuesto.toFixed(2), false);
                this.$wire.set('data.total', this.resumen.total.toFixed(2), false);
            },
        }
    }
</script>
