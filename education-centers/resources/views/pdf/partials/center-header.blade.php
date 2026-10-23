@php
    $docNumber = $docNumber ?? null;
    $docDate = $docDate ?? null;
@endphp
@if($center)
    <table class="pdf-header">
        <tr>
            <td>
                <div class="center-name">{{ $center->translated('name') ?? __('Education Centers') }}</div>
                <div class="muted">
                    {{ $center->translated('address') }}
                    @if($center->city) — {{ $center->city }} @endif
                </div>
                <div class="muted">
                    @if($center->phone){{ $center->phone }} @endif
                    @if($center->email)· {{ $center->email }} @endif
                </div>
            </td>
            @if($docNumber || $docDate)
                <td class="doc-ref">
                    @if($docNumber)<div>{{ __('N°') }} {{ $docNumber }}</div>@endif
                    @if($docDate)<div>{{ $docDate }}</div>@endif
                </td>
            @endif
        </tr>
    </table>
@elseif($docNumber || $docDate)
    <table class="pdf-header">
        <tr><td class="doc-ref">
            @if($docNumber)<div>{{ __('N°') }} {{ $docNumber }}</div>@endif
            @if($docDate)<div>{{ $docDate }}</div>@endif
        </td></tr>
    </table>
@endif
