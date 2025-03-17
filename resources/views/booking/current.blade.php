<x-app-layout>
<div class="container">
    <h2>Prenotazioni in Corso</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Scrivania</th>
                <th>Data Inizio</th>
                <th>Data Fine</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bookings as $booking)
                <tr>
                    <td>{{ $booking->desk->identifier }}</td>
                    <td>{{ Carbon\Carbon::parse($booking->from_date)->format('d-m-Y H:i') }}</td>
                    <td>{{ Carbon\Carbon::parse($booking->to_date)->format('d-m-Y H:i') }}</td>
                    <td>
                        <form action="{{ route('bookings.cancel', $booking->id) }}" method="POST">
                            @csrf
                            @method('POST')
                            <button type="submit" class="btn btn-danger btn-sm">Cancella</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="d-flex justify-content-center">
        {{ $bookings->links() }}  <!-- Paginazione -->
    </div>
</div>

</x-app-layout>