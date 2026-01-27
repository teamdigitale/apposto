<x-app-layout>
    <div class="container">
        <h2>Foglio presenze – {{ \Carbon\Carbon::parse($month)->translatedFormat('F Y') }}</h2>

        <form method="GET" action="{{ route('presences.overview') }}" class="mb-4">
            <label for="month" class="form-label">Seleziona mese:</label>
            <input type="month" name="month" id="month" value="{{ $month }}" class="form-control" style="max-width: 200px; display: inline-block;">
            <button type="submit" class="btn btn-primary ms-2">Vai</button>
        </form>

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
                        <td>{{ $row['user']->name }}</td>
                        @foreach ($row['days'] as $status)
                            <td class="text-center">
                                @switch($status)
                                    @case('presente') <span class="badge bg-success">P</span> @break
                                    @case('ferie') <span class="badge bg-warning text-dark">F</span> @break
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
