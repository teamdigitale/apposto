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
                                                <h6 class="mb-1 text-danger">{{ $project->name }}</h6>
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
                                    <h6 class="mb-1 text-danger">{{ $project->name }}</h6>
                                    <small class="text-muted">
                                        {{ $project->description ?? 'Nessuna descrizione' }}
                                    </small>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm btn-success"
                                            onclick="openJoinModal({{ $project->id }}, '{{ addslashes($project->name) }}')">
                                        <i class="bi bi-send"></i> Richiedi di Unirti
                                    </button>
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


{{-- Modal richiesta join con ruolo e messaggio --}}
<div class="modal fade" id="modalJoinRequest" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="joinRequestForm" action="">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-send"></i>
                        Richiesta di unione a <span id="joinModalProjectName"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeJoinModal()"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-person-badge"></i> Con quale ruolo vuoi unirti?
                        </label>
                        <select name="role" class="form-select" required>
                            <option value="">— Seleziona un ruolo —</option>
                            <option value="developer">👨‍💻 Developer</option>
                            <option value="designer">🎨 Designer</option>
                            <option value="tester">🧪 Tester</option>
                            <option value="product owner">📋 Product Owner</option>
                            <option value="scrum master">🔄 Scrum Master</option>
                            <option value="member">👤 Member (generico)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-chat-text"></i> Messaggio per l'amministratore
                            <small class="text-muted fw-normal">(opzionale)</small>
                        </label>
                        <textarea name="message" class="form-control" rows="3"
                                  placeholder="Es: Ho esperienza con Vue.js e sono disponibile da subito..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeJoinModal()">Annulla</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-send"></i> Invia Richiesta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Route base per requestJoin — Laravel genera l'URL con il placeholder {project}
const joinRouteBase = '{{ url('/projects') }}';

function openJoinModal(projectId, projectName) {
    document.getElementById('joinModalProjectName').textContent = projectName;
    document.getElementById('joinRequestForm').action = joinRouteBase + '/' + projectId + '/request-join';
    // Reset campi
    document.getElementById('joinRequestForm').reset();
    // Apri modal vanilla (stesso approccio degli altri modal del progetto)
    const el = document.getElementById('modalJoinRequest');
    el.style.display = 'block';
    el.classList.add('show');
    el.removeAttribute('aria-hidden');
    document.body.classList.add('modal-open');
    let bd = document.getElementById('join-modal-backdrop');
    if (!bd) {
        bd = document.createElement('div');
        bd.id = 'join-modal-backdrop';
        bd.className = 'modal-backdrop fade show';
        document.body.appendChild(bd);
    }
    bd.style.display = 'block';
}

function closeJoinModal() {
    const el = document.getElementById('modalJoinRequest');
    el.style.display = 'none';
    el.classList.remove('show');
    el.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
    const bd = document.getElementById('join-modal-backdrop');
    if (bd) bd.style.display = 'none';
}

// Chiudi cliccando fuori
document.getElementById('modalJoinRequest').addEventListener('click', function(e) {
    if (e.target === this) closeJoinModal();
});
</script>

</x-app-layout>