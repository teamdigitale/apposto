<x-app-layout>
<div class="container mt-4">
    <!-- Header Progetto -->
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">I Miei Progetti</a></li>
                    <li class="breadcrumb-item active">{{ $project->name }}</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2>{{ $project->name }}</h2>
                    @if($project->description)
                        <p class="text-muted">{{ $project->description }}</p>
                    @endif
                </div>
                
                <div>
                    @if($isMember)
                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Fai parte del team</span>
                    @else
                        <span class="badge bg-secondary"><i class="bi bi-info-circle"></i> Non sei membro</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Info Progetto -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center p-3 border-end">
                                <i class="bi bi-people-fill" style="font-size: 2rem; color: #0d6efd;"></i>
                                <h3 class="mb-0 mt-2">{{ $project->users->count() }}</h3>
                                <small class="text-muted">{{ Str::plural('Membro', $project->users->count()) }}</small>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="text-center p-3 border-end">
                                <i class="bi bi-calendar-check" style="font-size: 2rem; color: #28a745;"></i>
                                <h3 class="mb-0 mt-2">
                                    @if($project->start_date)
                                        {{ $project->start_date->format('d/m/Y') }}
                                    @else
                                        —
                                    @endif
                                </h3>
                                <small class="text-muted">Data Inizio</small>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="text-center p-3 border-end">
                                <i class="bi bi-calendar-x" style="font-size: 2rem; color: #dc3545;"></i>
                                <h3 class="mb-0 mt-2">
                                    @if($project->end_date)
                                        {{ $project->end_date->format('d/m/Y') }}
                                    @else
                                        —
                                    @endif
                                </h3>
                                <small class="text-muted">Data Fine</small>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <i class="bi bi-toggle-{{ $project->active ? 'on' : 'off' }}" 
                                   style="font-size: 2rem; color: {{ $project->active ? '#28a745' : '#6c757d' }};"></i>
                                <h3 class="mb-0 mt-2">{{ $project->active ? 'Attivo' : 'Inattivo' }}</h3>
                                <small class="text-muted">Stato</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Members -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-people"></i> Team Members</h5>
                </div>
                <div class="card-body">
                    @if($project->users->isEmpty())
                        <div class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="text-muted mt-3">Nessun membro nel team.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Email</th>
                                        <th>Ruolo</th>
                                        <th>Dal</th>
                                        <th>Ferie Disponibili</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($project->users as $user)
                                        <tr class="{{ $user->id == Auth::id() ? 'table-primary' : '' }}">
                                            <td>
                                                <strong>{{ $user->name }}</strong>
                                                @if($user->id == Auth::id())
                                                    <span class="badge bg-info ms-2">Tu</span>
                                                @endif
                                            </td>
                                            <td>{{ $user->email }}</td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <i class="bi bi-person-badge"></i>
                                                    {{ ucfirst($user->pivot->role) }}
                                                </span>
                                            </td>
                                            <td>{{ $user->pivot->created_at->format('d/m/Y') }}</td>
                                            <td>
                                                <span class="badge bg-{{ $user->remaining_leave_days > 10 ? 'success' : ($user->remaining_leave_days > 5 ? 'warning' : 'danger') }}">
                                                    {{ $user->remaining_leave_days }} giorni
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Statistiche Team -->
                        <div class="mt-4">
                            <h6 class="mb-3">Statistiche Team</h6>
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <div class="p-3 border rounded">
                                        <h4 class="mb-0">{{ $project->users->count() }}</h4>
                                        <small class="text-muted">Totale Membri</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="p-3 border rounded">
                                        <h4 class="mb-0">{{ $project->users->sum('ferie_totali') }}</h4>
                                        <small class="text-muted">Ferie Totali Team</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="p-3 border rounded">
                                        <h4 class="mb-0">{{ $project->users->sum('ferie_usate') }}</h4>
                                        <small class="text-muted">Ferie Usate Team</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="p-3 border rounded">
                                        <h4 class="mb-0">{{ $project->users->sum('remaining_leave_days') }}</h4>
                                        <small class="text-muted">Ferie Disponibili Team</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Azioni -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="d-flex gap-2">
                <a href="{{ route('projects.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Torna ai Progetti
                </a>
                
                <a href="{{ route('absences.dashboard') }}?project_id={{ $project->id }}" class="btn btn-info">
                    <i class="bi bi-graph-up"></i> Vedi Assenze Progetto
                </a>
                
                @if($isMember)
                    <a href="{{ route('presences.index') }}" class="btn btn-primary">
                        <i class="bi bi-calendar-check"></i> Gestisci Presenze
                    </a>
                @else
                    <form method="POST" action="{{ route('projects.join', $project->id) }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="role" value="member">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-plus-circle"></i> Unisciti al Progetto
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
</x-app-layout>