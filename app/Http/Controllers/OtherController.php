<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

class OtherController extends Controller
{
    public function showForm()
    {
        
        return view('other.index');
    }

    public function send(Request $request)
    {
        // Validazione
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'message' => 'required',
            'g-recaptcha-response' => 'required',
        ]);

        // Verifica il CAPTCHA
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => env('RECAPTCHA_SECRET_KEY'),
            'response' => $request->input('g-recaptcha-response'),
        ]);

        $captchaSuccess = $response->json()['success'] ?? false;

        if (!$captchaSuccess) {
            return back()->withErrors(['captcha' => 'Verifica CAPTCHA fallita, riprova.']);
        }

        // Invia l'email
        Mail::raw("Messaggio da {$request->name} ({$request->email}): \n\n{$request->message}", function ($mail) use ($request) {
            $mail->to(env('MAIL_ASSISTENZA'))->subject('Richiesta di Assistenza');
        });

        return back()->with('success', 'Messaggio inviato con successo!');
    }
}
