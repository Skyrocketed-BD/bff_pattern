<!DOCTYPE html>
<html lang="id">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" type="image/png" href="favicon.png">
    <title>{{ $title }}</title>

    <style>
        @page {
            /* margin-top: 100px;
            margin-bottom: 70px; */
            margin: 20mm 10mm 20mm 10mm;

            @bottom-center {
                content: "Halaman " counter(page) " dari " counter(pages);
            }
        }

        body {
            font-family: "SourceSansPro", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
                "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji",
                "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            color: #282d32;
            font-size: 0.875rem;
            position: relative;
            margin: 0 auto;
            /* background-color: #f9f9f9; */
        }

        header {
            padding-bottom: 10px;
            margin-top: -30px;
            margin-bottom: 15px;
            border-bottom: 1px solid #aaaaaa;
            height: 80px;
        }

        #logo {
            float: left;
            margin-top: 8px;
        }

        #logo img {
            height: 70px;
        }

        #company {
            float: right;
            text-align: right;
        }

        #company .info {
            text-align: right;
            color: #222;
        }

        h2.name {
            font-size: 1.4em;
            /* font-weight: normal; */
            margin: 0;
            margin-bottom: 5px;
        }

        h4 {
            margin: 0;
        }

        .remove-link {
            text-decoration: none;
        }

        .w-full {
            width: 100%;
        }

        .w-half {
            width: 50%;
        }

        .margin-top {
            margin-top: 1.12rem;
        }

        .margin-bottom {
            margin-bottom: 1.52rem;
        }

        .container {
            padding: 20px;
        }

        .header {
            text-align: center
        }

        .header .title {
            padding: 0;
            font-weight: bold;
            font-style: italic;
            color: #444;
            margin: 0;
        }

        .header .sub-title {
            padding: 0;
            font-weight: bold;
            margin: 0;
        }

        .info-section {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }

        .info-section .bill-to {
            font-style: italic;
            color: #444;
        }

        .info-section .line-space {
            padding-top: 5px;
            padding-bottom: 10px;
        }

        .info-bottom {
            position: fixed;
            bottom: 0px;
            left: 0;
            width: 100%;
            padding: 0;
            margin: 0px;
            margin-bottom: -30px;
        }

        .info-bottom .bill-to {
            font-style: italic;
            color: #444;
        }

        .info-bottom .line-space {
            padding-top: 5px;
            padding-bottom: 10px;
            font-size: 12px;
        }

        .table-container {
            margin-top: 5px;
            margin-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            color: rgba(0, 0, 0, 0.65);
        }

        th,
        td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .table-reference  td{
            padding: 4px 8px;
        }
        .reference {
            width: 120px;
        }

        .totals {
            margin-top: 20px;
            text-align: right;
        }
        .totals .total-line{
            margin-top: 3px;
            margin-bottom: 5px;
        }
        .total-paid {
            margin-top: 5px;
            padding-bottom: 5px;
            font-size: 1rem;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 30px;
            color: #7a7a7a;
            background-color: #f1f1f1;
            text-align: center;
            font-size: 12px;
            line-height: 10px;
            padding: 0;
            margin: 0;
            border: none;
            margin-bottom: -60px;
        }
    </style>
</head>

<body>
    <header>
        <div id="logo">
            @if (get_arrangement('logo'))
                @php
                    $logo = 'uploads/file/arrangement/' . get_arrangement('logo');
                @endphp
                <img src="{{ $logo }}" alt="Logo">
            @else
                <img src="logo.png" alt="Default Logo">
            @endif
        </div>
        <div id="company">
            <h2 class="name">{{ get_arrangement('company_name') }}</h2>
            <div class="info">
                <div>{{ get_arrangement('address') }}</div>
                <div>{{ get_arrangement('phone') }}</div>
                <div><a class="remove-link">{{ get_arrangement('email') }}</a></div>
            </div>
        </div>
    </header>

    <main class="container">

        <div class="header">
            <h2 class="title">{{ $title }}</h2>
            <h3 class="sub-title margin-bottom">{{ $transaction_number }}</h3>
        </div>

        <div class="info-section">
            <div class="line-space">
                <div class="bill-to">Bill to:</div>
                <strong>{{ $receipent }}</strong>
            </div>
            <div class="line-space">
                <div class="bill-to">Invoice date:</div>
                <strong>{{ date('d M Y', strtotime($date)) }}</strong>
            </div>
            <div class="line-space">
                <div class="bill-to">Paid destination:</div>
                <strong>{{ $paid_to }}</strong>
            </div>
            <div class="line-space">
                <div class="bill-to">Description:</div>
                {{-- <strong>{{ $description }}</strong> --}}
                <div class="table-container">
                    <table class="">
                        <tr>
                            <th>{{ $description }}</th>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="line-space">
                <div class="bill-to">Reference:</div>
                <div class="table-container">
                    <table class="table-reference">
                        <tr>
                            <td class="reference">Date</td>
                            <td>{{ date('d M Y', strtotime($transaction['date'])) }}</td>
                        </tr>
                        <tr>
                            <td>Reference Number</td>
                            <td>{{ $transaction['reference_number'] }}</td>
                        </tr>
                        <tr>
                            <td>Invoice Number</td>
                            <td>{{ $transaction['transaction_number'] }}</td>
                        </tr>
                        <tr>
                            <td>Amount</td>
                            <td>{{ $transaction['total'] }}</td>
                        </tr>
                        <tr>
                            <td>Description</td>
                            <td>{{ $transaction['description'] }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="totals">
            <div class="total-line"><i>Total:</i> <strong>{{ $total }}</strong></div>
            @if ($tax_name)
            <div class="total-line"><i>{{ $tax_name }}:</i> <strong>{{ $ppn }}</strong></div>
            @endif
            <div class="total-paid"><i>Total Paid:</i> <strong > {{  $total_paid }}</strong></div>
            {{-- Subtotal: ${{ number_format($invoice->subtotal, 2) }}<br> --}}
            {{-- Tax: ${{ number_format($invoice->tax, 2) }}<br> --}}
            {{-- Due Balance: <strong>${{ number_format($invoice->balance_due, 2) }}</strong> --}}
        </div>

        <div class="info-bottom">
            <div class="line-space">
                <div class="bill-to">Printed by:</div>
                <div class="table-container">
                    <table class="table-reference">
                        <tr>
                            <td class="reference">Date</td>
                            {{-- <td>{{ date('d M Y') }}</td> --}}
                            <td>{{ $printed_date }}</td>
                        </tr>
                        <tr>
                            <td>Admin</td>
                            <td>{{ $printed_by }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div class="footer">
        <p>&copy;<?php echo date('Y'); ?> SkyRocketed. All Rights Reserved</p>
    </div>

</body>

</html>
