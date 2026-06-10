@props([
    'title' => '¿Estás seguro?',
    'message' => 'Esta acción no se puede deshacer.',
    'confirmAction' => 'confirm',
    'confirmText' => 'Confirmar',
    'cancelText' => 'Cancelar',
    'type' => 'danger' // danger, info, warning
])

@php
    $colorClass = match($type) {
        'danger' => 'bg-rose-600 hover:bg-rose-500 focus:ring-rose-500 shadow-rose-100 text-white',
        'warning' => 'bg-amber-600 hover:bg-amber-500 focus:ring-amber-500 shadow-amber-100 text-white',
        default => 'bg-indigo-600 hover:bg-indigo-500 focus:ring-indigo-500 shadow-indigo-100 text-white',
    };

    $icon = match($type) {
        'danger' => '<svg class="h-6 w-6 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>',
        'warning' => '<svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 4.5h.008v.008H12v-.008zM2.25 12a9.75 9.75 0 1119.5 0 9.75 9.75 0 01-19.5 0z" /></svg>',
        default => '<svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" /></svg>',
    };
@endphp

<div 
    x-data="{ show: @entangle($attributes->wire('model')) }"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
>
    <!-- Backdrop with blur -->
    <div 
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="show = false"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
    ></div>

    <!-- Modal wrapper -->
    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
        <div 
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative transform overflow-hidden rounded-3xl bg-white px-4 pb-4 pt-5 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6"
        >
            <div class="sm:flex sm:items-start">
                <div class="mx-auto flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-50 sm:mx-0 sm:h-10 sm:w-10 border border-slate-100">
                    {!! $icon !!}
                </div>
                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                    <h3 class="text-lg font-bold leading-6 text-slate-900" id="modal-title">
                        {{ $title }}
                    </h3>
                    <div class="mt-2">
                        <p class="text-sm text-slate-500">
                            {{ $message }}
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                <button 
                    type="button" 
                    wire:click="{{ $confirmAction }}" 
                    @click="show = false"
                    class="inline-flex w-full justify-center rounded-xl px-4 py-2.5 text-sm font-semibold shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 sm:w-auto transition-all duration-150 {{ $colorClass }}"
                >
                    {{ $confirmText }}
                </button>
                <button 
                    type="button" 
                    @click="show = false" 
                    class="mt-3 inline-flex w-full justify-center rounded-xl bg-white hover:bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 border border-slate-300 shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto transition-all duration-150"
                >
                    {{ $cancelText }}
                </button>
            </div>
        </div>
    </div>
</div>
