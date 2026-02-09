@php
    $fmtRate = fn($rate) => rtrim(rtrim(number_format((float) $rate, 2, '.', ''), '0'), '.');
    $subtotalGeneral = $subtotalGeneral ?? 0;
    $descuentoGeneral = $descuentoGeneral ?? 0;
    $totalGeneral = $totalGeneral ?? 0;
@endphp

<table class="w-full text-sm" data-oc-summary-table>
    <tbody data-oc-summary-body>
        <tr data-oc-summary-row="subtotal">
            <th class="text-right font-semibold pr-4">Subtotal</th>
            <td class="text-right font-bold w-32" data-oc-summary-value="subtotal">$ {{ number_format($subtotalGeneral, 2, '.', ',') }}</td>
        </tr>
        <tr data-oc-summary-row="descuento">
            <th class="text-right font-semibold pr-4">Total Descuento</th>
            <td class="text-right font-bold w-32" data-oc-summary-value="descuento">$ {{ number_format($descuentoGeneral, 2, '.', ',') }}</td>
        </tr>
        @foreach ($tarifas ?? [] as $rate)
            @php
                $rateKey = (string) $rate;
                $base = $basePorIva[$rateKey] ?? 0;
                $iva = $ivaPorIva[$rateKey] ?? 0;
            @endphp
            @if (round($base, 6) > 0)
                <tr data-oc-summary-rate data-rate="{{ $rateKey }}" data-rate-type="base">
                    <th class="text-right font-semibold pr-4">Tarifa {{ $fmtRate($rate) }} %</th>
                    <td class="text-right font-bold w-32" data-oc-summary-rate-value="base">$ {{ number_format($base, 2, '.', ',') }}</td>
                </tr>
                <tr data-oc-summary-rate data-rate="{{ $rateKey }}" data-rate-type="iva">
                    <th class="text-right font-semibold pr-4">IVA {{ $fmtRate($rate) }} %</th>
                    <td class="text-right font-bold w-32" data-oc-summary-rate-value="iva">$ {{ number_format($iva, 2, '.', ',') }}</td>
                </tr>
            @endif
        @endforeach
        <tr class="border-t border-gray-300 dark:border-gray-700" data-oc-summary-anchor>
            <th class="text-right font-extrabold text-lg text-primary-600 pr-4">Total</th>
            <td class="text-right font-extrabold text-xl text-primary-600 w-32" data-oc-summary-value="total">$ {{ number_format($totalGeneral, 2, '.', ',') }}</td>
        </tr>
    </tbody>
</table>
