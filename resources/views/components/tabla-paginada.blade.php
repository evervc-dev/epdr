@props(['items'])

<div class="mt-4 px-4 py-3 bg-white border-t border-slate-200 sm:px-6 rounded-b-3xl">
    <div class="flex-1 flex justify-between sm:hidden">
        {{ $items->links('vendor.pagination.compact-spanish') }}
    </div>
    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-slate-500">
                Mostrando del
                <span class="font-semibold text-slate-700">{{ $items->firstItem() ?? 0 }}</span>
                al
                <span class="font-semibold text-slate-700">{{ $items->lastItem() ?? 0 }}</span>
                de
                <span class="font-semibold text-slate-700">{{ $items->total() }}</span>
                resultados
            </p>
        </div>
        <div>
            {{ $items->links('vendor.pagination.compact-spanish') }}
        </div>
    </div>
</div>
