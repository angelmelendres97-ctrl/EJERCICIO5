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
                    <th class="p-2 min-w-72">Bodega</th>
                    <th class="p-2 w-28">Producto</th>
                    <th class="p-2 w-28">Código</th>
                    <th class="p-2 min-w-[28rem]">Descripción</th>
                    <th class="p-2">Unidad</th>
                    <th class="p-2">Cant.</th>
                    <th class="p-2">Costo</th>
                    <th class="p-2">Desc.</th>
                    <th class="p-2">IVA %</th>
                    <th class="p-2 text-right">Subtotal</th>
                    <th class="p-2 text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <template x-if="rows.length === 0">
                    <tr>
                        <td colspan="12" class="p-4 text-center text-gray-500">Sin productos</td>
                    </tr>
                </template>

                <template x-for="(row, idx) in rows" :key="row._key">
                    <tr class="border-t border-gray-200 dark:border-gray-700 align-top">
                        <td class="p-1 text-center">
                            <button type="button" class="text-danger-600"
                                @click.stop.prevent="removeRow(idx)">✕</button>
                        </td>

                        <td class="p-1">
                            <select class="fi-select w-28" x-model="row.id_bodega" @focus="ensureBodegasLoaded()"
                                @click="ensureBodegasLoaded()" @change="onBodegaChange(row)">
                                <option value="">Seleccione</option>
                                <template x-for="b in bodegas" :key="b.id">
                                    <option :value="String(b.id)" x-text="b.nombre"></option>
                                </template>
                            </select>
                        </td>

                        <td class="p-1">
                            <input class="fi-input w-28 cursor-pointer" placeholder="Seleccionar producto..."
                                x-model="row.producto_filtro" readonly @click="openProductoModal(row)" />
                        </td>

                        <td class="p-1"><input class="fi-input w-28" x-model="row.codigo_producto" readonly></td>
                        <td class="p-1"><input class="fi-input w-64" :value="descripcionItem(row)" readonly></td>
                        <td class="p-1"><input class="fi-input w-20" x-model="row.unidad" readonly></td>

                        <td class="p-1"><input type="number" step="0.000001" class="fi-input w-20"
                                x-model="row.cantidad" @input="sync()"></td>
                        <td class="p-1"><input type="number" step="0.000001" class="fi-input w-20"
                                x-model="row.costo" @input="sync()"></td>
                        <td class="p-1"><input type="number" step="0.000001" class="fi-input w-20"
                                x-model="row.descuento" @input="sync()"></td>
                        <td class="p-1">
                            <select class="fi-select w-20" x-model="row.impuesto" @change="sync()">
                                <option value="0">0%</option>
                                <option value="5">5%</option>
                                <option value="8">8%</option>
                                <option value="15">15%</option>
                                <option value="18">18%</option>
                            </select>
                        </td>

                        <td class="p-1 text-right" x-text="money4(lineSubtotal(row))"></td>
                        <td class="p-1 text-right font-semibold" x-text="money4(lineTotal(row))"></td>
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
                    <td class="text-right" x-text="money2(summary.basePorIva[t] || 0)"></td>
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

    <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 p-4" x-show="productoModal.open"
        x-transition.opacity x-cloak>
        <div class="flex max-h-[85vh] w-full max-w-3xl flex-col overflow-hidden rounded-xl bg-white shadow-xl dark:bg-gray-900"
            @click.outside="closeProductoModal()">
            <div class="border-b p-4 dark:border-gray-700">
                <h3 class="text-sm font-semibold">Seleccionar producto</h3>
                <p class="text-xs text-gray-500">Bodega: <span x-text="productoModal.bodegaNombre || 'No seleccionada'"></span></p>
            </div>

            <div class="p-4 space-y-3">
                <input class="fi-input w-full" placeholder="Buscar por nombre o código..." x-model="productoModal.term"
                    @input.debounce.250ms="searchProductosModal()" @keydown.escape.stop="closeProductoModal()" />

                <div class="min-h-0 max-h-[55vh] overflow-y-auto rounded-lg border dark:border-gray-700">
                    <template x-if="productoModal.loading">
                        <p class="px-3 py-2 text-xs text-gray-500">Cargando...</p>
                    </template>

                    <template x-if="!productoModal.loading && (productoModal.results || []).length === 0">
                        <p class="px-3 py-2 text-xs text-gray-500">No hay coincidencias.</p>
                    </template>

                    <template x-for="p in (productoModal.results || [])" :key="`${p.codigo}-${p.label}`">
                        <button type="button" class="block w-full border-b px-3 py-2 text-left text-xs hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800"
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
            init() {
                this.applyServerRows(initialRows || []);
                this.loadBodegas();
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
            normalizeBodegaId(v) {
                const raw = String(v ?? '').trim();
                if (raw === '') return '';
                const numeric = raw.replace(/^0+/, '');
                return numeric === '' ? '0' : numeric;
            },
            bodegaIdEquals(a, b) {
                const aRaw = String(a ?? '').trim();
                const bRaw = String(b ?? '').trim();
                if (aRaw === '' || bRaw === '') return false;
                if (aRaw === bRaw) return true;
                return this.normalizeBodegaId(aRaw) === this.normalizeBodegaId(bRaw);
            },
            n(v) {
                const x = Number(v);
                return Number.isFinite(x) ? x : 0;
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
                    producto_auxiliar: row.producto_auxiliar ?? '',
                    producto_servicio: row.producto_servicio ?? '',
                    id_bodega: String(row.id_bodega ?? ''),
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
                this.sync();
            },
            async loadBodegas(force = false) {
                if (!this.livewire) return;
                if (!force && this.bodegas.length) return;
                try {
                    this.bodegas = await this.livewire.call('fetchBodegas');
                    const ctx = this.getCurrentContext();
                    this.bodegasContext = `${ctx.empresa}-${ctx.amdgEmpresa}-${ctx.amdgSucursal}`;
                    this.rows.forEach((row) => {
                        if (!row.id_bodega) return;
                        const selected = this.bodegas.find(b => this.bodegaIdEquals(b.id, row.id_bodega));
                        if (selected) {
                            row.id_bodega = String(selected.id);
                            row.bodega = selected.nombre;
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
                const list = await this.livewire.call('searchProductosPorBodega', row.id_bodega, row.producto_filtro || '');
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
                const selected = this.bodegas.find(b => this.bodegaIdEquals(b.id, row.id_bodega));
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
                const selected = this.bodegas.find(b => this.bodegaIdEquals(b.id, row.id_bodega));
                if (selected) {
                    row.id_bodega = String(selected.id);
                }
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
                if (row.es_auxiliar) return row.descripcion_auxiliar || row.producto || '';
                return row.producto || '';
            },
            addRow() {
                this.rows.push(this.normalizeRow({}));
                this.sync();
            },
            removeRow(i) {
                this.rows.splice(i, 1);
                this.sync();
            },
            lineSubtotal(r) {
                return this.n(r.cantidad) * this.n(r.costo);
            },
            lineTotal(r) {
                const base = Math.max(0, this.lineSubtotal(r) - this.n(r.descuento));
                return base + (base * (this.n(r.impuesto) / 100));
            },
            sync() {
                const basePorIva = {},
                    ivaPorIva = {};
                let subtotal = 0,
                    descuento = 0,
                    impuesto = 0;
                for (const r of this.rows) {
                    const rate = this.n(r.impuesto);
                    const k = String(rate);
                    const base = this.lineSubtotal(r);
                    const desc = this.n(r.descuento);
                    const net = Math.max(0, base - desc);
                    const iva = net * (rate / 100);
                    subtotal += base;
                    descuento += desc;
                    impuesto += iva;
                    basePorIva[k] = (basePorIva[k] || 0) + base;
                    ivaPorIva[k] = (ivaPorIva[k] || 0) + iva;
                }
                const present = Object.keys(basePorIva).filter(k => Math.round((basePorIva[k] || 0) * 1e6) / 1e6 >
                    0).map(Number);
                const preferred = [15, 0, 5, 8, 18];
                const tarifas = [...preferred.filter(x => present.includes(x)), ...present.filter(x => !preferred
                    .includes(x)).sort((a, b) => a - b)];
                this.summary = {
                    subtotal,
                    descuento,
                    impuesto,
                    total: subtotal - descuento + impuesto,
                    basePorIva,
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
                        id_bodega: this.n(r.id_bodega),
                        bodega: r.bodega || String(r.id_bodega || ''),
                        valor_impuesto: (base * (this.n(r.impuesto) / 100)).toFixed(6)
                    };
                });
                if (!this.livewire) return;
                clearTimeout(this.syncTimer);
                this.syncTimer = setTimeout(() => {
                    this.livewire.set('data.detalles', payload, false);
                    this.livewire.set('data.subtotal', subtotal.toFixed(2), false);
                    this.livewire.set('data.total_descuento', descuento.toFixed(2), false);
                    this.livewire.set('data.total_impuesto', impuesto.toFixed(2), false);
                    this.livewire.set('data.total', (subtotal - descuento + impuesto).toFixed(2), false);
                    this.livewire.set('data.resumen_totales', this.summary, false);
                }, 40);
            }
        }
    }
</script>
