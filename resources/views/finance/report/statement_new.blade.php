@foreach ($data as $key => $value)

@if (count($value['body']) > 0)
<table style="width: 100%" border="1">
    <tr>
        <td colspan="2">
            <b>{{ $value['name'] }}</b>
        </td>
    </tr>
    @foreach ($value['body'] as $key => $value2)
    <tr>
        <td>{{ $value2['name'] }}</td>
        <td align="right">{{ $value2['total'] }}</td>
    </tr>
    @endforeach
    <tr>
        <td><b>Total {{ $value['name'] }}</b></td>
        <td align="right">{{ $value['total'] }}</td>
    </tr>
</table>
@else
<table style="width: 100%" border="1">
    <tr>
        <td>
            <b>{{ $value['name'] }}</b>
        </td>
        <td align="right">{{ $value['total'] }}</td>
    </tr>
</table>
@endif

@endforeach