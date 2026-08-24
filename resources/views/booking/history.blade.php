<x-app-layout>
<div class="container">
    <h2>Storico prenotazioni</h2>

    @if($bookings->isEmpty())
        <p class="text-muted">Nessuna prenotazione nello storico.</p>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>Scrivania</th>
                    <th>Data inizio</th>
                    <th>Data fine</th>
                    <th>Stato</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bookings as $booking)
                    <tr>
                        <td>{{ $booking->desk->identifier ?? '—' }}</td>
                        <td>{{ Carbon\Carbon::parse($booking->from_date)->format('d-m-Y H:i') }}</td>
                        <td>{{ Carbon\Carbon::parse($booking->to_date)->format('d-m-Y H:i') }}</td>
                        <td>
                            @php
                                $status = \App\Enums\BookingStatus::tryFrom((int) $booking->status);
                            @endphp
                            @switch($status)
                                @case(\App\Enums\BookingStatus::Confirmed)
                                    <span class="badge bg-success text-white">Confermata</span>
                                    @break
                                @case(\App\Enums\BookingStatus::Cancelled)
                                    <span class="badge bg-danger text-white">Cancellata</span>
                                    @break
                                @case(\App\Enums\BookingStatus::Overridden)
                                    <span class="badge bg-warning text-dark">Sostituita</span>
                                    @break
                                @default
                                    <span class="badge bg-info text-white">Conclusa</span>
                            @endswitch
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="d-flex justify-content-center">
            {{ $bookings->links() }}
        </div>
    @endif
</div>
</x-app-layout>
