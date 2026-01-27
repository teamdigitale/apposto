@extends('bootstrap-italia::page')

@section('title', 'welcome page')

@section('content')
        <div class="row">
            <div class="col-lg-6 order-2 order-lg-1">
              <div class="card mb-5">
                <div class="card-body pb-5 px-0">
                @auth <h2>Ciao  {{ Auth::user()->name }}</h2>  @endauth
                    <a class="text-decoration-none" href="#">
                    
                    <h3 class="card-title">Benvenuti in questo applicativo del dipartimento che permette di prenotare una postazione in una delle sue sedi.</h3>
                    </a>
                  <div class="col-lg-10 col-xl-8 offset-lg-1 offset-xl-2 text-center">
                
              </div>

                  @if (Route::has('login'))
                            <nav class="-mx-3 flex flex-1 justify-end">
                                @auth
                                    <a href="{{ url('/dashboard') }}" class="btn btn-primary mt-40" > Prenota </a>
                                @else
                                <p class="mb-4 pt-3 lora"><strong>Se hai già una credenziale puoi accedere </strong> 
                                    <a href="{{ route('login') }}" class="btn btn-primary mt-40"  > Accedi </a></p>
                                @endauth
                            </nav>
                        @endif

                        <p class="mb-4 pt-3 lora">
                        per dubbi o domande non evita a contattarci</p>
                </div>
              </div>
            </div>
            <div class="col-lg-6 order-1 order-lg-2 px-0 px-lg-3">
              <img src="https://picsum.photos/800/600" title="titolo immagine" alt="descrizione immagine" class="img-fluid">
            </div>
          </div>
          <section id="calendario">
        <div class="section section-muted pb-90 pb-lg-50 px-lg-5 pt-0">
         <div class="container">
            <div class="row row-calendar">
              <div class="it-carousel-wrapper it-carousel-landscape-abstract-four-cols it-calendar-wrapper splide splide--slide splide--ltr splide--draggable is-active is-initialized" data-bs-carousel-splide="" id="splide01">
                <div class="it-header-block">
                  <p class="m-4"><strong>se sei al primo accesso devi rigenerare la password tramite questo link <br/> </strong> </p>
                
                  <div class="it-header-block-title">
                  <a href="{{ route('password.request') }}" class="text-decoration-none text-white"><h3 class="mb-0 text-center home-carousel-title">Rigenerare Password</h3></a>
                  </div>
                </div>
                
            </div>
          </div>
        </div>
      </section>
@stop

@section('css')
    <link rel="stylesheet" href="/css/custom.css">
@stop

@section('js')
    <script> console.log('Hi!'); </script>
@stop