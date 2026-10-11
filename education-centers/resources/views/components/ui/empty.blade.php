@props(['title' => 'Aucun élément', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800">
        {{ $icon ?? '' }}
        @unless(isset($icon))
            <x-icon name="document" class="h-6 w-6" />
        @endunless
    </div>
    <p class="font-medium text-slate-800 dark:text-slate-100">{{ $title }}</p>
    @if($description)
        <p class="mt-1 max-w-md text-sm text-slate-500">{{ $description }}</p>
    @endif
    @if($slot->isNotEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
