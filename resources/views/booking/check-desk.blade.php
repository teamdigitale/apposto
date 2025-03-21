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
    <div id="availableHours" class="mt-3"></div>
</div>

@section('js')
<script>
document.getElementById("checkDeskForm").addEventListener("submit", function(event) {
    event.preventDefault();

    let deskIdentifier = document.getElementById("desk_identifier").value;
    let date = document.getElementById("date").value;

    fetch("{{ route('desk.checkAvailability') }}", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ desk_identifier: deskIdentifier, date: date })
    })
    .then(response => response.json())
    .then(data => {
        let resultDiv = document.getElementById("result");
        let availableHoursDiv = document.getElementById("availableHours");

        if (data.success) {
            console.log(data);
            if (data.availableHours.length == 0) {
                resultDiv.innerHTML = `<div class="alert alert-danger">La scrivania <strong>${data.desk.identifier}</strong> è <strong>OCCUPATA</strong> per la data selezionata.</div>`;
                availableHoursDiv.innerHTML = ""; // Svuotiamo gli orari disponibili
            } else {
                resultDiv.innerHTML = `<div class="alert alert-success">La scrivania <strong>${data.desk.identifier}</strong> è <strong>DISPONIBILE</strong> per la data selezionata.</div>`;

                // Mostriamo gli orari disponibili
                if (data.availableHours.length == 27) {
                    availableHoursDiv.innerHTML = `<div class="alert alert-warning">Disponibile tutto il giorno</div>`;
                }else{
                    let hoursList = data.availableHours.map(hour => `<li class="list-group-item">${hour}</li>`).join('');
                    availableHoursDiv.innerHTML = `
                        <h5>Orari disponibili:</h5>
                        <ul class="list-group">${hoursList}</ul>
                    `;
                } 
            }
        } else {
            resultDiv.innerHTML = `<div class="alert alert-warning">${data.message}</div>`;
            availableHoursDiv.innerHTML = "";
        }
    })
    .catch(error => {
        console.error("Errore:", error);
        document.getElementById("result").innerHTML = `<div class="alert alert-danger">Errore nella verifica della scrivania.</div>`;
        document.getElementById("availableHours").innerHTML = "";
    });
});
</script>
@stop
</x-app-layout>