<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class OtherController extends Controller
{
    public function showForm()
    {
        return view('other.index');
    }

    public function send(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'message' => 'required',
            'g-recaptcha-response' => 'required',
        ]);

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => config('services.recaptcha.secret_key'),
            'response' => $request->input('g-recaptcha-response'),
        ]);

        $captchaSuccess = $response->json()['success'] ?? false;

        if (! $captchaSuccess) {
            return back()->withErrors(['captcha' => 'Verifica CAPTCHA fallita, riprova.']);
        }

        Mail::raw("Messaggio da {$request->name} ({$request->email}): \n\n{$request->message}", function ($mail) {
            $mail->to(config('services.assistenza.email'))->subject('Richiesta di Assistenza');
        });

        return back()->with('success', 'Messaggio inviato con successo!');
    }
}
