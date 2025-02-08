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
                <div class="select-wrapper col-4">
                    <select id="start_time" name="start_time" class="mt-5" require>
                        <option selected="" value="">Da ora</option>
                        <option value="09:00">9:00</option>
                        <option value="09:30">9:30</option>
                        <option value="10:00">10:00</option>
                        <option value="10:30">10:30</option>
                        <option value="11:00">11:00</option>
                        <option value="11:30">11:30</option>
                        <option value="12:00">12:00</option>
                        <option value="12:30">12:30</option>
                        <option value="13:00">13:00</option>
                        <option value="13:30">13:30</option>
                        <option value="14:00">14:00</option>
                        <option value="14:30">14:30</option>
                        <option value="15:00">15:00</option>
                        <option value="15:30">15:30</option>
                        <option value="16:00">16:00</option>
                        <option value="16:30">16:30</option>
                        <option value="17:00">17:00</option>
                        <option value="17:30">17:30</option>
                        <option value="18:00">18:00</option>
                        <option value="18:30">18:30</option>
                        <option value="19:00">19:00</option>
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
                        <option value="9:00">9:00</option>
                        <option value="9:30">9:30</option>
                        <option value="10:00">10:00</option>
                        <option value="10:30">10:30</option>
                        <option value="11:00">11:00</option>
                        <option value="11:30">11:30</option>
                        <option value="12:00">12:00</option>
                        <option value="12:30">12:30</option>
                        <option value="13:00">13:00</option>
                        <option value="13:30">13:30</option>
                        <option value="14:00">14:00</option>
                        <option value="14:30">14:30</option>
                        <option value="15:00">15:00</option>
                        <option value="15:30">15:30</option>
                        <option value="16:00">16:00</option>
                        <option value="16:30">16:30</option>
                        <option value="17:00">17:00</option>
                        <option value="17:30">17:30</option>
                        <option value="18:00">18:00</option>
                        <option value="18:30">18:30</option>
                        <option value="19:00">19:00</option>
                    </select>
                </div>
                
                
            </div>
        </div>
        

        <button type="submit" class="btn btn-primary mt-4">Prossimo</button>
    </form>
    
    @section('js')
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script> console.log('XXXXXX!');
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
</script>
    @stop
</x-app-layout>
