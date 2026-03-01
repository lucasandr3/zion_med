@props([
    'variant' => 'primary',   // primary | secondary | ghost | danger
    'size'    => 'md',        // sm | md | lg
    'type'    => 'button',
    'href'    => null,
])

@php
$base = 'inline-flex items-center justify-center gap-2 font-semibold rounded-lg
         transition-all active:scale-95 focus:outline-none focus:ring-2 focus:ring-offset-1
         disabled:opacity-50 disabled:cursor-not-allowed select-none';

$sizes = [
    'sm' => 'px-3 py-1.5 text-xs',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-6 py-3 text-base',
];

$variants = [
    'primary'   => 'bg-primary text-white shadow-sm hover:opacity-90 focus:ring-primary/40',
    'secondary' => 'bg-secondary text-white shadow-sm hover:opacity-90 focus:ring-secondary/40',
    'ghost'     => 'bg-transparent text-primary border border-primary hover:bg-primary/10 focus:ring-primary/30',
    'danger'    => 'bg-red-600 text-white shadow-sm hover:bg-red-700 focus:ring-red-400',
];

$classes = implode(' ', array_filter([
    $base,
    $sizes[$size]    ?? $sizes['md'],
    $variants[$variant] ?? $variants['primary'],
    $attributes->get('class'),
]));
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->except('class')->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->except('class')->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
