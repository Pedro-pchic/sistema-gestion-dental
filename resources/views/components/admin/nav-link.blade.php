@props(['active' => false])

<a {{ $attributes->class([
    'flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-400',
    'bg-brand-700 text-white shadow-sm' => $active,
    'text-stone-300 hover:bg-white/8 hover:text-white' => ! $active,
]) }} @if ($active) aria-current="page" @endif>
    <span class="size-1.5 shrink-0 rounded-full bg-current opacity-70" aria-hidden="true"></span>
    <span>{{ $slot }}</span>
</a>
