@props(['rol'])

@php
    $classes = match($rol) {
        'admin' => 'bg-rose-50 text-rose-700 border-rose-200/60',
        'director' => 'bg-sky-50 text-sky-700 border-sky-200/60',
        'docente' => 'bg-emerald-50 text-emerald-700 border-emerald-200/60',
        'bodega' => 'bg-amber-50 text-amber-700 border-amber-200/60',
        default => 'bg-slate-50 text-slate-700 border-slate-200/60',
    };

    $label = match($rol) {
        'admin' => 'Administrador',
        'director' => 'Director',
        'docente' => 'Docente',
        'bodega' => 'Bodega',
        default => ucfirst($rol),
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border {$classes} shadow-2xs"]) }}>
    <span class="h-1.5 w-1.5 rounded-full currentColor" style="background-color: currentColor;"></span>
    {{ $label }}
</span>
