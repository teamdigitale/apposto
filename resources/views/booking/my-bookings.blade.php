<x-app-layout>
<h1>Le mie prenotazioni</h1><br />
@if($bookings->isEmpty())
    <p>Non hai prenotazioni.</p>
@else
    <table class="table">
        <thead>
            <tr>
                <th>Scrivania</th>
                <th>Piano</th>
                <th>Sede</th>
                <th>Data Inizio</th>
                <th>Data Fine</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bookings as $booking)
                <tr>
                    <td>{{ $booking->desk->identifier }}</td>
                    <td>{{ $booking->desk->plan->description }}</td>
                    <td>{{ $booking->desk->plan->workplace->name }}</td>
                    <td>{{ Carbon\Carbon::parse($booking->from_date)->format('d-m-Y H:i') }} </td>
                    <td> {{ Carbon\Carbon::parse($booking->to_date)->format('d-m-Y H:i') }} </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif


</x-app-layout>
