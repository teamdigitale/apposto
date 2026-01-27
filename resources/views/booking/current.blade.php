<x-app-layout>
<div class="container">
    <h2>Prenotazioni in Corso</h2>
    
    <form id="multi-delete-form" action="{{ route('bookings.multiCancel') }}" method="POST">
        @csrf
        @method('POST')

        <table class="table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="select-all"></th> <!-- Checkbox principale -->
                    <th>Scrivania</th>
                    <th>Data Inizio</th>
                    <th>Data Fine</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bookings as $booking)
                    <tr>
                        <td><input type="checkbox" name="booking_ids[]" value="{{ $booking->id }}" class="booking-checkbox"></td>
                        <td>{{ $booking->desk->identifier }}</td>
                        <td>{{ Carbon\Carbon::parse($booking->from_date)->format('d-m-Y H:i') }}</td>
                        <td>{{ Carbon\Carbon::parse($booking->to_date)->format('d-m-Y H:i') }}</td>
                        <td>
                            <form action="{{ route('bookings.cancel', $booking->id) }}" method="POST" class="single-delete-form">
                                @csrf
                                @method('POST')
                                <button type="submit" class="btn btn-danger btn-sm">Cancella</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <button type="submit" class="btn btn-danger" id="delete-selected" disabled>Elimina Selezionati</button>
    </form>

    <div class="d-flex justify-content-center">
        {{ $bookings->links() }}  <!-- Paginazione -->
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const selectAll = document.getElementById("select-all");
    const checkboxes = document.querySelectorAll(".booking-checkbox");
    const deleteButton = document.getElementById("delete-selected");

    selectAll.addEventListener("change", function () {
        checkboxes.forEach(checkbox => checkbox.checked = selectAll.checked);
        toggleDeleteButton();
    });

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener("change", toggleDeleteButton);
    });

    function toggleDeleteButton() {
        const anyChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);
        deleteButton.disabled = !anyChecked;
    }
});
</script>
</x-app-layout>
