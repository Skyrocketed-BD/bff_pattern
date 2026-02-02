<header class="">
    <table class="table-header" cellpadding="0" cellspacing="0">
        <tr>
            <td class="logo-cell">
                @if (get_arrangement('logo'))
                    @php
                        $logo = 'uploads/file/arrangement/' . get_arrangement('logo');
                    @endphp
                    <img class="logo-img" src="{{ $logo }}" alt="Logo">
                @else
                    <img class="logo-img"  src="logo.png" alt="Default Logo">
                @endif
            </td>
            <td class="company-cell">
                <div class="company-bottom">
                    <h2 class="company-name">{{ get_arrangement('company_name') }}</h2>
                </div>
                <div class="info">
                    <div>{{ get_arrangement('address') }}</div>
                    <div>{{ get_arrangement('address_opt') }}</div>
                    <div><strong>Phone:</strong>{{ get_arrangement('phone') }} <strong>e-mail:</strong><a class="remove-link">{{ get_arrangement('email') }}</a></div>
                    <div></div>
                </div>
            </td>
        </tr>
        <tr class="header-border" >
            {{-- <td sstyle="padding-bottom: 5px !important"></td> --}}
            <td></td>
            <td style="border-bottom: 1px solid #252525; padding: 0 0 1px 0"></td>
        </tr>
    </table>
    {{-- <div id="logo">
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
            <div>{{ get_arrangement('address_opt') }}</div>
            <div><strong>Phone:</strong>{{ get_arrangement('phone') }} <strong>eMail:</strong><a class="remove-link">{{ get_arrangement('email') }}</a></div>
            <div></div>
        </div>
    </div> --}}
</header>
