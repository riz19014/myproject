@php
    $company = $pdfCompany ?? null;
    $nameLines = $company?->pdfHeaderNameLines() ?? ['', ''];
    $phoneLines = collect(preg_split('/\s*,\s*/', (string) ($company?->phone ?? '')) ?: [])
        ->map(static fn ($line) => trim($line))
        ->filter()
        ->values();
    $logoDataUri = $company?->pdfLogoDataUri();
    $initials = $company?->pdfLogoInitials() ?? '';
@endphp
<div class="pdf-co-header-wrap">
    <table class="pdf-co-header-table">
        <tr>
            <td class="pdf-co-brand-cell">
                <table class="pdf-co-brand-inner">
                    <tr>
                        <td class="pdf-co-logo-cell">
                            @if($logoDataUri)
                                <img src="{{ $logoDataUri }}" alt="" class="pdf-co-logo-img">
                            @elseif($initials !== '')
                                <div class="pdf-co-logo-fallback">{{ $initials }}</div>
                            @endif
                        </td>
                        <td class="pdf-co-name-cell">
                            @if($nameLines[0] !== '')
                                <div class="pdf-co-name-primary">{{ $nameLines[0] }}</div>
                            @endif
                            @if($nameLines[1] !== '')
                                <div class="pdf-co-name-secondary">{{ $nameLines[1] }}</div>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
            <td class="pdf-co-contact-cell">
                <div class="pdf-co-contact-name">Contact Us</div>
                @if($phoneLines->isNotEmpty())
                    @foreach($phoneLines as $phoneLine)
                        <div>{{ $phoneLine }}</div>
                    @endforeach
                @endif
                @if(filled($company?->email))
                    <div class="pdf-co-contact-email">E-mail: {{ $company->email }}</div>
                @endif
            </td>
        </tr>
    </table>
    @if(filled($company?->address))
        <div class="pdf-co-address-bar">{{ $company->address }}</div>
    @endif
</div>
