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
            <h3 class="sub-title margin-bottom">{{ $transaction_number }}</h3>
        </div>

        <div class="line-space"></div>
        <div class="line-space"></div>
        <div class="line-space"></div>

        <div class="">
            {{-- recipient --}}
            <table style="width:100%">
                <tr>
                    <td style="width:45%;vertical-align: top">
                        <table class="table-title">
                            <tr>
                                <td class="label" style="white-space:nowrap">Telah terima dari</td>
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
                    {{-- <td style="width:10%">
                    </td> --}}
                    <td style="width:45%; vertical-align: top">
                        <table class="table-title">
                            <tr>
                                <td class="label">Tanggal Transaksi</td>
                                <td class="colon">:</td>
                                <td class="content">{{ $date }}</td>
                            </tr>
                            <tr>
                                <td class="label">No. Transaksi</td>
                                <td class="colon">:</td>
                                <td class="content">{{ $transaction_number }}</td>
                            </tr>
                            <tr>
                                <td class="label">Akun/Rek. Tujuan</td>
                                <td class="colon">:</td>
                                <td class="content">{{ $paid_to }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            {{-- keterangan --}}
            <div class="line-space table-title miring margin-bottom05">Untuk:</div>
            <table class="table-title">
                <tr>
                    <td>
                        <div class="line-top-bottom-full">
                            <div class="transport-vessel">{{ $description }}</div>
                        </div>

                    </td>
                </tr>
            </table>

            {{-- <div class="line-space table-title miring">Keterangan:</div>
            <table class="table-title">
                <tr>
                    <td>
                        <table class="table-details">
                            <thead>
                                <tr>
                                    <th>{{ $description }}</th>
                                </tr>
                            </thead>
                        </table>
                    </td>
                </tr>
            </table> --}}


            {{-- details --}}
            {{-- <div class="line-space table-title miring">Detail:</div> --}}
            <div class="line-space table-title"></div>
            <table class="table-title">
                <tr>
                    <td>
                        <table class="table-details">
                            <thead>
                                <tr>
                                    <th class="tengah">Deskripsi</th>
                                    <th class="tengah" style="width: 150px">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($details as $key => $value)
                                    <tr>
                                        <td class="kanan nowrap">{{ $key }}</td>
                                        <td class="kanan tebal nowrap">{{ $value }}</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td class="kanan tebal nowrap">Total</td>
                                    <td class="kanan tebal nowrap">{{ $total_paid }}</td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="tebal tengah miring">
                                        {{ $terbilang }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        {{-- <table class="table-history" stsyle="border: 1px solid #111">
                            <thead>
                                <tr>
                                    <th class="">Komponen Transaksi</th>
                                    <th class="tengah" style="width: 100px">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($details as $key => $value)
                                    <tr>
                                        <td class="" style="padding-left: 3px;">
                                            {{ $key }}
                                        </td>
                                        <td class="kanan tebal nowrap">{{ $value }}</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td class="kanan tebal nowrap lebih-besar">Total Tagihan</td>
                                    <td class="kanan tebal nowrap lebih-besar">{{ $total_paid }}</td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="tebal tengah miring">
                                        {{ $terbilang }}
                                    </td>
                                </tr>
                            </tbody>
                        </table> --}}
                    </td>
                </tr>
            </table>

            {{-- referensi --}}
            <div class="line-space table-title miring">Referensi:</div>
            <table class="table-title">
                <tr>
                    <td>
                        <table class="table-history" stsyle="border: 1px solid #111">
                            {{-- <thead>
                                <tr>
                                    <th class="tengah" style="width: 70px">Tanggal</th>
                                    <th class="tengah" style="width: 160px">No. Transaksi</th>
                                    <th class="">Keterangan</th>
                                    <th class="tengah" style="width: 100px">Total</th>
                                </tr>
                            </thead> --}}
                            <tbody>
                                <tr>
                                    <td style="padding:2px; width: 100px" class="reference">Tanggal</td>
                                    <td style="padding:2px">{{ date('d M Y', strtotime($transaction['date'])) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px">No Referensi</td>
                                    <td style="padding:2px">{{ $transaction['reference_number'] }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px">No Invoice</td>
                                    <td style="padding:2px">{{ $transaction['transaction_number'] }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px">Total</td>
                                    <td style="padding:2px">{{ $transaction['total'] }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px">Keterangan</td>
                                    <td style="padding:2px">{{ $transaction['description'] }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <div class="line-space"></div>

        {{-- <div class="table-title"> --}}
        {{-- <div class="total-line"><i>Total:</i> <strong>{{ $total }}</strong></div> --}}
        {{-- @if ($tax_name) --}}
        {{-- <div class="total-line"><i>{{ $tax_name }}:</i> <strong>{{ $ppn }}</strong></div> --}}
        {{-- @endif --}}
        {{-- <div class="total-paid"><i>Total Paid:</i> <strong > {{  $total_paid }}</strong></div> --}}
        {{-- Subtotal: ${{ number_format($invoice->subtotal, 2) }}<br> --}}
        {{-- Tax: ${{ number_format($invoice->tax, 2) }}<br> --}}
        {{-- Due Balance: <strong>${{ number_format($invoice->balance_due, 2) }}</strong> --}}
        {{-- </div> --}}

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
