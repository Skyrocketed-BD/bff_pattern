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
                                <td class="label">Telah terima dari</td>
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
                    {{-- <td style="width:50px">
                    </td> --}}
                    <td style="width:45%; vertical-align: top">
                        <table class="table-title">
                            <tr>
                                <td class="label">Tanggal Transaksi</td>
                                <td class="colon">:</td>
                                <td class="content">{{ $date }}</td>
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


            {{-- table detail  --}}
            <div class="line-space"></div>
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
                                <tr>
                                    <td class="">{{ $description }}</td>
                                    <td class="kanan tebal nowrap">{{ $total }}</td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="tebal tengah miring">
                                        {{ $terbilang }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>

            {{-- riwayat pembayaran --}}
            {{-- @if (count($history) > 0) --}}
            @if (!empty($history))
                <div class="line-space"></div>
                <div class="line-space">
                    <table class="table-title">
                        <tr>
                            <td style="padding-bottom:5px" class="bill-to">Riwayat Pembayaran (15 transaksi terakhir)</td>
                        </tr>
                        <tr>
                            <td>
                                <table class="table-history" style="border: 1px solid #111">
                                    <thead>
                                        <tr>
                                            <th class="tengah" style="width: 60px">Tanggal</th>
                                            <th class="tengah" style="width: 130px">No. Transaksi</th>
                                            <th class="tengah" style="width: 70px">Kategori</th>
                                            <th class="tengah">Keterangan</th>
                                            <th class="tengah" style="width: 70px">Jumlah</th>
                                            <th class="tengah" style="width: 70px">Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($history as $item_history)
                                            <tr>
                                                <td class="tengah">{{ $item_history['date'] }}</td>
                                                <td class="tengah">{{ $item_history['transaction_number'] }}</td>
                                                <td class="tengah">{{ $item_history['category'] }}</td>
                                                <td style="padding-left: 5px" sclass="ml">{{ $item_history['description'] }}</td>
                                                <td class="kanan tebal nowrap">{{ $item_history['value'] }}</td>
                                                <td class="kanan tebal nowrap">{{ $item_history['saldo'] }}</td>

                                                {{-- <td class="" style="padding-left: 3px;">{{ $receipt['termin'] }}</td>
                                                <td class="kanan tebal nowrap">{{ $receipt['value'] }}</td> --}}
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
        <div class="line-space"></div>
        <div class="line-space"></div>


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
                            <td class="label">Yang menerima,</td>
                            <td class="colon"></td>
                            <td class="content"></td>
                        </tr>
                        <tr>
                            <td class="label"><p></p></td>
                            <td class="colon"></td>
                            <td class="content"></td>
                        </tr>
                        <tr>
                            <td class="label"><p></p></td>
                            <td class="colon"></td>
                            <td class="content"></td>
                        </tr>
                        <tr>
                            <td class="label"><p></p></td>
                            <td class="colon"></td>
                            <td class="content"></td>
                        </tr>
                        <tr>
                            <td class="label ttd-inspector">{{ $printed_by }}</td>
                            <td class="colon"></td>
                            <td class="content"></td>
                        </tr>
                        {{-- <tr>
                            <td class="label"><i>Head of Finance</i></td>
                            <td class="colon"></td>
                            <td class="content"></td>
                        </tr> --}}
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
