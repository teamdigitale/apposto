<x-app-layout>
<div class="container">
    <h2>Prenotazioni in corso</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($bookings->isEmpty())
        <p class="text-muted">Non hai prenotazioni in corso.</p>
        <a href="{{ route('booking.step.one') }}" class="btn btn-primary">Prenota una postazione</a>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="select-all" form="multi-delete-form"></th>
                    <th>Scrivania</th>
                    <th>Data inizio</th>
                    <th>Data fine</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bookings as $booking)
                    <tr>
                        <td>
                            <input type="checkbox"
                                   name="booking_ids[]"
                                   value="{{ $booking->id }}"
                                   class="booking-checkbox"
                                   form="multi-delete-form">
                        </td>
                        <td>{{ $booking->desk->identifier ?? '—' }}</td>
                        <td>{{ Carbon\Carbon::parse($booking->from_date)->format('d-m-Y H:i') }}</td>
                        <td>{{ Carbon\Carbon::parse($booking->to_date)->format('d-m-Y H:i') }}</td>
                        <td>
                            <form action="{{ route('bookings.cancel', $booking) }}" method="POST" onsubmit="return confirm('Cancellare questa prenotazione?')">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-sm">Cancella</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <form id="multi-delete-form" action="{{ route('bookings.multiCancel') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-danger" id="delete-selected" disabled>Elimina selezionati</button>
        </form>

        <div class="d-flex justify-content-center mt-3">
            {{ $bookings->links() }}
        </div>
    @endif
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const selectAll = document.getElementById("select-all");
    const checkboxes = document.querySelectorAll(".booking-checkbox");
    const deleteButton = document.getElementById("delete-selected");

    if (!selectAll || !deleteButton) {
        return;
    }

    selectAll.addEventListener("change", function () {
        checkboxes.forEach(checkbox => checkbox.checked = selectAll.checked);
        toggleDeleteButton();
    });

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener("change", toggleDeleteButton);
    });

    function toggleDeleteButton() {
        deleteButton.disabled = !Array.from(checkboxes).some(checkbox => checkbox.checked);
    }
});
</script>
</x-app-layout>
