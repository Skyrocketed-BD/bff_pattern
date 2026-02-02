<html>

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
            /* margin: 0 auto; */
        }

        header {
            padding-bottom: 10px;
            margin-top: -30px;
            margin-bottom: 15px;
            border-bottom: 1px solid #aaaaaa;
            height: 80px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 30px;
            background-color: #f1f1f1;
            text-align: center;
            font-size: 12px;
            line-height: 10px; /* Agar teks vertikal di tengah */
            padding: 0;
            margin: 0;
            border: none;
            margin-bottom: -60px;
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

        h2.name {
            font-size: 1.4em;
            font-weight: normal;
            margin: 0;
            margin-bottom: 5px;
        }

        h4 {
            margin: 0;
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

        table {
            width: 100%;
            border-spacing: 0;
            color: rgba(0, 0, 0, 0.65);
        }

        table.products {
            font-size: 0.75rem;
        }

        table.products th {
            padding: 0.55rem;
            background-color: #fafafa;
        }

        table.products tr th .th-sub-title {
            /* font-weight: bold; */
            /* font-style: normal; */
            padding: 0.2rem;
            background-color: #fafafa;
        }

        table.sub-products th {
            padding: 0.4rem;
            font-size: 0.75rem;
            background-color: #FEFBEA;
        }

        table tr.items td {
            font-size: 0.7rem;
            padding: 0.2rem 0.75rem 0.2rem 1.75rem;
            border-bottom: 1px solid #eee;
        }

        table tr.items td.nominal {
            text-align: right;
            width: 110px;
        }

        table tr.items-blank td {
            font-size: 0.7rem;
            padding: 0.2rem 0.75rem 0.2rem 1.75rem;
        }

        table tr.total td {
            border-top: 2px solid #eeeeeeb7;
            font-weight: bold;
            font-size: 0.65rem;
            border-bottom: 2px solid #eeeeeeb7;
            padding: 0.25rem 0.85rem;
        }

        .title {
            padding: 0;
            font-weight: bold;
            margin: 0;
        }
        .sub-title {
            padding: 0;
            font-weight: normal;
            font-style: italic;
            margin: 0;
        }

        .header-only {
            padding: 0;
            font-weight: bold;
            font-size: 0.85rem;
            font-style: italic;
            margin: 0;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .nowrap {
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <header class="clearfix">
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
            <div>{{ get_arrangement('address') }}</div>
            <div>{{ get_arrangement('phone') }}</div>
            <div><a href="mailto:{{ get_arrangement('email') }}">{{ get_arrangement('email') }}</a></div>
        </div>
    </header>

    <main>
        <div class="content">

            <div class="center margin-top margin-bottom">
                <h2 class="title margin-top">{{ $title }}</h2>
                <h3 class="sub-title margin-bottom">{{ $subtitle }}</h3>
            </div>


            <div class="margin-top">
                @foreach ($data as $key => $value)
                    @if (count($value['body']) > 0)
                        <br>
                        <table class="products">
                            <tr>
                                <th align="left" colspan="2" class="th-sub-title">{{ $value['name'] }}</th>
                            </tr>
                            @foreach ($value['body'] as $key => $value2)
                                <tr class="items">
                                    <td>{{ $value2['name'] }}</td>
                                    <td align="right" class="nominal">{{ rupiah($value2['total']) }}</td>
                                </tr>
                            @endforeach
                            <tr class="total">
                                <td align="">TOTAL {{ $value['name'] }}</td>
                                <td align="right">{{ rupiah($value['total']) }}</td>
                            </tr>
                            <tr class="items-blank">
                                <td></td>
                                <td></td>
                            </tr>
                        </table>
                    @else
                        @if ($value['type'] !== 'default')
                            <table class="sub-products">
                                <tr>
                                    <th align="left">{{ $value['name'] }}</th>
                                    @if ($value['display_currency'] === 'on')
                                        <th align="right">{{ rupiah($value['total']) }}</th>
                                    @else
                                        <th align="right">{{ $value['total'] }}</th>
                                    @endif
                                </tr>
                                <tr class="items-blank">
                                    <td></td>
                                    <td></td>
                                </tr>
                            </table>
                        @else
                            <table class="margin-top">
                                <tr>
                                    <td>
                                        <h3 class="header-only">{{ $value['name'] }}</h3>
                                    </td>
                                </tr>
                            </table>
                        @endif
                    @endif
                @endforeach

            </div>

        </div>
    </main>

    <div class="footer">
        <p>&copy;<?php echo date('Y')?> SkyRocketed. All Rights Reserved</p>
    </div>
</body>

</html>
