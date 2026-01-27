<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLivewireIsProtected
{
    public function handle(Request $request, Closure $next)
    {
        // logger('Livewire request unauthenticated: ' . $request->path());

        return $next($request);
    }
}
