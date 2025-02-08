<x-app-layout>
<div class="container">
    <h2>Rubrica Colleghi</h2>

    <!-- Form di ricerca -->
    <form method="GET" action="{{ route('contacts.index') }}" class="mb-3">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Cerca per nome, email o telefono" value="{{ request('search') }}">
            <button type="submit" class="btn btn-primary">Cerca</button>
            <a class="btn btn-warning ml-2" href="{{ route('contacts.index') }}" role="button">Cancella filtro</a>
        </div>
    </form>

    <!-- Tabella risultati -->
    <table class="table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Email</th>
                <th>Telefono</th>
                <th>Team</th>
                <th>Desk</th>
            </tr>
        </thead>
        <tbody>
            @forelse($contacts as $contact)
          
                <tr>
                    <td>{{ $contact->name }}</td>
                    <td>{{ $contact->email }}</td>
                    <td>{{ $contact->phone }}</td>
                    <td>{{ $contact->team->label }}</td>
                    <td>  <?php  
                    if (count($contact->bookings_active) > 0){
                        $booking_desk = $contact->bookings_active->first()->desk;
                        echo $booking_desk->identifier. " - ". $booking_desk->plan->description . " - " .$booking_desk->plan->workplace->name;
                
            } else{
                echo "-";
            }?></td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center">Nessun contatto trovato.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Paginazione -->
    <div class="d-flex justify-content-center">
        {{ $contacts->links() }}
    </div>
</div>
</x-app-layout>