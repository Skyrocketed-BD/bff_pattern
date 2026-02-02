<header class="clearfix">
    <div id="logo">
        @if (get_arrangement('logo'))
            @php
                $logo = 'uploads/file/arrangement/' . get_arrangement('logo');
            @endphp
            {{-- {!! $logo !!} --}}
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
    </div>
</header>
