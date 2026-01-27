@extends('bootstrap-italia::page')

@section('title', 'Bootstrap Italia')

@section('content')
<div class="container">
          <div class="row justify-content-center">
            <div class="col-lg-12 offset-lg-1">
            <div class="container p-0">
                <div class="row flex-column-reverse flex-lg-row">
                  <div class="col-12 pt-3">
                    <div class="cmp-card-latest-messages mb-3" data-bs-toggle="modal" data-bs-target="#">
                      <div class="card-flex shadow-sm px-4 pt-4 pb-1 rounded">
                        
                        
                        <div class="card-body p-0 my-2">
                          <h3 class="green-title-big t-primary mb-8"><a href="/booking" class="text-decoration-none" data-element="service-link">Prenota una postazione</a></h3>
                          <p class="text-paragraph">Tramite semplici passaggi scegli la tua scrivania.</p>
                        </div>
                      </div>
                    </div>
    
                    <div class="cmp-card-latest-messages mb-3 mb-30" data-bs-toggle="modal" data-bs-target="#">
                      <div class="card-flex shadow-sm px-4 pt-4 pb-4 rounded">
                        
                        
                        <div class="card-body p-0 my-2">
                          <h3 class="green-title-big t-primary mb-8"><a href="{{ route('bookings.current') }}" class="text-decoration-none" data-element="service-link">Vedi lo Prenotazioni Attuali</a></h3>
                          <p class="text-paragraph">Consulta l'elenco delle prenotazioni in corso.</p>
                        </div>
                      </div>
                    </div>

                    @if( Auth::user()->gestiamopresenze )
                      <div class="cmp-card-latest-messages mb-3 mb-30" data-bs-toggle="modal" data-bs-target="#">
                        <div class="card-flex shadow-sm px-4 pt-4 pb-4 rounded">
                          
                          
                          <div class="card-body p-0 my-2">
                            <h3 class="green-title-big t-primary mb-8"><a href="{{ route('presences.index') }}" class="text-decoration-none" data-element="service-link">Gestisci il tuo foglio presenze</a></h3>
                            <p class="text-paragraph">Programma le tue presenze/assenze in ufficio.</p>
                          </div>
                        </div>
                      </div>
                    @endif

                    <div class="cmp-card-latest-messages mb-3 mb-30" data-bs-toggle="modal" data-bs-target="#">
                      <div class="card-flex shadow-sm px-4 pt-4 pb-4 rounded">
                        
                        
                        <div class="card-body p-0 my-2">
                          <h3 class="green-title-big t-primary mb-8"><a href="{{ route('bookings.history') }}" class="text-decoration-none" data-element="service-link">Vedi lo storico prenotazioni</a></h3>
                          <p class="text-paragraph">Consulta l'elenco delle prenotazioni storiche.</p>
                        </div>
                      </div>
                    </div>

                    <div class="cmp-card-latest-messages mb-3 mb-30" data-bs-toggle="modal" data-bs-target="#">
                      <div class="card-flex shadow-sm px-4 pt-4 pb-4 rounded">
                        
                        
                        <div class="card-body p-0 my-2">
                          <h3 class="green-title-big t-primary mb-8"><a href="{{ route('desk.check') }}" class="text-decoration-none" data-element="service-link">Verifica disponibilità</a></h3>
                          <p class="text-paragraph">Tramite il codice della scrivania vedi se è libera in giornata.</p>
                        </div>
                      </div>
                    </div>
    
                    <div class="cmp-card-latest-messages mb-3 mb-30" data-bs-toggle="modal" data-bs-target="#">
                      <div class="card-flex shadow-sm px-4 pt-4 pb-4 rounded">
                        
                        
                        <div class="card-body p-0 my-2">
                          <h3 class="green-title-big t-primary mb-8"><a href="{{ route('contacts.index') }}" class="text-decoration-none" data-element="service-link">Cerca il collega</a></h3>
                          <p class="text-paragraph">Cerca il collega all'interno del dipartimento</p>
                        </div>
                      </div>
                    </div>
                    
                    <div class="cmp-card-latest-messages mb-3 mb-30" data-bs-toggle="modal" data-bs-target="#">
                      <div class="card-flex shadow-sm px-4 pt-4 pb-4 rounded">
                        
                        
                        <div class="card-body p-0 my-2">
                          <h3 class="green-title-big t-primary mb-8"><a href="{{ route('profile.edit') }}" class="text-decoration-none" data-element="service-link">Modifica le mie info</a></h3>
                          <p class="text-paragraph">Vuoi modificare il tuo cellulare nella rubrica del DTD o limitare la visualizzazione dei dati?</p>
                        </div>
                      </div>
                    </div>
                    
                    
                  </div>
                </div>
              </div>
    
              
            </div>
          </div>
        </div>
@stop

@section('css')
    <link rel="stylesheet" href="/css/custom.css">
@stop

@section('js')
    <script> console.log('Hi!'); </script>
@stop