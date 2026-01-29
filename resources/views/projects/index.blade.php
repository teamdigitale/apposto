<x-app-layout>
<div class="container mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>I Miei Progetti</h2>
            <p class="text-muted">Gestisci i progetti a cui lavori</p>
        </div>
    </div>

    <!-- Messaggi -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <!-- Progetti a cui sono assegnato -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-briefcase-fill"></i> I Miei Progetti 
                        <span class="badge bg-light text-primary">{{ $myProjects->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if($myProjects->isEmpty())
                        <div class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="text-muted mt-3">Non fai ancora parte di nessun progetto.</p>
                            <p class="text-muted">Unisciti a un progetto dalla sezione a destra!</p>
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($myProjects as $project)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">
                                                <a href="{{ route('projects.show', $project->id) }}" class="text-decoration-none">
                                                    {{ $project->name }}
                                                </a>
                                            </h6>
                                            @if($project->description)
                                                <p class="mb-2 text-muted small">{{ Str::limit($project->description, 80) }}</p>
                                            @endif
                                            
                                            <div class="d-flex align-items-center gap-3 small text-muted">
                                                <span>
                                                    <i class="bi bi-people"></i> 
                                                    {{ $project->users_count }} {{ Str::plural('membro', $project->users_count) }}
                                                </span>
                                                <span>
                                                    <i class="bi bi-person-badge"></i> 
                                                    {{ ucfirst($project->pivot->role) }}
                                                </span>
                                                <span>
                                                    <i class="bi bi-calendar-check"></i> 
                                                    Dal {{ $project->pivot->created_at->format('d/m/Y') }}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="ms-3">
                                            <a href="{{ route('projects.show', $project->id) }}" 
                                               class="btn btn-sm btn-outline-primary mb-2">
                                                <i class="bi bi-eye"></i> Dettagli
                                            </a>
                                            
                                            <!-- Modal per aggiornare ruolo -->
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-secondary mb-2" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#updateRoleModal{{ $project->id }}">
                                                <i class="bi bi-pencil"></i> Ruolo
                                            </button>
                                            
                                            <!-- Modal per lasciare progetto -->
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#leaveModal{{ $project->id }}">
                                                <i class="bi bi-box-arrow-right"></i> Lascia
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Aggiorna Ruolo -->
                                <div class="modal fade" id="updateRoleModal{{ $project->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('projects.update-role', $project->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Aggiorna Ruolo</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Progetto: <strong>{{ $project->name }}</strong></p>
                                                    <div class="mb-3">
                                                        <label for="role{{ $project->id }}" class="form-label">Ruolo</label>
                                                        <select name="role" id="role{{ $project->id }}" class="form-select" required>
                                                            <option value="developer" {{ $project->pivot->role == 'developer' ? 'selected' : '' }}>Developer</option>
                                                            <option value="designer" {{ $project->pivot->role == 'designer' ? 'selected' : '' }}>Designer</option>
                                                            <option value="tester" {{ $project->pivot->role == 'tester' ? 'selected' : '' }}>Tester</option>
                                                            <option value="manager" {{ $project->pivot->role == 'manager' ? 'selected' : '' }}>Manager</option>
                                                            <option value="member" {{ $project->pivot->role == 'member' ? 'selected' : '' }}>Member</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                                                    <button type="submit" class="btn btn-primary">Aggiorna</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Conferma Lascia -->
                                <div class="modal fade" id="leaveModal{{ $project->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Conferma Uscita</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Sei sicuro di voler lasciare il progetto <strong>{{ $project->name }}</strong>?</p>
                                                <p class="text-muted small">Non potrai più richiedere ferie per questo progetto.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                                                <form method="POST" action="{{ route('projects.leave', $project->id) }}" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger">Lascia Progetto</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Progetti disponibili -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-plus-circle-fill"></i> Progetti Disponibili
                        <span class="badge bg-light text-success">{{ $availableProjects->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if($availableProjects->isEmpty())
                        <div class="text-center py-4">
                            <i class="bi bi-check-circle" style="font-size: 3rem; color: #28a745;"></i>
                            <p class="text-muted mt-3">Fai già parte di tutti i progetti disponibili!</p>
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($availableProjects as $project)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ $project->name }}</h6>
                                            @if($project->description)
                                                <p class="mb-2 text-muted small">{{ Str::limit($project->description, 100) }}</p>
                                            @endif
                                            
                                            <div class="small text-muted">
                                                <i class="bi bi-people"></i> 
                                                {{ $project->users_count }} {{ Str::plural('membro', $project->users_count) }}
                                            </div>
                                        </div>
                                        
                                        <div class="ms-3">
                                            <button type="button" 
                                                    class="btn btn-sm btn-success" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#joinModal{{ $project->id }}">
                                                <i class="bi bi-plus-circle"></i> Unisciti
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Unisciti -->
                                <div class="modal fade" id="joinModal{{ $project->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('projects.join', $project->id) }}">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Unisciti al Progetto</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Progetto: <strong>{{ $project->name }}</strong></p>
                                                    @if($project->description)
                                                        <p class="text-muted small">{{ $project->description }}</p>
                                                    @endif
                                                    
                                                    <div class="mb-3">
                                                        <label for="role_join{{ $project->id }}" class="form-label">Seleziona il tuo ruolo</label>
                                                        <select name="role" id="role_join{{ $project->id }}" class="form-select">
                                                            <option value="member">Member (Generico)</option>
                                                            <option value="developer">Developer</option>
                                                            <option value="designer">Designer</option>
                                                            <option value="tester">Tester</option>
                                                            <option value="manager">Manager</option>
                                                        </select>
                                                        <small class="text-muted">Potrai modificarlo successivamente</small>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                                                    <button type="submit" class="btn btn-success">Unisciti</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Link rapidi -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card bg-light">
                <div class="card-body">
                    <h5 class="card-title">Link Utili</h5>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('presences.index') }}" class="btn btn-outline-primary">
                            <i class="bi bi-calendar-event"></i> Le Mie Presenze
                        </a>
                        <a href="{{ route('absences.dashboard') }}" class="btn btn-outline-info">
                            <i class="bi bi-graph-up"></i> Dashboard Assenze
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>