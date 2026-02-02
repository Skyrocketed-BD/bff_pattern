<!DOCTYPE html>
<html lang="id">

<head>
    <meta http-equiv="colon-Type" colon="text/html; charset=UTF-8" />
    <meta name="viewport"
        colon="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" colon="ie=edge">
    <meta charset="UTF-8">

    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet" href="css/pdf-id.css" type="text/css">

    <title>{{ $title }}</title>
</head>

<body>
    @include('header')

    <main class="container">
        <div class="header">
            <h2 class="title border-bottom">{{ $title }}</h2>
        </div>

        <div class="line-space"></div>
        <div class="line-space"></div>

        <div class="">
            {{-- recipient --}}
            <table style="width:100%">
                <tr>
                    <td style="width:40%;vertical-align: top">
                        <table class="table-title">
                            <tr>
                                <td class="label">Kepada</td>
                                <td class="colon">:</td>
                                <td class="content">
                                    <div class="company-name">{{ $receipent }}</div>
                                    @if ($receipent_address)
                                        <div class="company-name">{{ $receipent_address }}</div>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                    {{-- <td style="width:2%">
                    </td> --}}
                    <td style="width:40%; vertical-align: top">
                        <table class="table-title">
                            <tr>
                                <td class="label">Tanggal Invoice</td>
                                <td class="colon">:</td>
                                <td class="content">{{ $date }}</td>
                            </tr>
                            <tr>
                                <td class="label">No. Invoice</td>
                                <td class="colon">:</td>
                                <td class="content">{{ $invoice_number }}</td>
                            </tr>
                            @if ($receipent_contract)
                                <tr>
                                    <td class="label">No. Kontrak</td>
                                    <td class="colon">:</td>
                                    <td class="content contract-bold">{{ $receipent_contract }}</td>
                                </tr>
                            @endif
                        </table>
                    </td>
                </tr>
            </table>


            {{-- transport vessel --}}
            <div class="line-space"></div>
            @if ($transport_vessel)
                <div class="line-top-bottom-full">
                    <div class="transport-vessel">{{ $transport_vessel }}</div>
                </div>
            @endif


            {{-- shipping instruction dan kadar --}}
            <div class="line-space"></div>
            <table style="width:100%">
                <tr>
                    <td style="width:40%;vertical-align: top">
                        <table class="table-title">
                            @if ($shipping_instruction)
                                <tr>
                                    <td class="label">No SI:</td>
                                    <td class="colon">:</td>
                                    <td class="content">{{ $shipping_instruction }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td class="label">Kuantitas</td>
                                <td class="colon">:</td>
                                <td class="content">{{ $details['cargo'] }} WMT</td>
                            </tr>
                            <tr>
                                <td class="label">Harga</td>
                                <td class="colon">:</td>
                                <td class="content">{{ $details['hpm'] }}</td>
                            </tr>
                            <tr>
                                <td class="label">Kurs</td>
                                <td class="colon">:</td>
                                <td class="content">{{ $details['kurs'] }}</td>
                            </tr>
                            <tr>
                                <td class="label">Harga IDR</td>
                                <td class="colon">:</td>
                                <td class="content">{{ $details['hpm_idr'] }}</td>
                            </tr>
                        </table>
                    </td>
                    <td style="width:10%">
                    </td>
                    <td style="width:40%; vertical-align: top">
                        <table class="table-title">
                            <tr>
                                <td class="label">HMA {{ $details['hma_date'] }}</td>
                                <td class="colon">:</td>
                                <td class="content">{{ $details['hma'] }}</td>
                            </tr>
                            <tr>
                                <td class="label">MC</td>
                                <td class="colon">:</td>
                                <td class="content">{{ $details['mc'] }}%</td>
                            </tr>
                            <tr>
                                <td class="label">Ni</td>
                                <td class="colon">:</td>
                                <td class="content">{{ $details['ni'] }}%</td>
                            </tr>
                            <tr>
                                <td class="label">CF</td>
                                <td class="colon">:</td>
                                <td class="content">{{ $details['cf'] }}%</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            {{-- table detail  --}}
            <div class="line-space"></div>
            <table class="table-title">
                <tr>
                    <td>
                        <table class="table-details">
                            <thead>
                                <tr>
                                    <th class="tengah">Deskripsi</th>
                                    <th class="tengah">Tonase (MT)</th>
                                    <th class="tengah">Harga (Rp/MT)</th>
                                    <th class="tengah" style="width: 150px">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="tengah">Nickel Ore</td>
                                    <td class="tengah">{{ $details['cargo'] }} MT</td>
                                    <td class="tengah">{{ $details['hpm_idr'] }}</td>
                                    <td class="kanan tebal nowrap">{{ $details['price'] }}</td>
                                </tr>
                                @if ($termins)
                                    @if (count($termins['details']) > 0)
                                        @foreach ($termins['details'] as $termin)
                                            <tr>
                                                <td colspan="3" class="kanan  nowrap">{{ $termin['name'] }}</td>
                                                <td class="kanan  nowrap merah">({{ $termin['value_percent'] }})</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                    <tr>
                                        <td colspan="3" class="kanan tebal nowrap">Jumlah Tagihan (DPP)</td>
                                        <td class="kanan tebal nowrap">{{ $termins['dpp'] }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="kanan tebal nowrap">DPP Nilai Lain</td>
                                        <td class="kanan tebal nowrap">{{ $termins['dpp_lain'] }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="kanan tebal nowrap">PPN 12% (dibebaskan)</td>
                                        <td class="kanan tebal nowrap">{{ $termins['ppn_dibebaskan'] }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="kanan nowrap">Potongan PPh Pasal 22 (1.5%)</td>
                                        <td class="kanan merah nowrap">{{ $termins['pph'] }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td colspan="3" class="kanan tebal nowrap lebih-besar">Total Tagihan</td>
                                    <td class="kanan tebal nowrap lebih-besar">{{ $termins['sisa_tagihan'] }}</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="tebal tengah miring">
                                        {{ $terbilang }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>

            {{-- penggunaan deposit --}}
            @if ($pre_payment_type)
                <div class="line-space" style="width: 100%">
                    <table class="table-title">
                        <tr>
                            <td>
                                <table class="table-bank table-history" style="border: 1px solid #111">
                                    <tbody>
                                        <tr>
                                            <td class="tebal merah">{{ $pre_payment_type }}</td>
                                            <td class="kanan tebal merah nowrap">{{ $pre_payment }}</td>
                                        </tr>
                                        <tr>
                                            <td class="tebal">{{ $sisa_pre_payment_type }}</td>
                                            <td class="kanan tebal nowrap">{{ $sisa_pre_payment }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>
            @endif

            {{-- riwayat pembayaran --}}
            @if (count($receipts) > 0)
                <div class="line-space">
                    <table class="table-title">
                        <tr>
                            <td class="bill-to">Riwayat Pembayaran</td>
                            {{-- <td class="colon"></td>
                            <td class="content">
                            </td> --}}
                        </tr>
                        <tr>
                            <td>
                                <table class="table-history" style="border: 1px solid #111">
                                    <thead>
                                        <tr>
                                            <th class="tengah" style="width: 70px">Tanggal</th>
                                            <th class="tengah" style="width: 160px">No. Transaksi</th>
                                            <th class="">Keterangan</th>
                                            <th class="tengah" style="width: 100px">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($receipts as $receipt)
                                            <tr>
                                                <td class="tengah">{{ $receipt['date'] }}</td>
                                                <td class="tengah">{{ $receipt['pnm_number'] }}</td>
                                                <td class="" style="padding-left: 3px;">
                                                    {{ $receipt['termin'] }}</td>
                                                <td class="kanan tebal nowrap">{{ $receipt['value'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>
            @endif
        </div>

        {{-- bank --}}
        <div class="line-space"></div>
        <table style="width:100%">
            <tr>
                <td style="width:40%;vertical-align: top">
                    <table class="table-title">
                        <tr>
                            <td>
                                <div class="info-bank">
                                    <div class="line-space">
                                        <div class="bill-to"><strong>Pembayaran transfer ke Rekening:</strong></div>
                                        <table class="table-bank">
                                            <thead>
                                                <tr>
                                                    <th style="text-align: left !important" class="reference nowrap">
                                                        <span class="account-name">{{ $company }}</span>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($banks as $bank)
                                                    <tr style="text-align: left">
                                                        <td class="">{{ $bank->toCoa->name }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width:30%">
                </td>
                <td style="width:30%; vertical-align: top">
                </td>
            </tr>
        </table>

        {{-- bank --}}
        <table style="width:100%">
            <tr>
                <td style="width:40%;vertical-align: top">
                </td>
                <td style="width:30%">
                </td>
                <td style="width:30%; vertical-align: top">
                    <table class="table-title">
                        <tr>
                            <td class="label">Hormat kami,</td>
                            <td class="colon"></td>
                            <td class="content"></td>
                        </tr>
                        <tr>
                            <td class="label">
                                <p></p>
                            </td>
                            <td class="colon"></td>
                            <td class="content"></td>
                        </tr>
                        <tr>
                            <td class="label">
                                <p></p>
                            </td>
                            <td class="colon"></td>
                            <td class="content"></td>
                        </tr>
                        <tr>
                            <td class="label">
                                <p></p>
                            </td>
                            <td class="colon"></td>
                            <td class="content"></td>
                        </tr>
                        <tr>
                            <td class="label ttd-inspector">{{ $printed_by }}</td>
                            <td class="colon"></td>
                            <td class="content"></td>
                        </tr>
                        <tr>
                            <td class="label"><i>Head of Finance</i></td>
                            <td class="colon"></td>
                            <td class="content"></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="table-title">
            <tr>
                <td>
                    @include('bottom-info')
                </td>
            </tr>
        </table>

    </main>

    @include('footer')

</body>

</html>
