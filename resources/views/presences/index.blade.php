<x-app-layout>
@section('css')
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .fc-day-selected {
            background-color: #d1ecf1 !important;
            border: 2px solid #0dcaf0 !important;
        }
        .date-range-indicator {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background-color: #0dcaf0;
            color: white;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            margin-left: 0.5rem;
        }
    </style>
@stop

<div class="container mt-4">
    <!-- Alert per feedback -->
    <div id="alert-container"></div>

    <div class="row">
        <!-- Colonna sinistra: Calendario -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar3"></i> Calendario Presenze
                    </h5>
                </div>
                <div class="card-body">
                    <div id="calendar"></div>
                    
                    <div class="mt-3 p-3 bg-light border rounded">
                        <h6 class="mb-2"><i class="bi bi-info-circle"></i> Legenda</h6>
                        <div class="d-flex flex-wrap gap-3">
                            <span><span class="badge" style="background-color: #28a745;">P</span> Presente</span>
                            <span><span class="badge" style="background-color: #ffc107;">F</span> Ferie</span>
                            <span><span class="badge" style="background-color: #17a2b8;">SW</span> Smart Working</span>
                            <span><span class="badge" style="background-color: #dc3545;">Pe</span> Permesso</span>
                            <span><span class="badge" style="background-color: #f8d7da;">⚠️</span> Festività</span>
                            <span><span class="badge" style="background-color: #6c757d;">🚫</span> Colleghi Assenti</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonna destra: Modifica range di date -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-pencil-square"></i> Gestione Presenze
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-lightbulb"></i> <strong>Come selezionare:</strong><br>
                        <strong>Opzione 1:</strong> Trascina col mouse sul calendario (drag)<br>
                        <strong>Opzione 2:</strong> Clicca due volte (inizio + fine)<br>
                        <strong>Opzione 3:</strong> Compila manualmente i campi data sotto<br>
                        <hr class="my-2">
                        <i class="bi bi-pencil"></i> <strong>Per modificare/eliminare:</strong> Clicca su una presenza già salvata (badge P, F, SW, Pe)
                    </div>

                    <form id="presence-form">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Data Inizio</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar-range"></i></span>
                                <input type="date" 
                                       id="start-date" 
                                       name="start_date"
                                       class="form-control"
                                       min="{{ now()->format('Y-m-d') }}"
                                       required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Data Fine</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar-check"></i></span>
                                <input type="date" 
                                       id="end-date" 
                                       name="end_date"
                                       class="form-control"
                                       min="{{ now()->format('Y-m-d') }}"
                                       required>
                            </div>
                            <small class="text-muted">
                                <span id="days-count" class="date-range-indicator" style="display: none;">
                                    <i class="bi bi-calendar2-week"></i> <span id="days-number">0</span> giorni lavorativi
                                </span>
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label fw-bold">
                                <i class="bi bi-list-check"></i> Tipo Presenza
                            </label>
                            <select id="status" name="status" class="form-select form-select-lg">
                                <option value="presente">🏢 Presente in Ufficio</option>
                                <option value="ferie">🏖️ Ferie</option>
                                <option value="smart_working">💻 Smart Working</option>
                                <option value="permesso">⏰ Permesso</option>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg" id="save-btn" disabled>
                                <i class="bi bi-save"></i> Salva Periodo
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="clear-selection">
                                <i class="bi bi-x-circle"></i> Cancella Selezione
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Card Info Ferie -->
            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-info-circle"></i> Info Ferie</h6>
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 class="text-primary">{{ Auth::user()->ferie_totali }}</h4>
                            <small class="text-muted">Totali</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-danger">{{ Auth::user()->ferie_usate }}</h4>
                            <small class="text-muted">Usate</small>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <h3 class="text-success mb-0">{{ Auth::user()->remaining_leave_days }}</h3>
                        <small class="text-muted">Giorni Disponibili</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sezione Statistiche e Grafici -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up"></i> Statistiche Presenze
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Grafico Torta - Distribuzione Anno Corrente -->
                        <div class="col-md-4">
                            <h6 class="text-center mb-3">Distribuzione {{ now()->year }}</h6>
                            <canvas id="pieChart" style="max-height: 250px;"></canvas>
                        </div>
                        
                        <!-- Grafico Barre - Trend Ultimi 6 Mesi -->
                        <div class="col-md-8">
                            <h6 class="text-center mb-3">Trend Ultimi 6 Mesi</h6>
                            <canvas id="trendChart" style="max-height: 250px;"></canvas>
                        </div>
                    </div>
                    
                    <!-- Statistiche Numeriche -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <div class="row text-center">
                                    <div class="col-3">
                                        <h4 id="stat-ferie" class="mb-0">-</h4>
                                        <small>🏖️ Ferie</small>
                                    </div>
                                    <div class="col-3">
                                        <h4 id="stat-smart" class="mb-0">-</h4>
                                        <small>💻 Smart Working</small>
                                    </div>
                                    <div class="col-3">
                                        <h4 id="stat-permesso" class="mb-0">-</h4>
                                        <small>⏰ Permessi</small>
                                    </div>
                                    <div class="col-3">
                                        <h4 id="stat-presente" class="mb-0">-</h4>
                                        <small>🏢 Presente</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('js')
<script>
const savedPresences = @json($events);
const holidayEvents = @json($holidays);
const colleagueEvents = @json($colleagueEvents ?? []);

document.addEventListener('DOMContentLoaded', function () {
    const startDateInput = document.getElementById('start-date');
    const endDateInput = document.getElementById('end-date');
    const saveBtn = document.getElementById('save-btn');

    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'it',
        weekends: false,           // ✅ NASCONDI SABATO E DOMENICA
        selectable: true,          // ✅ ABILITA SELEZIONE
        selectMirror: true,        // ✅ Mostra preview durante il drag
        unselectAuto: false,       // ✅ Non deseleziona automaticamente
        
        // Eventi salvati + festività + colleghi
        events: [
            ...savedPresences.map(p => ({
                title: getShortTitle(p.status),
                start: p.start,
                backgroundColor: getColorByStatus(p.status),
                borderColor: getColorByStatus(p.status),
                allDay: true
            })),
            ...holidayEvents,
            ...colleagueEvents
        ],
        
        // Gestione DRAG per selezionare range
        select: function(info) {
            const start = info.startStr;
            
            // FullCalendar include un giorno in più nell'end, lo correggiamo
            const endParts = info.endStr.split('-');
            const endDate = new Date(
                parseInt(endParts[0]),
                parseInt(endParts[1]) - 1,
                parseInt(endParts[2]) - 1
            );
            
            const year = endDate.getFullYear();
            const month = String(endDate.getMonth() + 1).padStart(2, '0');
            const day = String(endDate.getDate()).padStart(2, '0');
            const end = `${year}-${month}-${day}`;
            
            // Popola i campi
            startDateInput.value = start;
            endDateInput.value = end;
            
            // Trigger change per aggiornare il conteggio
            startDateInput.dispatchEvent(new Event('change'));
            endDateInput.dispatchEvent(new Event('change'));
        },
        
        // ✅ CLICK SU EVENTO ESISTENTE PER MODIFICARE/ELIMINARE
        eventClick: function(info) {
            // Se è festività, mostra solo nome
            if (info.event.display === 'background') {
                showAlert(`ℹ️ Festività: ${info.event.title}`, 'info');
                return;
            }
            
            // Se è un collega, mostra info senza permettere modifica
            if (info.event.extendedProps.type === 'colleague') {
                const userName = info.event.extendedProps.userName;
                const status = info.event.extendedProps.status === 'ferie' ? 'Ferie' : 'Permesso';
                const date = formatDate(info.event.startStr);
                showAlert(`ℹ️ ${userName} - ${status} il ${date}`, 'info');
                return;
            }
            
            const eventDate = info.event.startStr;
            const eventStatus = info.event.extendedProps.status;
            
            // Mostra modal di conferma per proprie presenze
            if (confirm(`Presenza del ${formatDate(eventDate)}: ${getStatusLabel(eventStatus)}\n\nVuoi eliminare questa presenza?`)) {
                deletePresence(eventDate, info.event);
            }
        }
    });

    calendar.render();

    // Quando cambiano le date, evidenzia il range e calcola giorni
    startDateInput.addEventListener('change', updateRangeHighlight);
    endDateInput.addEventListener('change', updateRangeHighlight);

    function updateRangeHighlight() {
        const start = startDateInput.value;
        const end = endDateInput.value;
        
        // Rimuovi evidenziazioni precedenti
        document.querySelectorAll('.fc-day-selected').forEach(el => {
            el.classList.remove('fc-day-selected');
        });
        
        if (!start) {
            document.getElementById('days-count').style.display = 'none';
            saveBtn.disabled = true;
            return;
        }
        
        // Se solo start, evidenzia solo quello
        if (!end) {
            const dayEl = document.querySelector(`[data-date="${start}"]`);
            if (dayEl) dayEl.classList.add('fc-day-selected');
            
            const workDays = countWorkDays(start, start);
            document.getElementById('days-number').textContent = workDays;
            document.getElementById('days-count').style.display = 'inline-block';
            saveBtn.disabled = false;
            return;
        }
        
        // Validazione: end >= start
        if (new Date(end) < new Date(start)) {
            showAlert('⚠️ La data fine deve essere successiva alla data inizio', 'warning');
            endDateInput.value = '';
            return;
        }
        
        // Evidenzia range completo
        const startDate = new Date(start);
        const endDate = new Date(end);
        let current = new Date(startDate);
        
        while (current <= endDate) {
            const dateStr = current.toISOString().split('T')[0];
            const dayEl = document.querySelector(`[data-date="${dateStr}"]`);
            if (dayEl) {
                dayEl.classList.add('fc-day-selected');
            }
            current.setDate(current.getDate() + 1);
        }
        
        // Calcola e mostra giorni lavorativi
        const workDays = countWorkDays(start, end);
        document.getElementById('days-number').textContent = workDays;
        document.getElementById('days-count').style.display = 'inline-block';
        
        if (workDays === 0) {
            showAlert('⚠️ Nessun giorno lavorativo nel periodo (solo weekend/festivi)', 'warning');
            saveBtn.disabled = true;
        } else {
            saveBtn.disabled = false;
            
            // Verifica se ci sono festività nel range
            const hasHoliday = holidayEvents.some(h => {
                const holidayDate = new Date(h.start);
                return holidayDate >= startDate && holidayDate <= endDate;
            });
            if (hasHoliday) {
                showAlert('ℹ️ Il periodo contiene festività (saranno escluse automaticamente)', 'info');
            }
        }
    }

    // Form submit
    document.getElementById('presence-form').addEventListener('submit', function (e) {
        e.preventDefault();

        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        const status = document.getElementById('status').value;

        if (!startDate || !endDate) {
            showAlert('⚠️ Compila entrambe le date.', 'warning');
            return;
        }

        const workDays = countWorkDays(startDate, endDate);
        if (workDays === 0) {
            showAlert('⚠️ Nessun giorno lavorativo nel periodo selezionato.', 'warning');
            return;
        }

        // Disabilita pulsante
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvataggio...';

        // Invia richiesta
        fetch("{{ route('presences.store') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                start_date: startDate,
                end_date: endDate,
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.dates && data.dates.length > 0) {
                showAlert(`✅ ${data.count} giorni salvati con successo!`, 'success');
                
                // Aggiorna calendario
                data.dates.forEach(date => {
                    // Rimuovi evento esistente
                    calendar.getEvents().forEach(event => {
                        if (event.startStr === date) {
                            event.remove();
                        }
                    });
                    
                    // Aggiungi nuovo evento
                    calendar.addEvent({
                        title: getShortTitle(status),
                        start: date,
                        backgroundColor: getColorByStatus(status),
                        borderColor: getColorByStatus(status),
                        allDay: true
                    });
                });
                
                // Reset form
                clearSelection();
                
                // Ricarica pagina per aggiornare contatori ferie
                setTimeout(() => location.reload(), 1500);
            } else if (data.message) {
                showAlert(data.message, 'info');
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="bi bi-save"></i> Salva Periodo';
            }
        })
        .catch(error => {
            console.error('Errore:', error);
            showAlert('❌ Errore durante il salvataggio.', 'danger');
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="bi bi-save"></i> Salva Periodo';
        });
    });

    // Pulsante cancella
    document.getElementById('clear-selection').addEventListener('click', clearSelection);

    function clearSelection() {
        startDateInput.value = '';
        endDateInput.value = '';
        document.getElementById('days-count').style.display = 'none';
        saveBtn.disabled = true;
        
        document.querySelectorAll('.fc-day-selected').forEach(el => {
            el.classList.remove('fc-day-selected');
        });
        
        calendar.unselect();
    }

    function countWorkDays(start, end) {
        const startDate = new Date(start);
        const endDate = new Date(end);
        let count = 0;
        
        let current = new Date(startDate);
        while (current <= endDate) {
            const day = current.getDay();
            const dateStr = current.toISOString().split('T')[0];
            
            // Escludi weekend
            if (day !== 0 && day !== 6) {
                // Escludi festività
                const isHoliday = holidayEvents.some(h => h.start === dateStr);
                if (!isHoliday) {
                    count++;
                }
            }
            
            current.setDate(current.getDate() + 1);
        }
        
        return count;
    }

    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.getElementById('alert-container');
        container.appendChild(alertDiv);
        
        setTimeout(() => {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 150);
        }, 5000);
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

    function getShortTitle(status) {
        switch (status) {
            case 'presente': return 'P';
            case 'ferie': return 'F';
            case 'smart_working': return 'SW';
            case 'permesso': return 'Pe';
            default: return status;
        }
    }
    
    // ==========================================
    // FUNZIONI HELPER PER MODIFICA/ELIMINAZIONE
    // ==========================================
    
    function formatDate(dateStr) {
        const d = new Date(dateStr);
        return d.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }
    
    function getStatusLabel(status) {
        const labels = {
            'presente': '🏢 Presente',
            'ferie': '🏖️ Ferie',
            'smart_working': '💻 Smart Working',
            'permesso': '⏰ Permesso'
        };
        return labels[status] || status;
    }
    
    function deletePresence(date, eventObj) {
        // Redirect diretto alla route di eliminazione
        window.location.href = `/presences/${date}/delete`;
    }

    // ==========================================
    // CARICAMENTO GRAFICI E STATISTICHE
    // ==========================================
    
    let pieChart, trendChart;
    
    function loadCharts() {
        fetch("{{ route('presences.stats') }}?year={{ now()->year }}&month={{ now()->month }}")
            .then(response => response.json())
            .then(data => {
                updateStats(data);
                createPieChart(data.percentages);
                createTrendChart(data.trend);
            })
            .catch(error => {
                console.error('Errore caricamento statistiche:', error);
            });
    }
    
    function updateStats(data) {
        const yearStats = data.year_stats;
        
        document.getElementById('stat-ferie').textContent = yearStats.ferie || 0;
        document.getElementById('stat-smart').textContent = yearStats.smart_working || 0;
        document.getElementById('stat-permesso').textContent = yearStats.permesso || 0;
        document.getElementById('stat-presente').textContent = yearStats.presente || 0;
    }
    
    function createPieChart(percentages) {
        const ctx = document.getElementById('pieChart').getContext('2d');
        
        if (pieChart) {
            pieChart.destroy();
        }
        
        pieChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Presente', 'Ferie', 'Smart Working', 'Permesso'],
                datasets: [{
                    data: [
                        percentages.presente || 0,
                        percentages.ferie || 0,
                        percentages.smart_working || 0,
                        percentages.permesso || 0
                    ],
                    backgroundColor: ['#28a745', '#ffc107', '#17a2b8', '#dc3545'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 10,
                            font: { size: 11 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + '%';
                            }
                        }
                    }
                }
            }
        });
    }
    
    function createTrendChart(trend) {
        const ctx = document.getElementById('trendChart').getContext('2d');
        
        if (trendChart) {
            trendChart.destroy();
        }
        
        trendChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: trend.map(t => t.month),
                datasets: [
                    {
                        label: 'Presente',
                        data: trend.map(t => t.presente),
                        backgroundColor: '#28a745',
                        borderWidth: 1
                    },
                    {
                        label: 'Ferie',
                        data: trend.map(t => t.ferie),
                        backgroundColor: '#ffc107',
                        borderWidth: 1
                    },
                    {
                        label: 'Smart Working',
                        data: trend.map(t => t.smart_working),
                        backgroundColor: '#17a2b8',
                        borderWidth: 1
                    },
                    {
                        label: 'Permesso',
                        data: trend.map(t => t.permesso),
                        backgroundColor: '#dc3545',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    x: {
                        stacked: false,
                        grid: { display: false }
                    },
                    y: {
                        stacked: false,
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 10,
                            font: { size: 11 }
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
    }
    
    // Carica grafici al load della pagina
    loadCharts();
});
</script>
@stop
</x-app-layout>