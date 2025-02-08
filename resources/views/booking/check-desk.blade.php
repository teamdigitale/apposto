<x-app-layout>
@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@stop

<div class="container">
    <h2>Verifica Disponibilità Scrivania</h2>
    <form id="checkDeskForm">
        @csrf
        <div class="mb-3">
            <label for="desk_identifier" class="form-label">Codice Scrivania</label>
            <input type="text" class="form-control" id="desk_identifier" name="desk_identifier" required>
        </div>
        <div class="mb-3">
            <label for="date" class="form-label">Data</label>
            <input type="date" class="form-control" id="date" name="date" required>
        </div>
        <button type="submit" class="btn btn-primary">Verifica</button>
    </form>

    <div id="result" class="mt-4"></div>
</div>

@section('js')
<script>
document.getElementById("checkDeskForm").addEventListener("submit", function(event) {
    event.preventDefault();

    let deskIdentifier = document.getElementById("desk_identifier").value;
    let date = document.getElementById("date").value;

    fetch("{{ route('desk.checkAvailability') }}?desk_identifier=" + deskIdentifier + "&date=" + date, {
        method: "GET",
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content")
        }
    })
    .then(response => response.json())
    .then(data => {
        let resultDiv = document.getElementById("result");

        if (data.success) {
            if (data.isOccupied) {
                resultDiv.innerHTML = `<div class="alert alert-danger">La scrivania <strong>${data.desk.identifier}</strong> è <strong>OCCUPATA</strong> per la data selezionata.</div>`;
            } else {
                resultDiv.innerHTML = `<div class="alert alert-success">La scrivania <strong>${data.desk.identifier}</strong> è <strong>DISPONIBILE</strong> per la data selezionata.</div>`;
            }
        } else {
            resultDiv.innerHTML = `<div class="alert alert-warning">${data.message}</div>`;
        }
    })
    .catch(error => {
        console.error("Errore:", error);
        document.getElementById("result").innerHTML = `<div class="alert alert-danger">Errore nella verifica della scrivania.</div>`;
    });
});
</script>
@stop
</x-app-layout>