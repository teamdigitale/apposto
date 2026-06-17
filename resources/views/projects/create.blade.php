<x-app-layout>
<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="bi bi-plus-circle"></i> Nuovo Progetto</h2>
            <p class="text-muted">Crea un nuovo progetto e definiscine i dettagli</p>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('projects.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nome Progetto <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name') }}" required maxlength="255">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Descrizione</label>
                    <textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Data Inizio</label>
                        <input type="date" name="start_date" class="form-control"
                               value="{{ old('start_date') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Data Fine</label>
                        <input type="date" name="end_date" class="form-control"
                               value="{{ old('end_date') }}">
                    </div>
                </div>

                <hr class="my-4">
                <h6 class="text-muted mb-3"><i class="bi bi-link-45deg"></i> Risorse del Progetto</h6>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Link Canale Teams</label>
                    <input type="url" name="slack_channel" class="form-control"
                           placeholder="https://teams.microsoft.com/..."
                           value="{{ old('slack_channel') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Link Cartella Condivisa</label>
                    <input type="url" name="drive_folder" class="form-control"
                           placeholder="https://..."
                           value="{{ old('drive_folder') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Link Documentazione</label>
                    <input type="url" name="documentation_url" class="form-control"
                           placeholder="https://..."
                           value="{{ old('documentation_url') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Ulteriori Note sulle Risorse</label>
                    <textarea name="resources_notes" class="form-control" rows="3">{{ old('resources_notes') }}</textarea>
                </div>

                <hr class="my-4">

                <div class="form-check form-switch mb-4">
                    <input type="checkbox" name="active" class="form-check-input" id="activeSwitch"
                           value="1" {{ old('active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="activeSwitch">Progetto attivo</label>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Crea Progetto
                    </button>
                    <a href="{{ route('projects.index') }}" class="btn btn-secondary">Annulla</a>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>