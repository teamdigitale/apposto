<x-app-layout>
<div class="container mt-4">
    <h2><i class="bi bi-inbox"></i> Richieste Progetti</h2>
    <p class="text-muted">Gestisci le richieste di join/leave degli utenti</p>
    
    @if($requests->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-check-circle"></i> Nessuna richiesta in attesa!
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Utente</th>
                        <th>Progetto</th>
                        <th>Tipo</th>
                        <th>Ruolo Richiesto</th>
                        <th>Messaggio</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $req)
                    <tr>
                        <td>{{ $req->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <strong>{{ $req->user->name }}</strong><br>
                            <small class="text-muted">{{ $req->user->email }}</small>
                        </td>
                        <td>{{ $req->project->name }}</td>
                        <td>
                            @if($req->type === 'join')
                                <span class="badge bg-success">
                                    <i class="bi bi-plus-circle"></i> Unione
                                </span>
                            @else
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-dash-circle"></i> Uscita
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($req->type === 'join' && $req->role)
                                <span class="badge bg-primary">
                                    <i class="bi bi-person-badge"></i>
                                    {{ ucfirst($req->role) }}
                                </span>
                            @elseif($req->type === 'leave')
                                <span class="text-muted"><i>—</i></span>
                            @else
                                <span class="badge bg-light text-dark border">non specificato</span>
                            @endif
                        </td>
                        <td style="max-width:220px;">
                            @if($req->message)
                                <small class="text-wrap">{{ $req->message }}</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('projectRequests.approve', $req->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success" 
                                        onclick="return confirm('Approvare questa richiesta?')">
                                    <i class="bi bi-check-circle"></i> Approva
                                </button>
                            </form>
                            
                            <button class="btn btn-sm btn-danger" 
                                    onclick="openRejectModal({{ $req->id }})">
                                <i class="bi bi-x-circle"></i> Rifiuta
                            </button>
                            
                            <!-- Modal rifiuto -->
                            <div class="modal fade" id="rejectModal{{ $req->id }}">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('projectRequests.reject', $req->id) }}">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title">Rifiuta Richiesta</h5>
                                                <button type="button" class="btn-close" onclick="this.closest('.modal').style.display='none'; this.closest('.modal').classList.remove('show'); document.body.classList.remove('modal-open');"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Rifiutare la richiesta di <strong>{{ $req->user->name }}</strong>?</p>
                                                <div class="mb-3">
                                                    <label class="form-label">Motivo (opzionale)</label>
                                                    <textarea name="admin_notes" class="form-control" rows="2"></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').style.display='none'; this.closest('.modal').classList.remove('show'); document.body.classList.remove('modal-open');">Annulla</button>
                                                <button type="submit" class="btn btn-danger">Rifiuta</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<script>
function openRejectModal(id) {
    const el = document.getElementById('rejectModal' + id);
    el.style.display = 'block';
    el.classList.add('show');
    el.removeAttribute('aria-hidden');
    document.body.classList.add('modal-open');
    let bd = document.getElementById('reject-backdrop-' + id);
    if (!bd) {
        bd = document.createElement('div');
        bd.id = 'reject-backdrop-' + id;
        bd.className = 'modal-backdrop fade show';
        document.body.appendChild(bd);
    }
    bd.style.display = 'block';
    // Chiudi cliccando fuori
    el.addEventListener('click', function(e) {
        if (e.target === this) closeRejectModal(id);
    }, { once: true });
}
function closeRejectModal(id) {
    const el = document.getElementById('rejectModal' + id);
    if (el) {
        el.style.display = 'none';
        el.classList.remove('show');
        el.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    }
    const bd = document.getElementById('reject-backdrop-' + id);
    if (bd) bd.style.display = 'none';
}
// Pulsanti Annulla nei modal rifiuto usano closeRejectModal
document.querySelectorAll('[data-close-reject]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        closeRejectModal(this.dataset.closeReject);
    });
});
</script>

</x-app-layout>