<x-app-layout>
@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@stop

<div class="container">
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <h2>Step 2: Seleziona il Piano e la Scrivania</h2>
    <br />
    <p><strong>Sede selezionata:</strong> {{ $workplace->name }}</p>
    <p>
        <strong>Hai scelto</strong><br />
        <strong>DA: </strong> {{ Carbon\Carbon::parse($request->start_date)->format('d-m-Y') }} - {{ $request->start_time }}<br />
        <strong>A: </strong> {{ Carbon\Carbon::parse($request->end_date)->format('d-m-Y') }} - {{ $request->end_time }}
    </p>

    <h4>Seleziona un piano:</h4>
    <div class="btn-group" role="group">
        @foreach (Auth::user()->team->plans->where('workplace_id', $workplace->id) as $plan)
            <button type="button" class="btn btn-primary select-plan" data-plan-id="{{ $plan->id }}">{{ $plan->description }}</button>
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

    <form method="POST" action="{{ route('booking.step.three') }}" class="mt-4">
        @csrf
        <input type="hidden" name="desk_id" id="selectedDesk">
        @if($canBookExclusive)
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="is_exclusive" id="isExclusive" value="1">
                <label class="form-check-label" for="isExclusive">
                    <strong>Prenotazione esclusiva</strong>
                    <small class="d-block text-muted">
                        Riserva l'intera area al tuo team: nessun altro potrà prenotare nello stesso periodo.
                    </small>
                </label>
            </div>
        @endif
        <a class="btn btn-warning" href="{{ route('booking.step.one') }}" role="button">Precedente</a>
        <button class="btn btn-primary" type="submit" id="confirmButton" disabled>Conferma</button>
    </form>
</div>

@section('js')
<script>
document.addEventListener("DOMContentLoaded", function () {
    let selectedDeskId = null;
    let currentPlanId = null;
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute("content");

    function loadDesks(planId) {
        currentPlanId = planId;
        const deskList = document.getElementById('desk-list');
        deskList.innerHTML = '<p class="text-muted">Caricamento...</p>';

        fetch(`/api/desks/${planId}`)
            .then(response => response.json())
            .then(data => {
                deskList.innerHTML = '';

                const planTitle = document.createElement('h5');
                planTitle.textContent = `Scrivanie per il piano: ${data.plan.description}`;
                deskList.appendChild(planTitle);

                const planImage = document.getElementById('plan-image');
                if (data.plan.cover_image) {
                    planImage.src = '/storage/' + data.plan.cover_image;
                    planImage.style.display = 'block';
                } else {
                    planImage.style.display = 'none';
                }

                if (data.is_exclusive) {
                    deskList.innerHTML += `<div class="alert alert-warning">${data.message || "L'area è prenotata in modo esclusivo."}</div>`;
                    document.getElementById('confirmButton').disabled = true;
                    document.getElementById('selectedDesk').value = '';
                    return;
                }

                if (!data.desks.length) {
                    deskList.innerHTML += '<p class="text-muted">Nessuna scrivania disponibile per questo piano.</p>';
                    return;
                }

                data.desks.forEach(desk => {
                    const deskItem = document.createElement('div');
                    deskItem.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                    deskItem.setAttribute('data-desk-id', desk.id);

                    const deskInfo = document.createElement('span');
                    deskInfo.textContent = desk.identifier;

                    if (!desk.is_available) {
                        deskInfo.classList.add('text-danger');
                        deskInfo.textContent += ` (Occupata da ${desk.occupied_by})`;

                        if (desk.can_override) {
                            const overrideButton = document.createElement('button');
                            overrideButton.type = 'button';
                            overrideButton.className = 'btn btn-warning btn-sm';
                            overrideButton.textContent = 'Prendi postazione';
                            overrideButton.addEventListener('click', function () {
                                overrideDesk(desk.id, planId);
                            });
                            deskItem.appendChild(overrideButton);
                        }
                    } else {
                        deskItem.classList.add('btn-success');
                        deskItem.addEventListener('click', function () {
                            selectedDeskId = desk.id;
                            document.getElementById('selectedDesk').value = desk.id;
                            document.getElementById('confirmButton').disabled = false;
                            document.querySelectorAll('.list-group-item').forEach(item => item.classList.remove('active'));
                            this.classList.add('active');
                        });

                        if (desk.id == selectedDeskId) {
                            deskItem.classList.add('active');
                        }
                    }

                    deskItem.appendChild(deskInfo);
                    deskList.appendChild(deskItem);
                });
            })
            .catch(() => {
                deskList.innerHTML = '<div class="alert alert-danger">Errore nel caricamento delle scrivanie.</div>';
            });
    }

    document.querySelectorAll('.select-plan').forEach(button => {
        button.addEventListener('click', function () {
            document.querySelectorAll('.select-plan').forEach(btn => btn.classList.remove('btn-dark'));
            this.classList.add('btn-dark');
            loadDesks(this.getAttribute('data-plan-id'));
        });
    });

    function overrideDesk(deskId, planId) {
        if (!confirm("Vuoi prendere questa postazione? La prenotazione attuale verrà sostituita.")) {
            return;
        }

        fetch(`/override-desk/${deskId}`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrf,
            },
            body: JSON.stringify({})
        })
        .then(response => response.json().then(data => ({ ok: response.ok, data })))
        .then(({ ok, data }) => {
            if (ok && data.success) {
                selectedDeskId = deskId;
                loadDesks(planId);
                document.getElementById('selectedDesk').value = deskId;
                document.getElementById('confirmButton').disabled = false;
            } else {
                alert(data.message || "Non puoi prendere questa postazione.");
            }
        })
        .catch(() => {
            alert("Si è verificato un errore. Riprova.");
        });
    }
});
</script>
@stop
</x-app-layout>
