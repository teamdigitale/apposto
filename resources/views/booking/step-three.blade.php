<x-app-layout>

<h1>Riepilogo Prenotazione</h1><br />

<p><strong>Data Iniziale:</strong> {{ session('booking.start_date') }}</p>
<p><strong>Data Finale:</strong> {{ session('booking.end_date') }}</p>
<p><strong>Orario Iniziale:</strong> {{ session('booking.start_time') }}</p>
<p><strong>Orario Finale:</strong> {{ session('booking.end_time') }}</p>
<p><strong>Scrivania:</strong> {{ $desk->identifier }}</p>
<p><strong>Piano:</strong> {{ $desk->plan->description }}</p>
<p><strong>Sede:</strong> {{ $desk->plan->workplace->name }}</p>

<form action="{{ route('booking.complete') }}" method="POST">
    @csrf
    <input type="hidden" name="desk_id" value="{{ $desk->id }}">
    <button class="btn-success btn"type="submit">Conferma Prenotazione</button>
</form>

</x-app-layout>
