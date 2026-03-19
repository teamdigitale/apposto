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
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
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
                    <!-- I Miei Progetti -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">I Miei Progetti <span class="badge bg-light text-dark">{{ $myProjects->count() }}</span></h5>
                        </div>
                        <div class="card-body">
                            @if($myProjects->isEmpty())
                                <p class="text-muted">Non fai ancora parte di nessun progetto.</p>
                            @else
                                <div class="list-group">
                                    @foreach($myProjects as $project)
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">{{ $project->name }}</h6>
                                                <small class="text-muted">
                                                    {{ $project->users_count ?? $project->users->count() }} membri · 
                                                    Member dal {{ $project->pivot->created_at?->format('d/m/Y') ?? 'N/A' }}
                                                </small>
                                            </div>
                                            <div class="btn-group">
                                                <a href="{{ route('projects.show', $project->id) }}" 
                                                class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> Dettagli
                                                </a>
                                                
                                                <form method="POST" 
                                                    action="{{ route('projects.requestLeave', $project->id) }}" 
                                                    class="d-inline"
                                                    onsubmit="return confirm('Vuoi richiedere di lasciare {{ $project->name }}? L\'admin dovrà approvare.')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-box-arrow-right"></i> Richiedi di Lasciare
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progetti disponibili -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Progetti Disponibili <span class="badge bg-light text-dark">{{ $availableProjects->count() }}</span></h5>
            </div>
            <div class="card-body">
                @if($availableProjects->isEmpty())
                    <p class="text-muted">Non ci sono progetti disponibili al momento.</p>
                @else
                    <div class="list-group">
                        @foreach($availableProjects as $project)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">{{ $project->name }}</h6>
                                    <small class="text-muted">
                                        {{ $project->description ?? 'Nessuna descrizione' }}
                                    </small>
                                </div>
                                <div>
                                    <form method="POST" 
                                        action="{{ route('projects.requestJoin', $project->id) }}" 
                                        class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="bi bi-send"></i> Richiedi di Unirti
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
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