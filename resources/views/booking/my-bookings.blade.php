<x-app-layout>
<h1>Le mie prenotazioni</h1>
<span class="bg-primary text-white p-1 align-baseline">Le mie Prenotazioni </span><br />
@if($bookings->isEmpty())
    <p>Non hai prenotazioni.</p>
@else
    <ul>
        @foreach ($bookings as $booking)
            <li style="margin-top:1rem">
            <strong>Scrivania:</strong> {{ $booking->desk->identifier }} <br>
            <strong>Piano:</strong> {{ $booking->desk->plan->description }} <br>
            <strong>Sede:</strong> {{ $booking->desk->plan->workplace->name }} <br>
            <strong>Data da:</strong> {{ Carbon\Carbon::parse($booking->from_date)->format('d-m-Y h:m') }} <br>
            <strong>Data a:</strong> {{ Carbon\Carbon::parse($booking->to_date)->format('d-m-Y h:m') }} <br>
              
            </li>
        @endforeach
    </ul>
@endif


</x-app-layout>
