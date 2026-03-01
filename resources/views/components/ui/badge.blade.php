@props([
    'variant' => 'primary',   // primary | success | warning | danger | muted
])

@php
$styles = [
    'primary' => 'background:color-mix(in srgb, var(--c-primary) 12%, transparent);color:var(--c-primary)',
    'success' => 'background:rgba(34,197,94,0.1);color:#22c55e',
    'warning' => 'background:rgba(234,179,8,0.1);color:#ca8a04',
    'danger'  => 'background:rgba(239,68,68,0.1);color:#f87171',
    'muted'   => 'background:var(--c-soft);color:var(--c-muted);border:1px solid var(--c-border)',
];
$style = $styles[$variant] ?? $styles['primary'];
@endphp

<span {{ $attributes }}
      style="display:inline-flex;align-items:center;gap:0.25rem;border-radius:9999px;padding:0.15rem 0.6rem;font-size:0.7rem;font-weight:600;{{ $style }}">
    {{ $slot }}
</span>
