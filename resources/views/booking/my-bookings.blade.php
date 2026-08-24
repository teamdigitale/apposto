<x-app-layout>
<div class="container">
    <h1>Le mie prenotazioni</h1>
    <br />

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($bookings->isEmpty())
        <p>Non hai prenotazioni confermate.</p>
        <a href="{{ route('booking.step.one') }}" class="btn btn-primary">Prenota una postazione</a>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>Scrivania</th>
                    <th>Piano</th>
                    <th>Sede</th>
                    <th>Data inizio</th>
                    <th>Data fine</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bookings as $booking)
                    <tr>
                        <td>{{ $booking->desk->identifier ?? '—' }}</td>
                        <td>{{ $booking->desk->plan->description ?? '—' }}</td>
                        <td>{{ $booking->desk->plan->workplace->name ?? '—' }}</td>
                        <td>{{ Carbon\Carbon::parse($booking->from_date)->format('d-m-Y H:i') }}</td>
                        <td>{{ Carbon\Carbon::parse($booking->to_date)->format('d-m-Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <a href="{{ route('bookings.current') }}" class="btn btn-outline-primary">Gestisci prenotazioni</a>
    @endif
</div>
</x-app-layout>
