<x-app-layout>

<div class="container">
    <h2>Dubbi o domande? Contattaci</h2>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif


    <form action="{{ route('contact.send') }}" method="POST">
    @csrf
    <label for="name">Nome:</label>
    <input type="text" name="name" required>

    <label for="email">Email:</label>
    <input type="email" name="email" required>

    <label for="message">Messaggio:</label>
    <textarea name="message" required></textarea>

    <!-- CAPTCHA -->
    <div class="g-recaptcha" data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}"></div>

    <button type="submit" class="btn btn-primary mt-4">Invia</button>
</form>

<!-- Carica il JS di Google reCAPTCHA -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>

</div>
</x-app-layout>