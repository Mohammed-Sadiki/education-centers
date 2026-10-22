@php
    $currentLocale = app()->getLocale();
@endphp
<div class="inline-flex shrink-0 items-center rounded-xl border border-slate-200 bg-white p-0.5 text-xs font-semibold shadow-2xs dark:border-slate-700 dark:bg-slate-900"
     role="group" aria-label="{{ __('Changer de langue') }}">
    <a href="{{ route('locale.switch', 'fr') }}"
       @if($currentLocale === 'fr') aria-current="true" @endif
       aria-label="{{ __('Français') }}"
       class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 transition-colors {{ $currentLocale === 'fr' ? 'bg-[#0DAD98] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
        <span aria-hidden="true">🇫🇷</span>
        <span class="hidden sm:inline">{{ __('Français') }}</span>
    </a>
    <span class="px-0.5 text-slate-300 dark:text-slate-600" aria-hidden="true">|</span>
    <a href="{{ route('locale.switch', 'ar') }}"
       @if($currentLocale === 'ar') aria-current="true" @endif
       aria-label="{{ __('العربية') }}"
       class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 transition-colors {{ $currentLocale === 'ar' ? 'bg-[#0DAD98] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
        <span aria-hidden="true">🇲🇦</span>
        <span class="hidden sm:inline">{{ __('العربية') }}</span>
    </a>
</div>
