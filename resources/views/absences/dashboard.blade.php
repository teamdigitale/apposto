<x-app-layout>
@section('css')
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
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Data Inizio</label>
                            <input type="date" 
                                   name="start_date" 
                                   id="start_date" 
                                   class="form-control" 
                                   value="{{ $startDate->format('Y-m-d') }}"
                                   required>
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">Data Fine</label>
                            <input type="date" 
                                   name="end_date" 
                                   id="end_date" 
                                   class="form-control" 
                                   value="{{ $endDate->format('Y-m-d') }}"
                                   required>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Aggiorna
                            </button>
                        </div>
                    </form>
                    
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
                                        <h2 class="mb-0">{{ round(($data['days_with_absences'] / $data['total_days']) * 100, 1) }}%</h2>
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
                                                                            @foreach($absence['users'] as $userName)
                                                                                <span class="badge bg-secondary me-1">{{ $userName }}</span>
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
</x-app-layout>