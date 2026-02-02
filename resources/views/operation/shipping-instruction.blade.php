<!DOCTYPE html>
<html lang="id">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" type="image/png" href="favicon.png">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="css/pdf.css" type="text/css">

</head>

<body>
    @include('header')

    <main class="container">

        <div class="header">
            <h2 class="title border-bottom">{{ $title }}</h2>
            <h3 class="sub-title">{{ $si_number }}</h3>
        </div>

        <div class="info-section">
            <div class="line-space">
                <p></p>
                <div class="bill-to">Date:</div>
                <strong>{{ date('d M Y', strtotime($date)) }}</strong>
            </div>

            <div class="line-space">
                <div class="bill-to">Instruction to:</div>
                <strong>{{ $receipent }} QQ {{ $surveyor }}</strong>
            </div>

            <div class="line-space">
                <p>Through this document, <strong>{{ $details['shipper']  }}</strong> provides the following Shipping Instruction to <strong>{{ $kontraktor }}</strong>, {{ $additional_note }}</p>
            </div>

            <div class="line-space">
                <div class="bill-to">Details:</div>
                <div class="">
                    <table class="table-details">
                        <tr>
                            <td class="reference">Shipper</td>
                            <td>{{ $details['shipper'] }}</td>
                        </tr>
                        <tr>
                            <td class="reference">Consignee</td>
                            <td>{{ $details['consignee'] }}</td>
                        </tr>
                        <tr>
                            <td class="reference">Notify Party</td>
                            <td>{{ $details['notify_party'] }}</td>
                        </tr>
                        <tr>
                            <td class="reference">Commodity</td>
                            <td>{{ $details['commodity'] }}</td>
                        </tr>
                        <tr>
                            <td class="reference">Transport Vessel</td>
                            <td>{{ $details['transport_mode'] }}</td>
                        </tr>
                        <tr>
                            <td class="reference">Port of Loading</td>
                            <td>{{ $details['loading_port'] }}</td>
                        </tr>
                        <tr>
                            <td class="reference">Port of Discharge</td>
                            <td>{{ $details['discharge_port'] }}</td>
                        </tr>
                        <tr>
                            <td class="reference">Cargo Quantity</td>
                            <td>{{ $details['cargo_quantity'] }}</td>
                        </tr>
                        <tr>
                            <td class="reference">Loading Period</td>
                            <td>{{ $details['start_date'] }} to {{ $details['end_date']  }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <p></p>
        <p></p>

        <div class="info-inspector">
            <div class="line-space">
                <div class="table-container">
                    <table class="table-inspector">
                        <tr>
                            <td style="width:10%"></td>
                            <td style="width:10%"></td>
                            <td style="width:10%">Sincelery,</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td>&nbsp;</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td>&nbsp;</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td>&nbsp;</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td class="mining-inspector">{{ $mining_inspector }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td><i>Mining Inspector</i></td>
                        </tr>

                    </table>
                </div>
            </div>
        </div>

        @include('bottom-info')

    </main>

    @include('footer')

</body>

</html>
