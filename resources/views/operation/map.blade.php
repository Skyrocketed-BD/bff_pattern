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
            {{-- map --}}
            <table style="width:100%">
                <tr>
                    {{-- <td style="width:40%;vertical-align: top">
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
                    <td style="width:10%">
                    </td> --}}
                    <td stsyle="width:40%; vertical-align: top">
                        <img style="height: auto; width: 100%; object-fit: cover" src="data:{{ $mimeType }};base64,{{ $imageData }}" alt="Map Image">
                    </td>
                </tr>
            </table>

        </div>

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
