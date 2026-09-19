<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col gap-2">
            <h2 class="text-lg font-semibold tracking-tight text-gray-950">
                Gestione spazi e presenze
            </h2>
            <p class="text-sm leading-6 text-gray-600">
                Da questo pannello amministri postazioni, team e anagrafiche di APPosto.
                I numeri sotto si riferiscono alla <strong>giornata odierna</strong>
                ({{ now()->translatedFormat('l d F Y') }}): prenotazioni confermate,
                persone in ufficio, assenze e richieste di progetto da evadere.
            </p>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
