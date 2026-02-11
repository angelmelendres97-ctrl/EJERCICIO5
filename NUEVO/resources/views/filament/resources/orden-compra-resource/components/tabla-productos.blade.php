@php
    $rows = $detalles ?? [];
@endphp

<div wire:ignore x-data="ordenCompraProductosTable(@js($rows))" x-init="init()"
    x-on:oc-detalles-sync.window="if ($event.detail?.detalles) mergeServerRows($event.detail.detalles)" class="space-y-4">
    <div class="overflow-x-auto overflow-y-visible border rounded-xl border-gray-200 dark:border-gray-700">
        <table class="w-full text-xs">
            <thead class="bg-gray-100 dark:bg-gray-800">
                <tr>
                    <th class="p-2 w-12"></th>
                    <th class="p-2 w-40">Bodega</th>
                    <th class="p-2 w-28">Producto</th>
                    <th class="p-2 w-28 cursor-pointer select-none" @click="toggleSort('codigo')">
                        <div class="inline-flex items-center gap-1">
                            <span>Código</span>
                            <span class="text-[10px]" x-text="sortIndicator('codigo')"></span>
                        </div>
                    </th>

                    <th class="p-2">Unidad</th>
                    <th class="p-2">Cant.</th>
                    <th class="p-2">Costo</th>
                    <th class="p-2">Desc.</th>
                    <th class="p-2 text-right">Subtotal</th>
                    <th class="p-2">IVA %</th>

                    <th class="p-2 text-right">Total</th>
                    <th class="p-2 min-w-[28rem] cursor-pointer select-none" @click="toggleSort('descripcion')">
                        <div class="inline-flex items-center gap-1">
                            <span>Descripción</span>
                            <span class="text-[10px]" x-text="sortIndicator('descripcion')"></span>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                <template x-if="rows.length === 0">
                    <tr>
                        <td colspan="12" class="p-4 text-center text-gray-500">Sin productos</td>
                    </tr>
                </template>

                <template x-for="row in displayedRows" :key="row._key">
                    <tr class="border-t border-gray-200 dark:border-gray-700 align-top">
                        <td class="p-1 text-center">
                            <button type="button" class="text-danger-600"
                                @click.stop.prevent="removeRowByKey(row._key)">✕</button>
                        </td>

                        <td class="p-1">
                            <select class="fi-select w-40" :key="`bodega-${row._key}-${bodegas.length}`"
                                :value="String(row.id_bodega ?? '')" @focus="ensureBodegasLoaded()"
                                @click="ensureBodegasLoaded()"
                                @change="
                                    row.id_bodega = String($event.target.value || '');
                                    onBodegaChange(row);
                                ">
                                <option value="">Seleccione</option>

                                <template x-for="b in bodegas" :key="b.id">
                                    <option :value="String(b.id)" :selected="sameBodegaId(b.id, row.id_bodega)"
                                        x-text="b.nombre">
                                    </option>
                                </template>
                            </select>

                        </td>

                        <td class="p-1">
                            <input class="fi-input w-28 cursor-pointer" placeholder="Seleccionar producto..."
                                x-model="row.producto_filtro" readonly @click="openProductoModal(row)" />
                        </td>

                        <td class="p-1">
                            <input class="fi-input w-28" :value="codigoItem(row)" readonly>
                        </td>

                        <td class="p-1"><input class="fi-input w-20" x-model="row.unidad" readonly></td>

                        <td class="p-1"><input type="number" step="0.000001" class="fi-input w-20"
                                x-model="row.cantidad" @input="sync()"></td>
                        <td class="p-1"><input type="number" step="0.000001" class="fi-input w-20"
                                x-model="row.costo" @input="sync()"></td>
                        <td class="p-1"><input type="number" step="0.000001" class="fi-input w-20"
                                x-model="row.descuento" @input="sync()"></td>


                        <td class="p-1 text-right" x-text="money4(lineSubtotal(row))"></td>
                        <td class="p-1">
                            <select class="fi-select w-20" x-model="row.impuesto" @change="sync()">
                                <option value="0">0%</option>
                                <option value="5">5%</option>
                                <option value="8">8%</option>
                                <option value="12">12%</option>
                                <option value="15">15%</option>
                                <option value="18">18%</option>
                            </select>
                        </td>
                        <td class="p-1 text-right font-semibold" x-text="money4(lineTotal(row))"></td>
                        <td class="p-1">
                            <input class="fi-input w-64" :value="descripcionColumna(row)" readonly>

                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <div class="flex justify-start">
        <button type="button" class="fi-btn fi-btn-size-sm fi-btn-color-primary font-bold" @click="addRow()">+
            Agregar producto</button>
    </div>

    <div class="ml-auto w-full max-w-md border rounded-xl border-gray-200 dark:border-gray-700 p-3">
        <table class="w-full text-sm">
            <tr>
                <th class="text-right pr-2">Subtotal</th>
                <td class="text-right" x-text="money2(summary.subtotal)"></td>
            </tr>
            <tr>
                <th class="text-right pr-2">Total Descuento</th>
                <td class="text-right" x-text="money2(summary.descuento)"></td>
            </tr>
            <template x-for="t in summary.tarifas" :key="`t-${t}`">
                <tr>
                    <th class="text-right pr-2" x-text="`Tarifa ${fmtRate(t)} %`"></th>
                    <td class="text-right" x-text="money2(summary.baseNetaPorIva[t] || 0)"></td>
                </tr>
            </template>
            <template x-for="t in summary.tarifas" :key="`i-${t}`">
                <tr>
                    <th class="text-right pr-2" x-text="`IVA ${fmtRate(t)} %`"></th>
                    <td class="text-right" x-text="money2(summary.ivaPorIva[t] || 0)"></td>
                </tr>
            </template>
            <tr class="border-t border-gray-200 dark:border-gray-700">
                <th class="text-right pr-2 text-primary-600">Total</th>
                <td class="text-right text-primary-600 font-bold" x-text="money2(summary.total)"></td>
            </tr>
        </table>
    </div>

    <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 p-4 overflow-y-auto"
        x-show="productoModal.open" x-transition.opacity x-cloak
        style="align-items:center; justify-content:center; padding:16px; overflow:auto;">

        <div class="w-full max-w-4xl rounded-xl bg-white shadow-xl dark:bg-gray-900 max-h-[70vh] flex flex-col my-auto"
            @click.outside="closeProductoModal()"
            style="
        width:100%;
        max-width:900px;
        max-height:55vh;
        display:flex;
        flex-direction:column;
        border-radius:14px;
        overflow:hidden;
    ">

            <div class="border-b p-4 dark:border-gray-700">
                <h3 class="text-sm font-semibold">Seleccionar producto</h3>
                <p class="text-xs text-gray-500">Bodega: <span
                        x-text="productoModal.bodegaNombre || 'No seleccionada'"></span></p>
            </div>

            <div class="p-4 space-y-3 overflow-y-auto flex-1">
                <input class="fi-input w-full" placeholder="Buscar por nombre o código..."
                    x-model="productoModal.term" @input.debounce.250ms="searchProductosModal()"
                    @keydown.escape.stop="closeProductoModal()" />

                <div class="max-h-[38vh] overflow-y-auto rounded-lg border dark:border-gray-700">
                    <template x-if="productoModal.loading">
                        <p class="px-3 py-2 text-xs text-gray-500">Cargando...</p>
                    </template>

                    <template x-if="!productoModal.loading && (productoModal.results || []).length === 0">
                        <p class="px-3 py-2 text-xs text-gray-500">No hay coincidencias.</p>
                    </template>

                    <template x-for="p in (productoModal.results || [])" :key="`${p.codigo}-${p.label}`">
                        <button type="button"
                            class="block w-full border-b px-3 py-2 text-left text-xs hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800"
                            @click="selectProductoFromModal(p)">
                            <span x-text="p.label"></span>
                        </button>
                    </template>
                </div>
            </div>

            <div class="flex justify-end border-t p-3 dark:border-gray-700">
                <button type="button" class="fi-btn fi-btn-size-sm" @click="closeProductoModal()">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.ordenCompraProductosTable = window.ordenCompraProductosTable || function(initialRows) {
        return {
            rows: [],
            sort: {
                field: null,
                direction: 'asc',
            },
            bodegas: [],
            productosPorFila: {},
            latestSearchToken: {},
            bodegasContext: null,
            syncTimer: null,
            summary: {
                subtotal: 0,
                descuento: 0,
                impuesto: 0,
                total: 0,
                basePorIva: {},
                baseNetaPorIva: {},
                ivaPorIva: {},
                tarifas: []
            },
            productoModal: {
                open: false,
                rowKey: null,
                term: '',
                results: [],
                loading: false,
                bodegaNombre: '',
            },
            async init() {
                console.log('[OC] init initialRows:', initialRows);

                this.applyServerRows(initialRows || []);

                // ✅ Espera a que bodegas carguen y se haga el mapeo
                await this.loadBodegas(true);

                // ✅ Fuerza un tick para que el DOM replique el valor seleccionado
                this.$nextTick(() => {
                    console.log('[OC] post-loadBodegas rows:', this.rows.map(r => ({
                        key: r._key,
                        id_bodega: r.id_bodega
                    })));
                });

                setTimeout(() => this.ensureBodegasLoaded(), 300);
            },

            get displayedRows() {
                if (!this.sort.field) {
                    return this.rows;
                }

                const factor = this.sort.direction === 'asc' ? 1 : -1;
                return [...this.rows].sort((a, b) => {
                    const left = this.sortValue(a, this.sort.field);
                    const right = this.sortValue(b, this.sort.field);

                    if (left < right) return -1 * factor;
                    if (left > right) return 1 * factor;
                    return 0;
                });
            },
            toggleSort(field) {
                if (this.sort.field !== field) {
                    this.sort = {
                        field,
                        direction: 'asc',
                    };
                    return;
                }

                if (this.sort.direction === 'asc') {
                    this.sort.direction = 'desc';
                    return;
                }

                this.sort = {
                    field: null,
                    direction: 'asc',
                };
            },
            sortIndicator(field) {
                if (this.sort.field !== field) {
                    return '';
                }

                return this.sort.direction === 'asc' ? '▲' : '▼';
            },
            sortValue(row, field) {
                if (field === 'codigo') {
                    return String(row.codigo_producto ?? '').toLocaleLowerCase();
                }

                if (field === 'descripcion') {
                    const descripcion = (row.detalle_pedido && String(row.detalle_pedido).trim() !== '') ? row
                        .detalle_pedido : this.descripcionItem(row);
                    return String(descripcion ?? '').toLocaleLowerCase();
                }

                return '';
            },

            getCurrentContext() {
                return {
                    empresa: String(this.livewire?.get('data.id_empresa') ?? ''),
                    amdgEmpresa: String(this.livewire?.get('data.amdg_id_empresa') ?? ''),
                    amdgSucursal: String(this.livewire?.get('data.amdg_id_sucursal') ?? ''),
                };
            },
            async ensureBodegasLoaded() {
                const ctx = this.getCurrentContext();
                const contextKey = `${ctx.empresa}-${ctx.amdgEmpresa}-${ctx.amdgSucursal}`;
                if (this.bodegasContext !== contextKey || !this.bodegas.length) {
                    await this.loadBodegas(true);
                }
            },
            get livewire() {
                const id = this.$root.closest('[wire\\:id]')?.getAttribute('wire:id');
                return id && window.Livewire ? window.Livewire.find(id) : null;
            },
            n(v) {
                const x = Number(v);
                return Number.isFinite(x) ? x : 0;
            },
            normalizeBodegaId(v) {
                const raw = String(v ?? '').trim();
                if (raw === '') return '';

                // si viene "0003" -> "3"
                const noZeros = raw.replace(/^0+/, '');
                const candidate = noZeros === '' ? '0' : noZeros;

                // si es numérico, lo dejamos numérico normalizado
                const num = Number(candidate);
                return Number.isFinite(num) ? String(num) : raw.toUpperCase();
            },
            sameBodegaId(a, b) {
                return this.normalizeBodegaId(a) === this.normalizeBodegaId(b);
            },
            fmtRate(r) {
                return String(Number(r).toFixed(2)).replace(/\.00$/, '').replace(/(\.[1-9])0$/, '$1')
            },
            money2(v) {
                return '$ ' + this.n(v).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },
            money4(v) {
                return '$' + this.n(v).toLocaleString('en-US', {
                    minimumFractionDigits: 4,
                    maximumFractionDigits: 4
                });
            },
            normalizeRow(row) {
                return {
                    _key: row._key || (crypto.randomUUID ? crypto.randomUUID() : String(Date.now() + Math
                        .random())),
                    pedido_codigo: row.pedido_codigo ?? null,
                    pedido_detalle_id: row.pedido_detalle_id ?? null,
                    es_auxiliar: !!row.es_auxiliar,
                    es_servicio: !!row.es_servicio,
                    detalle: row.detalle ?? null,
                    detalle_pedido: row.detalle_pedido ?? null,
                    producto_auxiliar: row.producto_auxiliar ?? '',
                    producto_servicio: row.producto_servicio ?? '',
                    id_bodega: this.normalizeBodegaId(row.id_bodega ?? ''),
                    bodega: row.bodega ?? '',
                    producto_filtro: '',
                    showResultados: false,
                    highlightedIndex: -1,
                    codigo_producto: row.codigo_producto ?? '',
                    producto: row.producto ?? '',
                    descripcion_auxiliar: row.descripcion_auxiliar ?? '',
                    unidad: row.unidad ?? 'UN',
                    cantidad: this.n(row.cantidad ?? 1),
                    costo: this.n(row.costo ?? 0),
                    descuento: this.n(row.descuento ?? 0),
                    impuesto: String(row.impuesto ?? '0'),
                    codigo_visual: row.codigo_visual ?? '',

                }
            },

            rowImportKey(r) {
                return r.pedido_codigo && r.pedido_detalle_id ? `p:${r.pedido_codigo}:${r.pedido_detalle_id}` :
                    null;
            },
            applyServerRows(serverRows) {
                this.rows = (serverRows || []).map(r => this.normalizeRow(r));
                this.rows.forEach(r => this.fillProductoFiltro(r));
                this.sync();
                console.log('[OC] check id_bodega after apply/load:', this.rows.map(r => ({
                    key: r._key,
                    id_bodega: r.id_bodega,
                    type: typeof r.id_bodega
                })));
            },
            mergeServerRows(serverRows) {
                const incoming = (serverRows || []).map(r => this.normalizeRow(r));
                const existingKeys = new Set(this.rows.map(r => this.rowImportKey(r)).filter(Boolean));
                for (const r of incoming) {
                    const k = this.rowImportKey(r);
                    if (!k || !existingKeys.has(k)) {
                        this.rows.push(r);
                        if (k) existingKeys.add(k);
                    }
                }
                this.rows.forEach(r => this.fillProductoFiltro(r));
                this.ensureBodegasLoaded();
                this.sync();
            },
            async loadBodegas(force = false) {
                if (!this.livewire) return;
                if (!force && this.bodegas.length) return;
                try {
                    this.bodegas = await this.livewire.call('fetchBodegas');
                    console.log('[OC] bodegas raw:', this.bodegas);
                    console.log('[OC] ejemplo bodega keys:', this.bodegas?.[0] ? Object.keys(this.bodegas[0]) :
                        'sin bodegas');
                    const ctx = this.getCurrentContext();
                    this.bodegasContext = `${ctx.empresa}-${ctx.amdgEmpresa}-${ctx.amdgSucursal}`;
                    this.rows.forEach((row) => {
                        if (!row.id_bodega) return;
                        const selected = this.bodegas.find(b => this.sameBodegaId(b.id, row.id_bodega));
                        if (selected) {
                            row.id_bodega = String(selected.id);
                            row.bodega = selected.id ? selected.nombre : String(row.id_bodega);
                        }
                    });
                } catch (_) {
                    this.bodegas = [];
                }
            },
            fillProductoFiltro(row) {
                row.producto_filtro = row.producto ? `${row.producto} (${row.codigo_producto || ''})`.trim() : '';
            },
            async searchProductos(row) {
                await this.ensureBodegasLoaded();
                if (!this.livewire || !row.id_bodega) {
                    this.productosPorFila[row._key] = [];
                    row.showResultados = true;
                    return;
                }
                const token = (this.latestSearchToken[row._key] || 0) + 1;
                this.latestSearchToken[row._key] = token;
                const list = await this.livewire.call('searchProductosPorBodega', row.id_bodega, row
                    .producto_filtro || '');
                if (this.latestSearchToken[row._key] !== token) {
                    return;
                }
                this.productosPorFila[row._key] = list || [];
                row.showResultados = true;
                row.highlightedIndex = (this.productosPorFila[row._key] || []).length ? 0 : -1;
            },
            async openProductoModal(row) {
                await this.ensureBodegasLoaded();
                if (!row.id_bodega) return;
                const selected = this.bodegas.find(b => this.sameBodegaId(b.id, row.id_bodega));
                this.productoModal = {
                    open: true,
                    rowKey: row._key,
                    term: row.producto_filtro || '',
                    results: [],
                    loading: false,
                    bodegaNombre: selected?.nombre || '',
                };
                await this.searchProductosModal();
            },
            closeProductoModal() {
                this.productoModal.open = false;
            },
            async searchProductosModal() {
                if (!this.productoModal.open) return;
                const row = this.rows.find(r => r._key === this.productoModal.rowKey);
                if (!row) return;
                this.productoModal.loading = true;
                row.producto_filtro = this.productoModal.term;
                await this.searchProductos(row);
                this.productoModal.results = this.productosPorFila[row._key] || [];
                this.productoModal.loading = false;
            },
            selectProductoFromModal(p) {
                const row = this.rows.find(r => r._key === this.productoModal.rowKey);
                if (!row) return;
                this.selectProducto(row, p);
                this.closeProductoModal();
            },
            async onBodegaChange(row) {
                row.codigo_producto = '';
                row.producto = '';
                row.unidad = 'UN';
                row.producto_filtro = '';
                row.showResultados = false;
                row.highlightedIndex = -1;
                const selected = this.bodegas.find(b => this.sameBodegaId(b.id, row.id_bodega));
                row.bodega = selected ? selected.nombre : '';
                await this.searchProductos(row);
                this.sync();
            },
            selectProducto(row, p) {
                if (!p) {
                    this.sync();
                    return;
                }
                row.codigo_producto = p.codigo;
                row.producto = p.nombre;
                row.costo = this.n(p.costo);
                row.impuesto = String(Math.round(this.n(p.impuesto)));
                row.unidad = p.unidad || row.unidad || 'UN';
                row.descripcion_auxiliar = '';
                this.fillProductoFiltro(row);
                row.showResultados = false;
                row.highlightedIndex = -1;
                this.sync();
            },
            selectHighlighted(row) {
                const results = this.productosPorFila[row._key] || [];
                const idx = row.highlightedIndex ?? -1;
                if (idx >= 0 && results[idx]) {
                    this.selectProducto(row, results[idx]);
                }
            },
            highlightNext(row) {
                const results = this.productosPorFila[row._key] || [];
                if (!results.length) return;
                row.showResultados = true;
                row.highlightedIndex = Math.min((row.highlightedIndex ?? -1) + 1, results.length - 1);
            },
            highlightPrev(row) {
                const results = this.productosPorFila[row._key] || [];
                if (!results.length) return;
                row.showResultados = true;
                row.highlightedIndex = Math.max((row.highlightedIndex ?? 0) - 1, 0);
            },
            descripcionItem(row) {
                if (row.es_auxiliar) return row.producto_auxiliar || row.descripcion_auxiliar || row.producto || '';
                return row.producto || '';
            },
            codigoItem(row) {
                const aux = String(row.codigo_visual ?? '').trim();
                if (aux !== '') return aux;

                const cod = String(row.codigo_producto ?? '').trim();
                return cod;
            },

            descripcionColumna(row) {
                const aux = String(row.descripcion_auxiliar ?? '').trim(); // dped_desc_auxiliar
                const det = String(row.detalle_pedido ?? '').trim(); // dped_det_dped

                // ✅ requerido: "aux - det"
                if (aux !== '' && det !== '') return `${aux} - ${det}`;

                // fallback
                if (aux !== '') return aux;
                if (det !== '') return det;

                // último fallback: nombre del producto / auxiliar
                return this.descripcionItem(row);
            },

            addRow() {
                this.rows.push(this.normalizeRow({}));
                this.sync(true);
            },
            removeRowByKey(key) {
                const index = this.rows.findIndex(r => r._key === key);
                if (index === -1) return;
                this.rows.splice(index, 1);
                this.sync(true);
            },
            lineSubtotal(r) {
                return this.n(r.cantidad) * this.n(r.costo);
            },
            lineTotal(r) {
                const base = Math.max(0, this.lineSubtotal(r) - this.n(r.descuento));
                return base + (base * (this.n(r.impuesto) / 100));
            },
            persistState(payload, descuento, impuesto) {
                if (!this.livewire) return;

                this.livewire.set('data.detalles', payload, false);
                this.livewire.set('data.subtotal', this.summary.subtotal.toFixed(2), false);
                this.livewire.set('data.total_descuento', descuento.toFixed(2), false);
                this.livewire.set('data.total_impuesto', impuesto.toFixed(2), false);
                this.livewire.set('data.total', this.summary.total.toFixed(2), false);
                this.livewire.set('data.resumen_totales', this.summary, false);
            },
            sync(immediate = false) {
                const basePorIva = {},
                    descPorIva = {};
                let subtotal = 0,
                    descuento = 0;

                const roundMoney = (value) => Math.round((Number(value) + Number.EPSILON) * 100) / 100;

                for (const r of this.rows) {
                    const rate = this.n(r.impuesto);
                    const k = String(rate);
                    const base = this.lineSubtotal(r);
                    const desc = this.n(r.descuento);

                    subtotal += base;
                    descuento += desc;
                    basePorIva[k] = (basePorIva[k] || 0) + base;
                    descPorIva[k] = (descPorIva[k] || 0) + desc;
                }

                const baseNetaPorIva = {};
                const ivaPorIva = {};
                for (const [k, base] of Object.entries(basePorIva)) {
                    const rate = this.n(k);
                    const desc = this.n(descPorIva[k] || 0);
                    const netRounded = roundMoney(Math.max(0, this.n(base) - desc));
                    baseNetaPorIva[k] = netRounded;
                    ivaPorIva[k] = roundMoney(netRounded * (rate / 100));
                }

                const impuesto = roundMoney(Object.values(ivaPorIva).reduce((acc, val) => acc + this.n(val), 0));
                subtotal = roundMoney(subtotal);
                descuento = roundMoney(descuento);

                const present = Object.keys(basePorIva).filter(k => Math.round((basePorIva[k] || 0) * 1e6) / 1e6 >
                    0).map(Number);
                const preferred = [15, 0, 5, 8, 18];
                const tarifas = [...preferred.filter(x => present.includes(x)), ...present.filter(x => !preferred
                    .includes(x)).sort((a, b) => a - b)];
                this.summary = {
                    subtotal,
                    descuento,
                    impuesto,
                    total: roundMoney(subtotal - descuento + impuesto),
                    basePorIva,
                    baseNetaPorIva,
                    ivaPorIva,
                    tarifas
                };

                const payload = this.rows.map(({
                    _key,
                    producto_filtro,
                    ...r
                }) => {
                    const base = Math.max(0, this.lineSubtotal(r) - this.n(r.descuento));
                    return {
                        ...r,
                        id_bodega: r.id_bodega,
                        bodega: r.bodega || String(r.id_bodega || ''),
                        valor_impuesto: (base * (this.n(r.impuesto) / 100)).toFixed(6)
                    };
                });

                clearTimeout(this.syncTimer);

                if (immediate) {
                    this.persistState(payload, descuento, impuesto);
                    return;
                }

                this.syncTimer = setTimeout(() => {
                    this.persistState(payload, descuento, impuesto);
                }, 40);
            }
        }
    }
</script>
