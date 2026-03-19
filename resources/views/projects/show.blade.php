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
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
<?php print_r($project);?>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-folder-symlink"></i> Risorse Progetto
            </h5>
        </div>
        <div class="card-body">
            
            @if($project->slack_channel || $project->drive_folder || $project->documentation_url || $project->resources_notes)
                <div class="row mb-3">
                    @if($project->slack_channel)
                    <div class="col-md-6 mb-2">
                        <strong><i class="bi bi-slack text-primary"></i> Canale Teams:</strong><br>
                        <a href="{{ $project->slack_channel }}" 
                        target="_blank" class="text-decoration-none">
                            #{{ $project->slack_channel }}
                            <i class="bi bi-box-arrow-up-right small"></i>
                        </a>
                    </div>
                    @endif
                    
                    @if($project->drive_folder)
                    <div class="col-md-6 mb-2">
                        <strong><i class="bi bi-google text-warning"></i> Cartella Progetto:</strong><br>
                        <a href="{{ $project->drive_folder }}" target="_blank" class="text-decoration-none">
                            Apri cartella
                            <i class="bi bi-box-arrow-up-right small"></i>
                        </a>
                    </div>
                    @endif
                    
                    @if($project->documentation_url)
                    <div class="col-md-12 mb-2">
                        <strong><i class="bi bi-file-earmark-text text-info"></i> Documentazione:</strong><br>
                        <a href="{{ $project->documentation_url }}" target="_blank" class="text-decoration-none">
                            {{ $project->documentation_url }}
                            <i class="bi bi-box-arrow-up-right small"></i>
                        </a>
                    </div>
                    @endif
                    
                    @if($project->resources_notes)
                    <div class="col-md-12">
                        <strong><i class="bi bi-sticky"></i> Note:</strong><br>
                        <p class="mb-0 text-muted">{{ $project->resources_notes }}</p>
                    </div>
                    @endif
                </div>                
            @endif
            
            <!-- Form di modifica (collapsabile) -->
            <div class="collapse mt-3" id="resourcesForm">
                <hr>
                <form method="POST" action="{{ route('projects.updateResources', $project->id) }}">
                    @csrf
                    @method('PATCH')
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-slack"></i> Canale Slack
                            </label>
                            <input type="text" name="slack_channel" class="form-control" 
                                value="{{ old('slack_channel', $project->slack_channel) }}"
                                placeholder="es: team-progetto">
                            <small class="text-muted">Solo il nome del canale (senza #)</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="bi bi-google"></i> Cartella Google Drive
                            </label>
                            <input type="url" name="drive_folder" class="form-control" 
                                value="{{ old('drive_folder', $project->drive_folder) }}"
                                placeholder="https://drive.google.com/drive/folders/...">
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                <i class="bi bi-file-earmark-text"></i> Link Documentazione
                            </label>
                            <input type="url" name="documentation_url" class="form-control" 
                                value="{{ old('documentation_url', $project->documentation_url) }}"
                                placeholder="https://...">
                            <small class="text-muted">Notion, Confluence, Wiki, ecc.</small>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                <i class="bi bi-sticky"></i> Note / Altre Risorse
                            </label>
                            <textarea name="resources_notes" class="form-control" rows="3" 
                                    placeholder="Altre informazioni utili, link, credenziali, ecc.">{{ old('resources_notes', $project->resources_notes) }}</textarea>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Salva Risorse
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#resourcesForm">
                            Annulla
                        </button>
                    </div>
                </form>
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