@php
    $currentLocale = app()->getLocale();
@endphp
<div class="ec-lang-pill" role="group" aria-label="{{ __('Changer de langue') }}">
    <a href="{{ route('locale.switch', 'fr') }}"
       @if($currentLocale === 'fr') aria-current="true" @endif
       aria-label="{{ __('Français') }}">{{ __('FR') }}</a>
    <a href="{{ route('locale.switch', 'ar') }}"
       @if($currentLocale === 'ar') aria-current="true" @endif
       aria-label="{{ __('العربية') }}">{{ __('AR') }}</a>
</div>
