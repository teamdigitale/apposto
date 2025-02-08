@extends('bootstrap-italia::page')

@section('title', 'Bootstrap Italia')
@livewireStyles
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
@section('css')
    <link rel="stylesheet" href="/css/custom.css">
@stop

@section('content')
<div class="container">
    
    <div class="card">
      <div class="card-header">
        Livewire Multipage form validation in Laravel
      </div>
      <div class="card-body">
        <livewire:wizard />
      </div>
    </div>
        
</div>
@stop



@section('js')
    <script> console.log('Hi!'); </script>
@stop