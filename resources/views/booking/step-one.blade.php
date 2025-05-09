<x-app-layout>

<div class="container-fluid">
          <div class="row">
            <div class="col-12 col-lg-10">
              <div class="cmp-hero">
                <section class="align-items-start">
                  <div class="it-hero-text-wrapper pt-0 ps-0 pb-lg-60">
                    
                    <h1 class="text-black" data-element="page-name">Step 1: Seleziona Sede e Date</h1>
                    <div class="hero-text">
                        <p>In pochi step riusciarai a prenotare la tua postazione, seleziona la data</p>
                    </div>  
                </div>
                </section>
              </div>        
            </div>
          </div>
        </div>
        @if($errors->any())
            @foreach($errors->all() as $error)
                <p> {{ $error }} </p>
            @endforeach
        @else
            <p></p>
        @endif
    <form action="{{ route('booking.step.two') }}" method="POST">
        @csrf
        <?php
        $time= date('H:i');
        list($hours, $minutes) = explode(":", $time);
        if($minutes >30){
            $minutes = '00';
            $hours +=1;
        }else{
            $minutes = '30';
        }
        $timestamp_new_start = ($hours * 3600) + ($minutes * 60);
        $timestamp_new_end = ($hours * 3600) + ($minutes * 60);
        ?>
        <div class="container-fluid">
            <div class="row">
                <div class="col-4 col-md-3">
                    <label for="workplace">Sede</label>
                </div>
                <div class="select-wrapper col-4">
                    <select class="form-select form-select-lg mb-3" name="workplace_id" required>
                        <option value="" disabled selected>Seleziona una sede</option>
                        @foreach ($workplaces as $workplace)
                            <option value="{{ $workplace->id }}">{{ $workplace->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-8 col-md-4">
                    <label for="start_date">Data @if(Auth::user()->team->allow_multi_day) Iniziale @endif</label>
                    <input type="date" id="start_date" name="start_date" required>
                </div>
                <?php
                        $array_start ="";
                        foreach ( range( 27000, 73800, 1800 ) as $timestamp ) {

                            $hour_mins = gmdate( 'H:i', $timestamp );
                            $array_start .="<option value='$hour_mins'>$hour_mins</option>";
                            
                        }

                        $array_end ="";
                        foreach ( range( 28800, 75600, 1800 ) as $timestamp ) {

                            $hour_mins = gmdate( 'H:i', $timestamp );
                            $array_end .="<option value='$hour_mins'>$hour_mins</option>";
                            
                        }

                    

                        ?>
                <div class="select-wrapper col-4">
                    <select id="start_time" name="start_time" class="mt-5" require>
                        <option selected="" value="">Da ora</option>
                        <?php echo $array_start;?>
                    </select>
                </div>
                
                @if(Auth::user()->team->allow_multi_day)
            </div>

            <div class="row mt-4">
                <div class="col-8 col-md-4">
                    <label for="end_date">Data Finale</label>
                    <input type="date" id="end_date" name="end_date" required>
                </div>
                @endif
                <div class="select-wrapper col-4">
                    <select id="end_time" name="end_time" class="mt-5" require>
                        <option selected="" value="">Ad ora</option>
                        <?php echo $array_end;?>
                    </select>
                </div>
                
                
            </div>
        </div>
        
        <input type="hidden" id="multiday" value="{{ Auth::user()->team->allow_multi_day ? '1' : '0' }}" />                
        <button type="submit" class="btn btn-primary mt-4">Prenota postazione generica</button>
        @if( Auth::user()->gestiamopresenze )
            <input type="hidden" id="deskprefix" value="{{ Auth::user()->default_workstation_id }}" />  
            <button class="btn btn-success  mt-4" type="submit" id="passnextButton" disabled>Prenotazione preferita veloce</button>
            <span id="error-message" class="text-danger mt-2 d-block" style="display:none;"></span>

        @endif
    </form>
    
    @section('js')
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script> console.log('XXXXXX!');
    </script>
    <script>
        $(document).ready(function () {
            const $startDate = $('#start_date');
            const $endDate = $('#end_date');
            const $startTime = $('#start_time');
            const $endTime = $('#end_time');
            const $passNextButton = $('#passnextButton');
            const $errorMessage = $('#error-message');
            const multiday = $('#multiday').val() === '1';

            function isDateRangeValid() {
                if (!multiday) return true;

                const start = new Date(`${$startDate.val()}T${$startTime.val()}`);
                const end = new Date(`${$endDate.val()}T${$endTime.val()}`);
                return end > start;
            }

            function checkAvailability() {
                const startDateVal = $startDate.val();
                const endDateVal = $endDate.val();
                const startTimeVal = $startTime.val();
                const endTimeVal = $endTime.val();

                const allFieldsFilled = multiday
                    ? (startDateVal && endDateVal && startTimeVal && endTimeVal)
                    : (startDateVal && startTimeVal && endTimeVal);

                if (!allFieldsFilled) {
                    $passNextButton.prop('disabled', true);
                    $errorMessage.hide();
                    return;
                }

                if (multiday && !isDateRangeValid()) {
                    $passNextButton.prop('disabled', true);
                    $errorMessage.text('⚠️ L\'intervallo di date e orari non è valido.').show();
                    return;
                }

                $errorMessage.hide();

                $.ajax({
                    url: "{{ route('booking.checkWorkstationAvailability') }}",
                    method: "POST",
                    contentType: "application/json",
                    data: JSON.stringify({
                        start_date: startDateVal,
                        end_date: endDateVal,
                        start_time: startTimeVal,
                        end_time: endTimeVal
                    }),
                    headers: {
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    success: function (response) {
                        if (response.available || response.can_override) {
                            $passNextButton.prop('disabled', false);
                            $errorMessage.hide();
                        } else {
                            $passNextButton.prop('disabled', true);
                            const by = response.occupied_by ? ` da ${response.occupied_by}` : '';
                            $errorMessage.text(`⚠️ Postazione occupata${by}.`).show();
                        }
                    },
                    error: function () {
                        $passNextButton.prop('disabled', true);
                        $errorMessage.text('⚠️ Errore nel controllo disponibilità.').show();
                    }
                });
            }

            $startDate.on('change', checkAvailability);
            $endDate.on('change', checkAvailability);
            $startTime.on('change', checkAvailability);
            $endTime.on('change', checkAvailability);

            $passNextButton.on('click', function (e) {
                e.preventDefault();

                const form = $('<form>', {
                    method: 'POST',
                    action: "{{ route('booking.complete') }}"
                });

                form.append($('<input>', { type: 'hidden', name: '_token', value: '{{ csrf_token() }}' }));

                const fields = {
                    desk_id: $('#deskprefix').val()
                };

                $.each(fields, function (name, value) {
                    form.append($('<input>', {
                        type: 'hidden',
                        name: name,
                        value: value || ''
                    }));
                });

                $('body').append(form);
                form.submit();
            });
        });
        </script>

    <script type="text/javascript">
        $(function(){
            var dtToday = new Date();
        
            var month = dtToday.getMonth() + 1;
            var day = dtToday.getDate();
            var year = dtToday.getFullYear();
            if(month < 10)
                month = '0' + month.toString();
            if(day < 10)
            day = '0' + day.toString();
            var maxDate = year + '-' + month + '-' + day;
            $('#start_date').attr('min', maxDate);
            $('#end_date').attr('min', maxDate);
        });
        document.addEventListener("DOMContentLoaded", function () {
    const dateInput = document.getElementById("start_date");
    const timeSelect = document.getElementById("start_time");

    function generateTimeOptions(startTimestamp) {
        timeSelect.innerHTML = "";  

        const maxTimestamp = 21 * 3600;  

        for (let timestamp = startTimestamp; timestamp <= maxTimestamp; timestamp += 1800) {
            let hourMins = new Date(timestamp * 1000).toISOString().substr(11, 5); // Formato HH:MM
            let option = document.createElement("option");
            option.value = hourMins;
            option.textContent = hourMins;
            timeSelect.appendChild(option);
        }
    }

    dateInput.addEventListener("change", function () {
        const selectedDate = new Date(dateInput.value);
        const today = new Date();
        
        if (selectedDate.toDateString() === today.toDateString()) {
            // Se la data è oggi, calcola l'ora arrotondata alla mezz'ora successiva
            let hours = today.getHours();
            let minutes = today.getMinutes();

            if (minutes > 30) {
                hours += 1;
                minutes = 0;
            } else {
                minutes = 30;
            }

            let startTimestamp = (hours * 3600) + (minutes * 60);
            generateTimeOptions(startTimestamp);
        } else {
            // Se non è oggi, mostra tutte le ore da 7:30 a 21:00
            generateTimeOptions(7.5 * 3600);
        }
    });

    // Inizializza con gli orari standard (dalle 7:30 alle 21:00)
    generateTimeOptions(7.5 * 3600);
});
</script>
    @stop
</x-app-layout>
