<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Workplace;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Wizard extends Component
{
    public $currentStep = 1; // Step iniziale del wizard

    // Dati raccolti durante il wizard
    public $workplace_id;
    public $plan_id;
    public $desk_id;
    public $start_date;
    public $end_date;

    // Elenco delle sedi, piani e scrivanie
    public $workplaces = [];
    public $plans = [];
    public $desks = [];

    public function mount()
    {
        // Recupera le sedi con piani e scrivanie
        $this->workplaces = Workplace::with('plans.desks')->get()->keyBy('id');
        $this->plans = collect(); // Inizializza i piani come vuoti
        $this->desks = collect(); // Inizializza le scrivanie come vuote
    }

    public function render()
    {
        return view('livewire.wizard');
    }

    public function changeworkplace()
{
    Log::info("lòl");
    Log::info( $this->workplaces);
    $workplace = ($this->workplaces)->get($this->workplace_id);

    if ($workplace) {
        $this->plans = $workplace->plans;
    } else {
        $this->plans = collect();
    }

    // Resetta i valori dei piani e scrivanie selezionati
    $this->plan_id = null;
    $this->desks = collect();
    $this->desk_id = null;
}

    public function updatedWorkplaceId()
    {
        // Trova la sede selezionata
       
    }

    public function updatedPlanId()
    {
        // Trova il piano selezionato
        $plan = collect($this->plans)->firstWhere('id', $this->plan_id);

        if ($plan) {
            $this->desks = $plan->desks;
        } else {
            $this->desks = collect();
        }

        // Resetta il valore della scrivania selezionata
        $this->desk_id = null;
    }

    public function nextStep()
    {
        $this->validateStep(); // Validazione per lo step corrente
        $this->currentStep++;
    }

    public function previousStep()
    {
        $this->currentStep--;
    }

    public function validateStep()
    {
        if ($this->currentStep == 1) {
            $this->validate([
                'workplace_id' => 'required|exists:workplaces,id',
                'plan_id' => 'required|exists:plans,id',
                'desk_id' => 'required|exists:desks,id',
            ]);
        }

        if ($this->currentStep == 2) {
            $this->validate([
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);
        }
    }

    public function completeBooking()
    {
        $this->validateStep(); // Validazione finale

        // Crea la prenotazione
        Booking::create([
            'user_id' => Auth::id(),
            'desk_id' => $this->desk_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ]);

        // Messaggio di successo
        session()->flash('success', 'Prenotazione completata!');
        return redirect()->route('booking.wizard');
    }
}
