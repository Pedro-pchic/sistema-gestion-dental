@props(['active' => false, 'icon' => 'grid'])

@php
    $iconPaths = [
        'grid' => 'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z',
        'patients' => 'M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M18 8a4 4 0 0 1 0 8 M22 20v-2a4 4 0 0 0-3-3.87',
        'calendar' => 'M7 2v4 M17 2v4 M3 9h18 M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z M8 14h3 M8 18h3',
        'treatment' => 'M12 2l1.9 6.1L20 10l-6.1 1.9L12 18l-1.9-6.1L4 10l6.1-1.9L12 2z M19 17l.7 2.3L22 20l-2.3.7L19 23l-.7-2.3L16 20l2.3-.7L19 17z',
        'invoice' => 'M6 2h12v20l-3-2-3 2-3-2-3 2V2z M9 8h6 M9 12h6 M9 16h4',
        'payment' => 'M3 5h18a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z M1 10h22 M6 15h5',
        'report' => 'M4 20V12 M10 20V7 M16 20v-4 M22 20V3 M2 20h21',
        'users' => 'M4 21v-2a5 5 0 0 1 5-5h3a5 5 0 0 1 5 5v2H4z M10.5 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M20 8v6 M17 11h6',
    ];
@endphp

<a {{ $attributes->class([
    'flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-300',
    'bg-white text-brand-900 shadow-sm' => $active,
    'text-slate-300 hover:bg-white/10 hover:text-white' => ! $active,
]) }} aria-label="{{ trim(strip_tags((string) $slot)) }}" title="{{ trim(strip_tags((string) $slot)) }}" @if ($active) aria-current="page" @endif>
    <svg class="size-5 shrink-0 {{ $active ? 'text-brand-600' : 'text-brand-300' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" aria-hidden="true">
        <path d="{{ $iconPaths[$icon] ?? $iconPaths['grid'] }}" />
    </svg>
    <span data-sidebar-label class="truncate">{{ $slot }}</span>
</a>
