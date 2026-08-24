<x-app-layout>
    <div class="container">
        <h2>Foglio presenze – {{ \Carbon\Carbon::parse($month)->translatedFormat('F Y') }}</h2>

        {{-- Form unico con tabella per allineamento perfetto label/input --}}
        <form method="GET" action="{{ route('presences.overview') }}"
              class="mb-4 p-3 bg-light rounded border d-inline-block">
            <table class="mb-0" style="border-collapse: separate; border-spacing: 1rem 0;">
                <thead>
                    <tr>
                        <th class="fw-semibold pb-1 text-nowrap" style="padding-right: 60px;">
                            <i class="bi bi-calendar3"></i> Mese
                        </th>
                        <th class="fw-semibold pb-1 text-nowrap">
                            <i class="bi bi-briefcase"></i> Progetto
                        </th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="pe-5">
                            <input type="month" name="month" id="month" value="{{ $month }}"
                                   class="form-control" style="min-width: 180px;">
                        </td>
                        <td class="pe-4">
                            <select name="project_filter" id="project_filter"
                                    class="form-select" style="min-width: 220px;">
                                <option value="">Tutti i progetti</option>
                                @foreach($allProjects as $proj)
                                    <option value="{{ $proj->id }}"
                                            {{ $validFilter == $proj->id ? 'selected' : '' }}>
                                        {{ $proj->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td class="text-nowrap">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel"></i> Applica
                            </button>
                            @if($validFilter)
                                <a href="{{ route('presences.overview', ['month' => $month]) }}"
                                   class="btn btn-outline-secondary ms-2">
                                    <i class="bi bi-x-circle"></i> Rimuovi
                                </a>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>

        <table class="table table-bordered table-sm align-middle text-center">
            <thead>
                <tr>
                    <th class="text-start">Utente</th>
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
                                    @case('presente')      <span class="badge bg-success">P</span>           @break
                                    @case('ferie')         <span class="badge bg-warning text-dark">A</span> @break
                                    @case('smart_working') <span class="badge bg-info text-dark">SW</span>   @break
                                    @default               <span class="text-muted">—</span>
                                @endswitch
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Legenda --}}
        <div class="d-flex align-items-center gap-3 mt-3 flex-wrap small text-muted">
            <strong>Legenda:</strong>
            <span><span class="badge bg-success ml-2">P</span> Presente</span>
            <span><span class="badge bg-info text-dark ml-2">SW</span> Smart Working</span>
            <span><span class="badge bg-warning text-dark ml-2">A</span> Assenza</span>
            <span><span class="text-muted ml-2">—</span> Non compilato</span>
        </div>

    </div>
</x-app-layout>