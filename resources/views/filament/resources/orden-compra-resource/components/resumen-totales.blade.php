@php
    $fmtRate = fn($rate) => rtrim(rtrim(number_format((float) $rate, 2, '.', ''), '0'), '.');
    $subtotalGeneral = $subtotalGeneral ?? 0;
    $descuentoGeneral = $descuentoGeneral ?? 0;
    $totalGeneral = $totalGeneral ?? 0;
    $tarifas = collect([15, 0, 5, 8, 18])->merge($tarifas ?? [])->unique()->values();
@endphp

<table class="w-full text-sm" data-resumen-totales>
    <tbody>
        <tr>
            <th class="text-right font-semibold pr-4">Subtotal</th>
            <td class="text-right font-bold w-32" data-resumen-subtotal>$ {{ number_format($subtotalGeneral, 2, '.', ',') }}</td>
        </tr>
        <tr>
            <th class="text-right font-semibold pr-4">Total Descuento</th>
            <td class="text-right font-bold w-32" data-resumen-descuento>$ {{ number_format($descuentoGeneral, 2, '.', ',') }}</td>
        </tr>
        @foreach ($tarifas as $rate)
            @php
                $rateKey = (string) $rate;
                $base = $basePorIva[$rateKey] ?? 0;
                $iva = $ivaPorIva[$rateKey] ?? 0;
                $hiddenClass = round($base, 6) > 0 ? '' : 'hidden';
            @endphp
            <tr class="{{ $hiddenClass }}" data-resumen-tarifa-row="{{ $rate }}">
                <th class="text-right font-semibold pr-4">Tarifa {{ $fmtRate($rate) }} %</th>
                <td class="text-right font-bold w-32" data-resumen-base="{{ $rate }}">$ {{ number_format($base, 2, '.', ',') }}</td>
            </tr>
            <tr class="{{ $hiddenClass }}" data-resumen-iva-row="{{ $rate }}">
                <th class="text-right font-semibold pr-4">IVA {{ $fmtRate($rate) }} %</th>
                <td class="text-right font-bold w-32" data-resumen-iva="{{ $rate }}">$ {{ number_format($iva, 2, '.', ',') }}</td>
            </tr>
        @endforeach
        <tr class="border-t border-gray-300 dark:border-gray-700">
            <th class="text-right font-extrabold text-lg text-primary-600 pr-4">Total</th>
            <td class="text-right font-extrabold text-xl text-primary-600 w-32" data-resumen-total>$ {{ number_format($totalGeneral, 2, '.', ',') }}</td>
        </tr>
    </tbody>
</table>
