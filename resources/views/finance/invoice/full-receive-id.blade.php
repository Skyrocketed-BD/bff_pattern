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
                    {{-- <td style="width:10%">
                    </td> --}}
                    <td style="width:45%; vertical-align: top">
                        <table class="table-title">
                            <tr>
                                <td class="label">Tanggal Invoice</td>
                                <td class="colon">:</td>
                                <td class="content">{{ $date }}</td>
                            </tr>
                            <tr>
                                <td class="label">No. Invoice</td>
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

            <div class="line-space"></div>

            {{-- table detail  --}}
            <div class="line-space table-title"></div>
            <table class="table-title">
                <tr>
                    <td>
                        <table class="table-details">
                            <thead>
                                <tr>
                                    <th class="tengah" style="width: 180px">Nama Transaksi</th>
                                    <th>Keterangan</th>
                                    <th class="tengah" style="width: 150px">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="">{{ $transaction_name }}</td>
                                    <td class="">{{ $description }}</td>
                                    <td class="kanan tebal nowrap">{{ $total }}</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="tebal tengah miring">
                                        {{ $terbilang }}
                                    </td>
                                </tr>
                            </tbody>
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
        </div>
    </main>

    @include('footer')

</body>

</html>
