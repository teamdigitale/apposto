<x-app-layout>
<div class="container">
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <h1>Riepilogo prenotazione</h1>
    <br />

    <p><strong>Data iniziale:</strong> {{ Carbon\Carbon::parse(session('booking.start_date'))->format('d-m-Y') }}</p>
    <p><strong>Data finale:</strong> {{ Carbon\Carbon::parse(session('booking.end_date'))->format('d-m-Y') }}</p>
    <p><strong>Orario iniziale:</strong> {{ session('booking.start_time') }}</p>
    <p><strong>Orario finale:</strong> {{ session('booking.end_time') }}</p>
    <p><strong>Scrivania:</strong> {{ $desk->identifier }}</p>
    <p><strong>Piano:</strong> {{ $desk->plan->description }}</p>
    <p><strong>Sede:</strong> {{ $desk->plan->workplace->name }}</p>
    @if(session('booking.is_exclusive'))
        <p><span class="badge bg-warning text-dark">Prenotazione esclusiva dell'area</span></p>
    @endif

    <form action="{{ route('booking.complete') }}" method="POST">
        @csrf
        <a class="btn btn-warning" href="{{ route('booking.step.one') }}">Annulla</a>
        <button class="btn btn-success" type="submit">Conferma prenotazione</button>
    </form>
</div>
</x-app-layout>
