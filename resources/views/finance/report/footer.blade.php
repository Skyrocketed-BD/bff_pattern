
<footer>
    <div class="footer w-full">
        <p>Copyright &copy; <?php echo date('Y'); ?></p>
    </div>
</footer>
</body>

</html>
{{-- <h2>{{ print_r($data) }} </h2> --}}

{{-- @foreach ($data as $key => $value) --}}

{{-- <h3>{{ $value }}</h3> --}}
{{-- @if (count($value['body']) > 0)
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
@endif --}}
{{-- @endforeach --}}
{{-- <html>
<head>
    <style>
        @page{
            margin-top: 100px; /* create space for header */
            margin-bottom: 70px; /* create space for footer */
        }
        header, footer{
            position: fixed;
            left: 0px;
            right: 0px;
        }
        header{
            height: 60px;
            margin-top: -60px;
        }
        footer{
            height: 50px;
            margin-bottom: -50px;
          }
    </style>
</head>
<body>
    <header>
        <h1>This is a Header</h1>
    </header>
    <footer>
        <p>Copyright &copy; <?php echo date('Y'); ?></p>
    </footer>
    <main>
        <p>This is the body</p>
    </main>
</body>
</html> --}}
