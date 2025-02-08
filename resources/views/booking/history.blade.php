<x-app-layout>
<div class="container">
    <h2>Storico Prenotazioni</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Scrivania</th>
                <th>Data Inizio</th>
                <th>Data Fine</th>
                <th>Stato</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bookings as $booking)
                <tr>
                    <td>{{ $booking->desk->identifier }}</td>
                    <td>{{ Carbon\Carbon::parse($booking->from_date)->format('d-m-Y h:m') }}</td>
                    <td>{{ Carbon\Carbon::parse($booking->to_date)->format('d-m-Y h:m') }}</td>
                    <td>
                        @if($booking->status == 0)
                            <span class="badge bg-success text-white">Confermata</span>
                        @elseif($booking->status == 1)
                            <span class="badge bg-danger text-white">Cancellata</span>
                        @elseif($booking->status == 2)
                            <span class="badge bg-warning text-white">Rubata</span>
                        @else
                            <span class="badge bg-info text-white">Conclusa</span>
                        @endif
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