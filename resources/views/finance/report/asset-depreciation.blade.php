@include('finance.report.header', [$title])
@include('finance.report.header-logo')

<style>
@page { margin: 25px; }

table {
    width: 100%;
    border-spacing: 0;
    color: rgba(0, 0, 0, 0.65);
}
table.products {
    font-size: 0.65rem;
}

table.products th {
    /* padding: 0.55rem; */
    background-color: #fafafa;
}

table.products tr th .th-sub-title {
    /* padding: 0.2rem; */
    background-color: #fafafa;
}

table.sub-products th {
    /* padding: 0.4rem; */
    font-size: 0.75rem;
    background-color: #FEFBEA;
    margin-bottom: 10px;
}

table tr.items td {
    font-size: 0.7rem;
    padding: 2px 10px 2px 5px;
    border-bottom: 1px solid #eee;
}

table tr.items  td.nominal {
    text-align: right;
    width: 110px;
}

table tr.total td {
    background-color: #FEFBEA !important;
    border-top: 2px solid #eeeeeeb7;
    font-weight: bold;
    font-size: 0.7rem;
    border-bottom: 2px solid #eeeeeeb7;
    padding: 0.25rem 0.85rem;
}

</style>

<div class="center margin-bottom">
    {{-- <h2 class="title margin-top">{{ $data['title'] }}</h2>
    <h3 class="sub-title margin-bottom">{{ $data['subtitle'] }}</h3> --}}
    <h2 class="title margin-top">{{ $title }}</h2>
    <h3 class="sub-title margin-bottom">{{ $subtitle }}</h3>
</div>


    <div class="margin-top">
        <table class="products">
            <thead>
                <tr>
                    <th rowspan="2">No. </th>
                    <th rowspan="2">Jenis Aktiva</th>
                    {{-- <th rowspan="2">Identity Number</th> --}}
                    {{-- <th rowspan="2">Nama</th> --}}
                    <th rowspan="2">Tgl Perolehan</th>
                    <th colspan="3">Nilai Aktiva</th=>
                    <th rowspan="2">Kelompok</th>
                    {{-- <th rowspan="2">% </th> --}}
                    <th rowspan="2">Penyusutan</th>
                    <th rowspan="2">Akum. Penyusutan</th>
                    <th rowspan="2">Nilai Sisa </th>
                </tr>
                <tr>
                    <th>Jumlah Unit</th>
                    <th>Harga Per Unit</th>
                    <th>Nilai Perolehan</th>
                </tr>
            </thead>
            <tbody>
        @foreach ($data as $key=>$value)
                    <tr>
                        <td align="center" class="th-sub-title"><b>{{ angka_romawi($key + 1) }}</b></td>
                        <td align="left" class="th-sub-title"><b>{{ $value['group'] }}</b></td>
                    </tr>

                    @php
                    $total = 0;
                    $depreciation = 0;
                    $depreciation_amount = 0;
                    $gl = 0;
                    @endphp

                    @foreach ($value['item'] as $key => $value2)
                        @php
                        $total += $value2['total'];
                        $depreciation += $value2['depreciation'];
                        $depreciation_amount += $value2['depreciation_amount'];
                        $gl += $value2['gl'];
                        @endphp
                        <tr class="items">
                            <td></td>
                            <td class="nowrap">- {{ $value2['name'] }}</td>
                            <td class="nowrap center">{{ $value2['date'] }}</td>
                            <td class="center">{{ $value2['qty'] }}</td>
                            <td class="right nowrap">{{ rupiah($value2['price']) }}</td>
                            <td class="right nowrap">{{ rupiah($value2['total']) }}</td>
                            <td class="">{{ $value2['group'] }}</td>
                            {{-- <td class="center">{{ $value2['rate'] }}</td> --}}
                            <td class="right nowrap">{{ rupiah($value2['depreciation']) }}</td>
                            <td class="right nowrap">{{ rupiah($value2['depreciation_amount']) }}</td>
                            <td class="right nowrap">{{ rupiah($value2['gl']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total">
                        <td colspan="5" align="right">J  U  M  L  A  H</td>
                        <td class="right nowrap">{{ rupiah($total) }}</td>
                        <td></td>
                        <td class="right nowrap">{{ rupiah($depreciation) }}</td>
                        <td class="right nowrap">{{ rupiah($depreciation_amount) }}</td>
                        <td class="right nowrap">{{ rupiah($gl) }}</td>
                    </tr>
            {{-- @else
                <table class="sub-products">
                    <tr>
                        <th align="right">{{ $value['name'] }}</th>
                        <th align="right">{{ rupiah($value['total']) }}</th>
                    </tr>
                </table>
            @endif --}}


        @endforeach
    </tbody>
    </table>


    </div>



{{-- @foreach ($data as $key => $value)
<h3>{{ $value['name'] }}</h3>
@if (count($value['body']) > 0)
<table style="width: 100%">
    @foreach ($value['body'] as $key => $value2)
    <tr>
        <td>{{ $value2['name'] }}</td>
        <td align="right">{{ rupiah($value2['total']) }}</td>
    </tr>
    @endforeach
    <tr>
        <td><b>Total {{ $value['name'] }}</b></td>
        <td align="right">{{ rupiah($value['total']) }}</td>
    </tr>
</table>
@endif
@endforeach --}}

@include('finance.report.footer')
