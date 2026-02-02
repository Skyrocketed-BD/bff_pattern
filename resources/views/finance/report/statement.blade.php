@include('finance.report.header', [$title])
@include('finance.report.header-logo')

<div class="center margin-top margin-bottom">
    {{-- <h2 class="title margin-top">{{ $data['title'] }}</h2>
    <h3 class="sub-title margin-bottom">{{ $data['subtitle'] }}</h3> --}}
    <h2 class="title margin-top">{{ $title }}</h2>
    <h3 class="sub-title margin-bottom">{{ $subtitle }}</h3>
</div>


    <div class="margin-top">
        @foreach ($data as $key=>$value)

            @if (count($value['body']) > 0)
                <br>
                <table class="products">
                    <tr>
                        <th align="left" colspan="2" class="th-sub-title">{{ $value['name'] }}</th>
                    </tr>
                    @foreach ($value['body'] as $key=>$value2)
                        <tr class="items">
                            {{-- <td align="center">{{ $item['coa_number'] }}</td> --}}
                            <td>{{ $value2['name'] }}</td>
                            <td align="right" class="nominal">{{ rupiah($value2['total']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total">
                        {{-- <td></td> --}}
                        <td align="right">TOTAL {{ $value['name'] }}</td>
                        <td align="right">{{ rupiah($value['total']) }}</td>
                    </tr>
                </table>
            @else
                @if ($value['is_formula'] == 1)
                    <table class="sub-products">
                        <tr>
                            <th align="right">{{ $value['name'] }}</th>
                            <th align="right">{{ rupiah($value['total']) }}</th>
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
