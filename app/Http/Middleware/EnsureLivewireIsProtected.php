<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLivewireIsProtected
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('livewire/*') && !auth()->check()) {
            abort(403, 'Accesso non autorizzato');
        }

        return $next($request);
    }
}
