<div 
    x-data="{ 
        toasts: [],
        addToast(message, type = 'success') {
            const id = Date.now();
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 4000);
        }
    }"
    @notify.window="addToast($event.detail.message, $event.detail.type)"
    class="fixed top-5 right-5 z-50 flex flex-col gap-3 w-full max-w-sm"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div 
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2"
            x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            :class="{
                'bg-emerald-50 border-emerald-100 text-emerald-800': toast.type === 'success',
                'bg-rose-50 border-rose-100 text-rose-800': toast.type === 'error',
                'bg-amber-50 border-amber-100 text-amber-800': toast.type === 'warning',
                'bg-sky-50 border-sky-100 text-sky-800': toast.type === 'info'
            }"
            class="flex items-start gap-3 p-4 rounded-2xl border shadow-xl bg-white/95 backdrop-blur-md transition-all duration-300"
        >
            <!-- Success Icon -->
            <template x-if="toast.type === 'success'">
                <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </template>
            <!-- Error Icon -->
            <template x-if="toast.type === 'error'">
                <svg class="h-5 w-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </template>
            <!-- Warning Icon -->
            <template x-if="toast.type === 'warning'">
                <svg class="h-5 w-5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </template>
            <!-- Info Icon -->
            <template x-if="toast.type === 'info'">
                <svg class="h-5 w-5 text-sky-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </template>

            <div class="flex-1">
                <p class="text-sm font-semibold text-slate-900" x-text="toast.type.charAt(0).toUpperCase() + toast.type.slice(1)"></p>
                <p class="mt-0.5 text-sm text-slate-600" x-text="toast.message"></p>
            </div>

            <button 
                @click="toasts = toasts.filter(t => t.id !== toast.id)" 
                class="text-slate-400 hover:text-slate-600 focus:outline-none shrink-0"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>
