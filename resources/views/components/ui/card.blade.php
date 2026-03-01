@props([
    'padding' => 'md',   // sm | md | lg | none
    'shadow'  => true,
    'border'  => true,
])

@php
$paddings = [
    'none' => '',
    'sm'   => 'p-4',
    'md'   => 'p-5',
    'lg'   => 'p-6',
];

$classes = implode(' ', array_filter([
    'rounded-xl bg-surface',
    $border  ? 'border border-content/10' : '',
    $shadow  ? 'shadow-sm'                : '',
    $paddings[$padding] ?? $paddings['md'],
    $attributes->get('class'),
]));
@endphp

<div {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    @isset($header)
        <div class="pb-4 mb-4 border-b border-content/10 font-semibold text-content">
            {{ $header }}
        </div>
    @endisset

    {{ $slot }}

    @isset($footer)
        <div class="pt-4 mt-4 border-t border-content/10 text-sm text-muted">
            {{ $footer }}
        </div>
    @endisset
</div>
