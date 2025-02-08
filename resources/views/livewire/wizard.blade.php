<div>
   
@if(!empty($successMessage))
<div class="alert alert-success">
   {{ $successMessage }}
</div>
@endif
  
<div class="stepwizard">
    <div class="stepwizard-row setup-panel">
        <div class="stepwizard-step">
            <a href="#step-1" type="button" class="btn btn-circle {{ $currentStep != 1 ? 'btn-default' : 'btn-primary' }}">1</a>
            <p>Step 1</p>
        </div>
        <div class="stepwizard-step">
            <a href="#step-2" type="button" class="btn btn-circle {{ $currentStep != 2 ? 'btn-default' : 'btn-primary' }}">2</a>
            <p>Step 2</p>
        </div>
        <div class="stepwizard-step">
            <a href="#step-3" type="button" class="btn btn-circle {{ $currentStep != 3 ? 'btn-default' : 'btn-primary' }}" disabled="disabled">3</a>
            <p>Step 3</p>
        </div>
    </div>
</div>
  
    <div class="row setup-content {{ $currentStep != 1 ? 'displayNone' : '' }}" id="step-1">
        <div class="col-xs-12">
            <div class="col-md-12">
            <h2>Step 1: Seleziona Sede, Piano e Scrivania</h2>

                <label for="workplace">Sede</label>
                <select id="workplace" wire:model="workplace_id" wire:change="changeworkplace">
                    <option value="" disabled selected>Seleziona una sede</option>
                    @foreach ($workplaces as $workplace)
                        <option value="{{ $workplace->id }}">{{ $workplace->name }}</option>
                    @endforeach
                </select>

                <label for="plan">Piano</label>
                <select id="plan" wire:model="plan_id">
                    <option value="" disabled selected>Seleziona un piano</option>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan['id'] }}">{{ $plan['name'] }}</option>
                    @endforeach
                </select>

                <label for="desk">Scrivania</label>
                <select id="desk" wire:model="desk_id">
                    <option value="" disabled selected>Seleziona una scrivania</option>
                    @foreach ($desks as $desk)
                        <option value="{{ $desk->id }}">{{ $desk->name }}</option>
                    @endforeach
                </select>
  
                <button class="btn btn-primary nextBtn btn-lg pull-right" wire:click="firstStepSubmit" type="button" >Next</button>
            </div>
        </div>
    </div>
    <div class="row setup-content {{ $currentStep != 2 ? 'displayNone' : '' }}" id="step-2">
        <div class="col-xs-12">
            <div class="col-md-12">
                <h3> Step 2</h3>
  
                <h2>Step 2: Seleziona le Date</h2>

                <label for="start_date">Data Inizio</label>
                <input type="date" id="start_date" wire:model="start_date">

                <label for="end_date">Data Fine</label>
                <input type="date" id="end_date" wire:model="end_date">

                <button wire:click="previousStep">Indietro</button>
                <button wire:click="nextStep">Prossimo</button>
  
                <button class="btn btn-primary nextBtn btn-lg pull-right" type="button" wire:click="secondStepSubmit">Next</button>
                <button class="btn btn-danger nextBtn btn-lg pull-right" type="button" wire:click="back(1)">Back</button>
            </div>
        </div>
    </div>
    <div class="row setup-content {{ $currentStep != 3 ? 'displayNone' : '' }}" id="step-3">
        <div class="col-xs-12">
            <div class="col-md-12">
                <h3> Step 3</h3>
  
                <h2>Step 3: Conferma Prenotazione</h2>

                <p><strong>Sede:</strong> {{ optional($workplaces->find($workplace_id))->name }}</p>
                <p><strong>Piano:</strong> {{ optional(collect($plans)->firstWhere('id', $plan_id))->name }}</p>
                <p><strong>Scrivania:</strong> {{ optional(collect($desks)->firstWhere('id', $desk_id))->name }}</p>
                <p><strong>Data Inizio:</strong> {{ $start_date }}</p>
                <p><strong>Data Fine:</strong> {{ $end_date }}</p>
  
                <button class="btn btn-success btn-lg pull-right" wire:click="submitForm" type="button">Finish!</button>
                <button class="btn btn-danger nextBtn btn-lg pull-right" type="button" wire:click="back(2)">Back</button>
            </div>
        </div>
    </div>
</div>