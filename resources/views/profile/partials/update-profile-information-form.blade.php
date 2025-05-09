<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Modifica il tuo Profilo
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Aggiorna le tue informazioni personali
        </p>
    </header>

    <!--<form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>-->

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="phone" :value="__('Telefono')" />
            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $user->phone)" autofocus autocomplete="phone" />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>


        <div class="mt-3">
            <x-input-label for="allow_view" :value="__('Consenti la condivisione delle tue info')" />
            <input type="checkbox" name="allow_view" id="allow_view"  {{  ($user->allow_view == 1 ? ' checked' : '') }} />
            <x-input-error class="mt-2" :messages="$errors->get('allow_view')" />
        </div>
        @if( Auth::user()->gestiamopresenze )

        <div class="  mb-3">
                <x-input-label for="default_workstation_id" :value="'Postazione Preferita: '" />
                <select name="default_workstation_id" id="default_workstation_id" class="form-select">
                    @if (!$user->defaultWorkstation?->identifier)
                    <option value="-" selected >Nessuna assegnata</option>
                    @endif 
                    @foreach($availableWorkstations as $workstation)
                        <option value="{{ $workstation->id }}" {{ $user->default_workstation_id == $workstation->id ? 'selected' : '' }}>
                            {{ $workstation->identifier }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
        
        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Salva') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Salvato.') }}</p>
            @endif
        </div>
    </form>
</section>
