<x-app-layout>
@section('css')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
.absence-badge {
    font-size: 1.2rem;
    padding: 0.5rem 1rem;
}
.project-card {
    transition: all 0.3s;
}
.project-card:hover {
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
.percentage-high {
    color: #dc3545;
    font-weight: bold;
}
.percentage-medium {
    color: #ffc107;
    font-weight: bold;
}
.percentage-low {
    color: #28a745;
    font-weight: bold;
}
.timeline-item {
    border-left: 2px solid #dee2e6;
    padding-left: 1rem;
    margin-bottom: 1rem;
}
</style>
@stop

<div class="container mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="bi bi-graph-up-arrow"></i> Dashboard Assenze Progetti</h2>
            <p class="text-muted">Monitora le assenze del team per ogni progetto</p>
        </div>
    </div>

    <!-- Filtro Date -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('absences.dashboard') }}" class="row g-3 align-items-end">
                        <!-- ✅ NUOVO: Filtro Progetto -->
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="bi bi-briefcase"></i> Progetto
                            </label>
                            <select name="project_id" class="form-select">
                                <option value="">Tutti i miei progetti</option>
                                @foreach($allUserProjects as $proj)
                                    <option value="{{ $proj->id }}" {{ $projectFilter == $proj->id ? 'selected' : '' }}>
                                        {{ $proj->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Data Inizio</label>
                            <input type="date" name="start_date" class="form-control"
                                value="{{ $startDate->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Data Fine</label>
                            <input type="date" name="end_date" class="form-control"
                                value="{{ $endDate->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Aggiorna
                            </button>
                        </div>
                    </form>

                    <!-- ✅ Badge filtro attivo -->
                    @if($projectFilter)
                    <div class="alert alert-info d-flex justify-content-between align-items-center mt-2 py-2 mb-0">
                        <span>
                            <i class="bi bi-funnel-fill"></i>
                            <strong>Filtro attivo:</strong> 
                            {{ $allUserProjects->firstWhere('id', $projectFilter)->name ?? 'Progetto' }}
                        </span>
                        <a href="{{ route('absences.dashboard', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" 
                        class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Rimuovi
                        </a>
                    </div>
                    @endif
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            Periodo selezionato: <strong>{{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }}</strong>
                            ({{ $startDate->diffInDays($endDate) + 1 }} giorni)
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(empty($projectAbsences))
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Nessun progetto con membri trovato nel periodo selezionato.
        </div>
    @else
        {{-- PUNTO 4: Tab switcher Per Progetto / Per Utente --}}
        <ul class="nav nav-tabs mb-4" id="dashboardTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-progetti-btn"
                        type="button" role="tab"
                        onclick="switchTab('tab-progetti')">
                    <i class="bi bi-briefcase"></i> Per Progetto
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-utenti-btn"
                        type="button" role="tab"
                        onclick="switchTab('tab-utenti')">
                    <i class="bi bi-people"></i> Per Utente
                    <span class="badge bg-secondary ms-1">{{ count($userAbsences) }}</span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="dashboardTabsContent">
        {{-- ======================================================= --}}
        {{-- TAB 1: Vista per Progetto (contenuto originale)         --}}
        {{-- ======================================================= --}}
        <div class="tab-pane fade show active" id="tab-progetti" role="tabpanel">

        <!-- Riepilogo Generale -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card bg-light">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h3 class="mb-0">{{ count($projectAbsences) }}</h3>
                                <small class="text-muted">Progetti Attivi</small>
                            </div>
                            <div class="col-md-3">
                                <h3 class="mb-0">{{ array_sum(array_column($projectAbsences, 'total_members')) }}</h3>
                                <small class="text-muted">Totale Membri</small>
                            </div>
                            <div class="col-md-3">
                                <h3 class="mb-0">{{ array_sum(array_column($projectAbsences, 'days_with_absences')) }}</h3>
                                <small class="text-muted">Giorni con Assenze</small>
                            </div>
                            <div class="col-md-3">
                                <h3 class="mb-0 {{ max(array_column($projectAbsences, 'max_absence_percentage')) > 50 ? 'text-danger' : 'text-warning' }}">
                                    {{ max(array_column($projectAbsences, 'max_absence_percentage')) }}%
                                </h3>
                                <small class="text-muted">Max Assenza Rilevata</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grafici e Statistiche Avanzate -->
         @if(Auth::user()->superuser)
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-bar-chart-line"></i> Analisi Grafiche
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Top 5 Progetti con Maggiori Assenze -->
                                <div class="col-md-6 mb-4">
                                    <h6 class="text-center">Top 5 Progetti - % Assenze</h6>
                                    <canvas id="topProjectsChart" style="max-height: 300px;"></canvas>
                                </div>
                                
                                <!-- Timeline Assenze Globali -->
                                <div class="col-md-6 mb-4">
                                    <h6 class="text-center">Timeline Assenze Periodo</h6>
                                    <canvas id="timelineChart" style="max-height: 300px;"></canvas>
                                </div>
                            </div>
                            
                            <div class="row">
                                <!-- Distribuzione Tipi Assenza -->
                                <div class="col-md-6 mb-4">
                                    <h6 class="text-center">Tipi di Assenza</h6>
                                    <canvas id="typeChart" style="max-height: 250px;"></canvas>
                                </div>
                                
                                <!-- Metriche Chiave -->
                                <div class="col-md-6">
                                    <div class="alert alert-light border">
                                        <h6><i class="bi bi-clipboard-data"></i> Metriche Chiave</h6>
                                        <div class="row text-center mt-3">
                                            <div class="col-6 mb-3">
                                                <div class="border rounded p-2">
                                                    <h4 id="metric-total-absences" class="text-danger mb-0">-</h4>
                                                    <small class="text-muted">Totale Assenze</small>
                                                </div>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <div class="border rounded p-2">
                                                    <h4 id="metric-avg-percentage" class="text-warning mb-0">-</h4>
                                                    <small class="text-muted">% Media Assenza</small>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="border rounded p-2">
                                                    <h4 id="metric-peak-day" class="text-info mb-0">-</h4>
                                                    <small class="text-muted">Giorno Picco</small>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="border rounded p-2">
                                                    <h4 id="metric-work-days" class="text-success mb-0">-</h4>
                                                    <small class="text-muted">Giorni Lavorativi</small>
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
        @endif

        <!-- Cards Progetti -->
        @foreach($projectAbsences as $data)
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card project-card">
                        <div class="card-header bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="bi bi-briefcase-fill"></i> {{ $data['project']->name }}
                                </h5>
                                <div>
                                    <span class="badge bg-light text-dark">
                                        <i class="bi bi-people"></i> {{ $data['total_members'] }} {{ Str::plural('membro', $data['total_members']) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Statistiche Progetto -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="text-center p-3 border rounded">
                                        <h2 class="mb-0 
                                            @if($data['avg_absence_percentage'] > 30) percentage-high 
                                            @elseif($data['avg_absence_percentage'] > 15) percentage-medium 
                                            @else percentage-low @endif">
                                            {{ $data['avg_absence_percentage'] }}%
                                        </h2>
                                        <small class="text-muted">Assenza Media</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 border rounded">
                                        <h2 class="mb-0 
                                            @if($data['max_absence_percentage'] > 50) percentage-high 
                                            @elseif($data['max_absence_percentage'] > 30) percentage-medium 
                                            @else percentage-low @endif">
                                            {{ $data['max_absence_percentage'] }}%
                                        </h2>
                                        <small class="text-muted">Assenza Massima</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 border rounded">
                                        <h2 class="mb-0">{{ $data['days_with_absences'] }}</h2>
                                        <small class="text-muted">Giorni con Assenze</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 border rounded">
                                        <h2 class="mb-0">{{ $data['coverage_percentage'] }}%</h2>
                                        <small class="text-muted">Copertura Periodo</small>
                                    </div>
                                </div>
                            </div>

                            @if(empty($data['absences_by_date']))
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle"></i> Nessuna assenza nel periodo selezionato!
                                </div>
                            @else
                                <!-- Dettaglio Assenze per Data -->
                                <div class="accordion" id="accordion{{ $data['project']->id }}">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" 
                                                    type="button" 
                                                    data-bs-toggle="collapse" 
                                                    data-bs-target="#collapse{{ $data['project']->id }}">
                                                <i class="bi bi-calendar-week me-2"></i>
                                                Dettaglio Assenze per Data ({{ count($data['absences_by_date']) }} giorni)
                                            </button>
                                        </h2>
                                        <div id="collapse{{ $data['project']->id }}" 
                                             class="accordion-collapse collapse" 
                                             data-bs-parent="#accordion{{ $data['project']->id }}">
                                            <div class="accordion-body">
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>Data</th>
                                                                <th>Assenti</th>
                                                                <th>% Assenza</th>
                                                                <th>Persone Assenti</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($data['absences_by_date'] as $date => $absence)
                                                                <tr>
                                                                    <td>
                                                                        <strong>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</strong>
                                                                        <small class="text-muted d-block">
                                                                            {{ \Carbon\Carbon::parse($date)->locale('it')->dayName }}
                                                                        </small>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge bg-danger">
                                                                            {{ $absence['count'] }}/{{ $data['total_members'] }}
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <span class="
                                                                            @if($absence['percentage'] > 50) percentage-high 
                                                                            @elseif($absence['percentage'] > 30) percentage-medium 
                                                                            @else percentage-low @endif">
                                                                            {{ $absence['percentage'] }}%
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <small>
                                                                            @foreach($absence['users'] as $user)
                                                                                <span class="badge bg-secondary me-1">
                                                                                    {{ $user['name'] }}
                                                                                    @if($user['status'] == 'ferie')
                                                                                        <i class="bi bi-umbrella-fill"></i>
                                                                                    @else
                                                                                        <i class="bi bi-clock-fill"></i>
                                                                                    @endif
                                                                                </span>
                                                                            @endforeach
                                                                        </small>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="card-footer text-end">
                            <a href="{{ route('projects.show', $data['project']->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> Vedi Team
                            </a>
                            <a href="{{ route('absences.export', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" 
                               class="btn btn-sm btn-outline-success">
                                <i class="bi bi-download"></i> Esporta CSV
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        </div>{{-- fine tab-pane tab-progetti --}}

        {{-- ======================================================= --}}
        {{-- TAB 2: Vista per Utente (PUNTO 4)                       --}}
        {{-- ======================================================= --}}
        <div class="tab-pane fade" id="tab-utenti" role="tabpanel">
            @if(empty($userAbsences))
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Nessun utente trovato nei tuoi progetti per il periodo selezionato.
                </div>
            @else
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-people-fill"></i>
                            Riepilogo per Utente
                            <small class="ms-2 fw-normal opacity-75">{{ $startDate->format('d/m/Y') }} — {{ $endDate->format('d/m/Y') }}</small>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Persona</th>
                                        <th>Progetti / Ruolo</th>
                                        <th class="text-center">🚫 Assenze</th>
                                        <th class="text-center">⏰ Permessi</th>
                                        <th class="text-center">💻 Smart W.</th>
                                        <th class="text-center">🏢 Presente</th>
                                        <th class="text-center">% Assenza</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($userAbsences as $ua)
                                    <tr>
                                        <td>
                                            <strong>{{ $ua['user']->name }}</strong><br>
                                            <small class="text-muted">{{ $ua['user']->email }}</small>
                                        </td>
                                        <td>
                                            @foreach($ua['projects'] as $proj)
                                                <span class="badge bg-light text-dark border me-1 mb-1">
                                                    {{ $proj->name }}
                                                    @if($proj->pivot->role)
                                                        <span class="text-muted">({{ $proj->pivot->role }})</span>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </td>
                                        <td class="text-center">
                                            @if($ua['stats']['ferie'] > 0)
                                                <span class="badge bg-warning text-dark">{{ $ua['stats']['ferie'] }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($ua['stats']['permesso'] > 0)
                                                <span class="badge bg-danger">{{ $ua['stats']['permesso'] }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($ua['stats']['smart_working'] > 0)
                                                <span class="badge bg-info">{{ $ua['stats']['smart_working'] }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($ua['stats']['presente'] > 0)
                                                <span class="badge bg-success">{{ $ua['stats']['presente'] }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $pct = $ua['absence_percentage'];
                                                $cls = $pct > 50 ? 'percentage-high' : ($pct > 25 ? 'percentage-medium' : 'percentage-low');
                                            @endphp
                                            <span class="{{ $cls }}">{{ $pct }}%</span>
                                            <div class="progress mt-1" style="height:4px;">
                                                <div class="progress-bar {{ $pct > 50 ? 'bg-danger' : ($pct > 25 ? 'bg-warning' : 'bg-success') }}"
                                                     style="width:{{ min($pct,100) }}%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>{{-- fine tab-pane tab-utenti --}}

        </div>{{-- fine tab-content --}}
    @endif

    <!-- Legend -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title">Legenda Percentuali</h6>
                    <div class="d-flex gap-4">
                        <span class="percentage-low">
                            <i class="bi bi-check-circle"></i> 0-15%: Bassa
                        </span>
                        <span class="percentage-medium">
                            <i class="bi bi-exclamation-circle"></i> 16-30%: Media
                        </span>
                        <span class="percentage-high">
                            <i class="bi bi-x-circle"></i> >30%: Alta
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('js')
<script>
// Tab switcher vanilla JS (no window.bootstrap richiesto)
window.switchTab = function(targetId) {
    // Aggiorna pulsanti
    document.querySelectorAll('#dashboardTabs .nav-link').forEach(function(btn) {
        btn.classList.remove('active');
    });
    const activeBtn = document.getElementById(targetId.replace('tab-', 'tab-') + '-btn');
    // Mappa id pane → id button
    const btnMap = {
        'tab-progetti': 'tab-progetti-btn',
        'tab-utenti':   'tab-utenti-btn',
    };
    const btnEl = document.getElementById(btnMap[targetId]);
    if (btnEl) btnEl.classList.add('active');

    // Aggiorna pane
    document.querySelectorAll('#dashboardTabsContent .tab-pane').forEach(function(pane) {
        pane.classList.remove('show', 'active');
    });
    const targetPane = document.getElementById(targetId);
    if (targetPane) {
        targetPane.classList.add('show', 'active');
    }
};

document.addEventListener('DOMContentLoaded', function () {
    loadDashboardCharts();
    
    function loadDashboardCharts() {
        const startDate = '{{ $startDate->format("Y-m-d") }}';
        const endDate = '{{ $endDate->format("Y-m-d") }}';
        
        fetch(`{{ route('absences.chart-data') }}?start_date=${startDate}&end_date=${endDate}`)
            .then(response => response.json())
            .then(data => {
                updateMetrics(data);
                createTopProjectsChart(data.top_projects);
                createTimelineChart(data.timeline);
                createTypeChart(data.type_stats);
            })
            .catch(error => {
                console.error('Errore caricamento grafici:', error);
            });
    }
    
    function updateMetrics(data) {
        // Totale assenze
        const totalAbsences = data.timeline.reduce((sum, item) => sum + item.absent, 0);
        document.getElementById('metric-total-absences').textContent = totalAbsences;
        
        // Percentuale media
        const avgPercentage = data.timeline.length > 0
            ? (data.timeline.reduce((sum, item) => sum + item.percentage, 0) / data.timeline.length).toFixed(1)
            : 0;
        document.getElementById('metric-avg-percentage').textContent = avgPercentage + '%';
        
        // Giorno con picco
        const peakDay = data.timeline.reduce((max, item) => 
            item.absent > max.absent ? item : max, 
            { absent: 0, date: '-' }
        );
        document.getElementById('metric-peak-day').textContent = peakDay.date || '-';
        
        // Giorni lavorativi
        document.getElementById('metric-work-days').textContent = data.period.days;
    }
    
    function createTopProjectsChart(projects) {
        const ctx = document.getElementById('topProjectsChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: projects.map(p => p.name),
                datasets: [{
                    label: '% Assenze',
                    data: projects.map(p => p.percentage),
                    backgroundColor: projects.map(p => {
                        if (p.percentage > 30) return '#dc3545';
                        if (p.percentage > 15) return '#ffc107';
                        return '#28a745';
                    }),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const project = projects[context.dataIndex];
                                return [
                                    `Assenze: ${context.parsed.x}%`,
                                    `Membri: ${project.total_members}`,
                                    `Giorni: ${project.absent_days}`
                                ];
                            }
                        }
                    }
                }
            }
        });
    }
    
    function createTimelineChart(timeline) {
        const ctx = document.getElementById('timelineChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: timeline.map(t => t.date),
                datasets: [{
                    label: '% Assenze',
                    data: timeline.map(t => t.percentage),
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#dc3545',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const item = timeline[context.dataIndex];
                                return [
                                    `Assenti: ${item.absent}`,
                                    `Percentuale: ${item.percentage}%`
                                ];
                            }
                        }
                    }
                }
            }
        });
    }
    
    function createTypeChart(typeStats) {
        const ctx = document.getElementById('typeChart').getContext('2d');
        
        const labels = {
            'ferie': 'Ferie',
            'smart_working': 'Smart Working',
            'permesso': 'Permesso',
            'presente': 'Presente'
        };
        
        const colors = {
            'ferie': '#ffc107',
            'smart_working': '#17a2b8',
            'permesso': '#dc3545',
            'presente': '#28a745'
        };
        
        const data = Object.keys(typeStats).map(key => ({
            label: labels[key] || key,
            value: typeStats[key],
            color: colors[key] || '#6c757d'
        }));
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(d => d.label),
                datasets: [{
                    data: data.map(d => d.value),
                    backgroundColor: data.map(d => d.color),
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
                            padding: 15,
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@stop

</x-app-layout>