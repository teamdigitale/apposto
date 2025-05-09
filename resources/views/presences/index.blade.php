<x-app-layout>
@section('css')
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.js'></script>
@stop

<div class="container mt-4">
    <div class="row">
        <!-- Colonna sinistra: Calendario -->
        <div class="col-md-8">
            <div id="calendar"></div>
        </div>

        <!-- Colonna destra: Modifica selezione giorno -->
        <div class="col-md-4">
            <h4>Modifica Presenza Giorno</h4>
            <form id="presence-form">
                @csrf
                <div class="form-group mb-3">
                    <input type="text" id="selected-date" class="form-control" placeholder="Seleziona un giorno" readonly>
                </div>

                <div class="form-group mb-3">
                    <select id="status" class="form-select">
                        <option value="presente">Presente</option>
                        <option value="ferie">Ferie</option>
                        <option value="smart_working">Smart Working</option>
                        <option value="permesso">Permesso</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Salva Modifiche</button>
            </form>
        </div>
    </div>
</div>

@section('js')
<script>
         const savedPresences = @json($events);
         const holidayEvents = @json($holidays);

document.addEventListener('DOMContentLoaded', function () {
    let selectedDates = [...savedPresences.map(p => ({ date: p.start, status: p.status }))];

    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'it',
        weekends: false,
        selectable: true,
        events: savedPresences.map(p => ({
            title: p.title,
            start: p.start,
            backgroundColor: getColorByStatus(p.status),
            borderColor: getColorByStatus(p.status),
            allDay: true
        })),
        ...holidayEvents,
        select: function (info) {
            const clickedDate = info.startStr;
            const isHoliday = holidayEvents.some(h => h.start === clickedDate);
            
            if (isHoliday) {
                alert("Giorno festivo: non puoi selezionare questa data.");
                return;
            }

            // Continua con selezione normale
            document.getElementById('selected-date').value = formatDateToItalian(clickedDate);
            document.getElementById('selected-date').dataset.dateOriginal = clickedDate;

            const existing = selectedDates.find(item => item.date === clickedDate);
            document.getElementById('status').value = existing ? existing.status : 'presente';
        }
    });

    calendar.render();

    document.getElementById('presence-form').addEventListener('submit', function (e) {
        e.preventDefault();

        const date = document.getElementById('selected-date').dataset.dateOriginal;
        const status = document.getElementById('status').value;

        if (!date) {
            alert('Seleziona un giorno dal calendario.');
            return;
        }

        // Rimuovi il precedente se già esiste
        selectedDates = selectedDates.filter(item => item.date !== date);
        removeExistingEvent(date);

        // Aggiungi nuovo stato
        selectedDates.push({ date, status });

        calendar.addEvent({
            title: status.replace('_', ' '),
            start: date,
            backgroundColor: getColorByStatus(status),
            borderColor: getColorByStatus(status),
            allDay: true
        });

        // Invia via AJAX
        fetch("{{ route('presences.store') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                date: date,
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            //alert('Presenze salvate correttamente!');
            document.getElementById('selected-date').value = '';
        })
        .catch(error => {
            console.error('Errore:', error);
            alert('Errore durante il salvataggio.');
        });
    });

    function removeExistingEvent(date) {
        calendar.getEvents().forEach(event => {
            if (event.startStr === date) event.remove();
        });
    }

    function formatDateToItalian(dateStr) {
        const parts = dateStr.split('-'); // ['2025', '04', '29']
        return `${parts[2]}-${parts[1]}-${parts[0]}`; // '29-04-2025'
    }

    function getColorByStatus(status) {
        switch (status) {
            case 'presente': return '#28a745';
            case 'ferie': return '#ffc107';
            case 'smart_working': return '#17a2b8';
            case 'permesso': return '#dc3545';
            default: return '#6c757d';
        }
    }
});
</script>
@stop
</x-app-layout>
