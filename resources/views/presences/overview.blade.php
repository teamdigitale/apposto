<x-app-layout>
    <div class="container">
        <h2>Foglio presenze – {{ \Carbon\Carbon::parse($month)->translatedFormat('F Y') }}</h2>

        <div class="d-flex flex-wrap align-items-end gap-3 mb-4">
            {{-- Selettore mese --}}
            <form method="GET" action="{{ route('presences.overview') }}">
                @if($validFilter)
                    <input type="hidden" name="project_filter" value="{{ $validFilter }}">
                @endif
                <label for="month" class="form-label">Seleziona mese:</label>
                <div class="d-flex gap-2">
                    <input type="month" name="month" id="month" value="{{ $month }}"
                           class="form-control" style="max-width: 200px;">
                    <button type="submit" class="btn btn-primary">Vai</button>
                </div>
            </form>

            {{-- Filtro per progetto --}}
            <form method="GET" action="{{ route('presences.overview') }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <label class="form-label">Filtra per progetto:</label>
                <div class="d-flex gap-2">
                    <select name="project_filter" class="form-select" onchange="this.form.submit()">
                        <option value="">Tutti i progetti</option>
                        @foreach($allProjects as $proj)
                            <option value="{{ $proj->id }}" {{ $validFilter == $proj->id ? 'selected' : '' }}>
                                {{ $proj->name }}
                            </option>
                        @endforeach
                    </select>
                    @if($validFilter)
                        <a href="{{ route('presences.overview', ['month' => $month]) }}"
                           class="btn btn-outline-secondary text-nowrap">
                            Rimuovi
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <table class="table table-bordered table-sm align-middle text-center">
            <thead>
                <tr>
                    <th>Utente</th>
                    @foreach ($days as $day)
                        @holiday($day)
                            <th class="table-danger text-center" style="white-space: nowrap;">
                                {{ \Carbon\Carbon::parse($day)->format('d') }}<br>
                                <small>{{ \Carbon\Carbon::parse($day)->translatedFormat('D') }}</small>
                            </th>
                        @else
                            <th class="text-center" style="white-space: nowrap;">
                                {{ \Carbon\Carbon::parse($day)->format('d') }}<br>
                                <small>{{ \Carbon\Carbon::parse($day)->translatedFormat('D') }}</small>
                            </th>
                        @endholiday
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($overview as $row)
                    <tr>
                        <td class="text-start">{{ $row['user']->name }}</td>
                        @foreach ($row['days'] as $status)
                            <td class="text-center">
                                @switch($status)
                                    @case('presente') <span class="badge bg-success">P</span> @break
                                    @case('ferie') <span class="badge bg-warning text-dark">A</span> @break
                                    @case('permesso') <span class="badge bg-danger">Pe</span> @break
                                    @case('smart_working') <span class="badge bg-info text-dark">SW</span> @break
                                    @default -
                                @endswitch
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>