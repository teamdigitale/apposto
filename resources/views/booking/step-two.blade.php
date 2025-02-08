<x-app-layout>
@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@stop

<div class="container">
<h2>Step 2: Seleziona il Piano e la Scrivania</h2>
<br />
<p><strong>Sede selezionata:</strong> {{ $workplace->name }}</p>
<p>
    <strong>Hai scelto</strong><br />
    <strong>DA: </strong> {{ Carbon\Carbon::parse($request->start_date)->format('d-m-Y') }} - {{$request->start_time}}<br />
    <strong>A: </strong> {{ Carbon\Carbon::parse($request->end_date)->format('d-m-Y h:m') }} - {{$request->end_time}}
</p>

<h4>Seleziona un piano:</h4>
<div class="btn-group" role="group">
    @foreach (Auth::user()->team->plans->where('workplace_id',$workplace->id) as $plan)
        <button class="btn btn-primary select-plan" data-plan-id="{{ $plan->id }}">{{ $plan->description }}</button>
    @endforeach
</div>

<div class="row">
    <div class="col-md-6">
        <h4 class="mt-4">Scrivanie disponibili:</h4>
        <div id="desk-list" class="list-group">
            <p class="text-muted">Seleziona un piano per vedere le scrivanie disponibili.</p>
        </div>
    </div>
    <div class="col-md-6">
        <h4 class="mt-4">Mappa del Piano:</h4>
        <img id="plan-image" src="" class="img-fluid" style="display: none; max-width: 100%;" alt="Mappa del Piano">
    </div>
</div>

<!--
<h4 class="mt-4">Scrivanie disponibili:</h4>
<div id="desk-list" class="list-group">
    <p class="text-muted">Seleziona un piano per vedere le scrivanie disponibili.</p>
</div>-->

<form method="POST" action="{{ route('booking.step.three') }}">
    @csrf
    <input type="hidden" name="desk_id" id="selectedDesk">
    <a class="btn btn-warning" href="{{ url()->previous() }}" role="button">Precedente</a>
    <button class="btn btn-primary" type="submit" id="confirmButton" disabled>Conferma</button>
</form>
</div>

@section('js')
<script>
document.addEventListener("DOMContentLoaded", function () {
    let selectedDeskId = null;
    let currentPlanId = null; // Per tenere traccia del piano selezionato

    // Carica le scrivanie dinamicamente
    function loadDesks(planId) {
        currentPlanId = planId;

        fetch(`/api/desks/${planId}`)
            .then(response => response.json())
            .then(data => {
                let deskList = document.getElementById('desk-list');
                deskList.innerHTML = '';

                let planTitle = document.createElement('h5');
                planTitle.textContent = `Scrivanie per il piano: ${data.plan.description}`;
                deskList.appendChild(planTitle);


                let planImage = document.getElementById('plan-image');
                console.log(planImage);

                if (data.plan.cover_image) {

                    planImage.src = '/storage/'+data.plan.cover_image;
                    planImage.style.display = 'block';

                } else {

                    planImage.style.display = 'none';

                }

                if (data.desks.length === 0) {
                    deskList.innerHTML += '<p class="text-muted">Nessuna scrivania disponibile per questo piano.</p>';
                } else {
                    data.desks.forEach(desk => {
                        let deskItem = document.createElement('div');
                        deskItem.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                        deskItem.setAttribute('data-desk-id', desk.id);

                        let deskInfo = document.createElement('span');
                        deskInfo.textContent = desk.identifier;

                        if (!desk.is_available) {
                            deskInfo.classList.add('text-danger');
                            deskInfo.textContent += ` (Occupata da ${desk.occupied_by})`;

                            if (desk.can_override) {
                                let overrideButton = document.createElement('button');
                                overrideButton.className = 'btn btn-warning btn-sm';
                                overrideButton.textContent = 'Ruba Prenotazione';
                                overrideButton.addEventListener('click', function () {
                                    overrideDesk(desk.id, planId);
                                });

                                deskItem.appendChild(overrideButton);
                            }
                        } else {
                            //deskInfo.classList.add('text-success');
                            deskItem.classList.add('btn-success');
                            deskItem.addEventListener('click', function () {
                                selectedDeskId = desk.id;
                                document.getElementById('selectedDesk').value = desk.id;
                                document.getElementById('confirmButton').disabled = false;

                                document.querySelectorAll('.list-group-item').forEach(item => item.classList.remove('active'));
                                this.classList.add('active');
                            });

                            // Se questa scrivania era selezionata prima, riattivarla
                            if (desk.id == selectedDeskId) {
                                deskItem.classList.add('active');
                            }
                        }

                        deskItem.appendChild(deskInfo);
                        deskList.appendChild(deskItem);
                    });
                }
            });
    }

    // Assegna eventi ai bottoni dei piani
    document.querySelectorAll('.select-plan').forEach(button => {
        button.addEventListener('click', function () {
            let planId = this.getAttribute('data-plan-id');

            document.querySelectorAll('.select-plan').forEach(btn => btn.classList.remove('btn-dark'));
            this.classList.add('btn-dark');

            loadDesks(planId);
        });
    });

    // Funzione per rubare una prenotazione
    function overrideDesk(deskId, planId) {
        if (!confirm("Sei sicuro di voler rubare questa postazione?")) {
            return;
        }
        console.log(deskId);

        console.log(planId);
        
        fetch(`/override-desk/${deskId}`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
            },
            body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(data => {
           // console.log(data.success);
            if (data.success) {
                alert("Postazione rubata con successo!");
                console.log("kkkk");
                // Mantieni la selezione se c'era
                let previousDeskId = deskId;
                console.log(previousDeskId);

                // Ricarica solo le scrivanie, senza perdere la selezione del piano
                loadDesks(planId);

                // Riattiva il bottone di conferma se necessario
                if (previousDeskId) {
                    document.getElementById('selectedDesk').value = previousDeskId;
                    document.getElementById('confirmButton').disabled = false;
                }
            } else {
                alert("Errore: " + data.message);
            }
        })
        .catch(error => {
            console.error("Errore nella richiesta:", error);
            alert("Si è verificato un errore. Riprova.");
        });
    }
});
</script>
@stop
</x-app-layout>
