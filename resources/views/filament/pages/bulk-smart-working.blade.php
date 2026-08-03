<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Form mese + opzioni --}}
        <x-filament::section>
            <x-slot name="heading">Impostazioni</x-slot>
            <x-slot name="description">
                Imposta Smart Working su tutti i giorni lavorativi del mese per tutti gli utenti attivi.
                Gli utenti potranno poi modificare i singoli giorni in autonomia.
            </x-slot>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Mese
                    </label>
                    <input type="month"
                           wire:model.live="month"
                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                </div>

                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox"
                               wire:model.live="overwrite"
                               class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            Sovrascrivi presenze già esistenti
                        </span>
                    </label>
                </div>
            </div>

            @if(!$overwrite)
                <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800 dark:bg-blue-900/20 dark:border-blue-700 dark:text-blue-300">
                    <strong>ℹ️ Modalità sicura:</strong> i giorni già compilati dagli utenti verranno preservati.
                </div>
            @else
                <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800 dark:bg-amber-900/20 dark:border-amber-700 dark:text-amber-300">
                    <strong>⚠️ Attenzione:</strong> tutti i giorni del mese verranno sovrascritti con Smart Working, inclusi quelli già compilati.
                </div>
            @endif
        </x-filament::section>

        {{-- Anteprima --}}
        <x-filament::section>
            <x-slot name="heading">Anteprima — {{ \Carbon\Carbon::parse($month)->translatedFormat('F Y') }}</x-slot>

            @php
                $m          = \Carbon\Carbon::parse($month);
                $cursor     = $m->copy()->startOfMonth();
                $end        = $m->copy()->endOfMonth();
                $workDays   = 0;
                while ($cursor->lte($end)) {
                    if ($cursor->isWeekday() && !\App\Helpers\Holidays::isHoliday($cursor)) $workDays++;
                    $cursor->addDay();
                }
                $usersCount = \App\Models\User::where('allow_view', true)->count();
            @endphp

            <div class="grid grid-cols-3 gap-4 text-center">
                <div class="p-4 bg-blue-50 rounded-lg border border-blue-200 dark:bg-blue-900/20 dark:border-blue-700">
                    <div class="text-3xl font-bold text-blue-700 dark:text-blue-300">{{ $workDays }}</div>
                    <div class="text-sm text-blue-600 dark:text-blue-400 mt-1">Giorni lavorativi</div>
                </div>
                <div class="p-4 bg-green-50 rounded-lg border border-green-200 dark:bg-green-900/20 dark:border-green-700">
                    <div class="text-3xl font-bold text-green-700 dark:text-green-300">{{ $usersCount }}</div>
                    <div class="text-sm text-green-600 dark:text-green-400 mt-1">Utenti attivi</div>
                </div>
                <div class="p-4 bg-purple-50 rounded-lg border border-purple-200 dark:bg-purple-900/20 dark:border-purple-700">
                    <div class="text-3xl font-bold text-purple-700 dark:text-purple-300">{{ $workDays * $usersCount }}</div>
                    <div class="text-sm text-purple-600 dark:text-purple-400 mt-1">Presenze da creare</div>
                    <div class="text-xs text-purple-500 dark:text-purple-500">
                        {{ $overwrite ? 'sovrascrittura inclusa' : 'max, escluse già compilate' }}
                    </div>
                </div>
            </div>
        </x-filament::section>

    </div>
</x-filament-panels::page>