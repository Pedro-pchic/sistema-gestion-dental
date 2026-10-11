@props([
    'product',
    'width' => 640,
    'loading' => 'lazy',
])

@php($imageUrl = $product->imageDeliveryUrl((int) $width))

@if ($imageUrl)
    <img src="{{ $imageUrl }}" alt="Fotografía de {{ $product->name }}" width="{{ $width }}" height="{{ (int) round((int) $width * 0.75) }}"
        loading="{{ $loading }}" decoding="async" {{ $attributes->merge(['class' => 'block object-cover']) }}>
@else
    <div aria-hidden="true" {{ $attributes->merge(['class' => 'grid place-items-center bg-sand text-coffee']) }}>
        <span class="text-3xl font-black tracking-tight">{{ mb_strtoupper(mb_substr($product->name, 0, 2)) }}</span>
    </div>
@endif
