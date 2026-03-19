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
                                    <i class="bi bi-plus-circle"></i> Richiesta Unione
                                </span>
                            @else
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-dash-circle"></i> Richiesta Uscita
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($req->message)
                                <small>{{ $req->message }}</small>
                            @else
                                <span class="text-muted">-</span>
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
                                    data-bs-toggle="modal" 
                                    data-bs-target="#rejectModal{{ $req->id }}">
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
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Rifiutare la richiesta di <strong>{{ $req->user->name }}</strong>?</p>
                                                <div class="mb-3">
                                                    <label class="form-label">Motivo (opzionale)</label>
                                                    <textarea name="admin_notes" class="form-control" rows="2"></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
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
</x-app-layout>