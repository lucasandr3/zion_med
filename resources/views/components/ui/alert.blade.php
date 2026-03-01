@props([
    'type' => 'info',   // info | success | warning | error
])

@php
$styles = [
    'info'    => ['bg'=>'rgba(59,130,246,0.08)',  'border'=>'rgba(59,130,246,0.25)',  'text'=>'var(--c-primary)', 'icon'=>'info'],
    'success' => ['bg'=>'rgba(34,197,94,0.08)',   'border'=>'rgba(34,197,94,0.25)',   'text'=>'#22c55e',          'icon'=>'check_circle'],
    'warning' => ['bg'=>'rgba(234,179,8,0.08)',   'border'=>'rgba(234,179,8,0.25)',   'text'=>'#ca8a04',          'icon'=>'warning'],
    'error'   => ['bg'=>'rgba(239,68,68,0.08)',   'border'=>'rgba(239,68,68,0.25)',   'text'=>'#ef4444',          'icon'=>'cancel'],
];
$s = $styles[$type] ?? $styles['info'];
@endphp

<div role="alert"
     {{ $attributes->merge(['class' => '']) }}
     style="display:flex;align-items:flex-start;gap:0.625rem;border-radius:0.5rem;border:1px solid {{ $s['border'] }};background:{{ $s['bg'] }};padding:0.75rem 1rem;font-size:0.8125rem;color:{{ $s['text'] }}">
    <span class="material-symbols-outlined shrink-0" style="font-size:18px;margin-top:0.05rem;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 20">{{ $s['icon'] }}</span>
    <div>{{ $slot }}</div>
</div>
