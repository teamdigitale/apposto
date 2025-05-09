<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class CheckRouteSecurity extends Command
{
    protected $signature = 'security:check-routes';
    protected $description = 'Verifica se le rotte hanno protezioni di sicurezza come auth, can, role, ecc.';

    public function handle(): void
    {
        $routes = Route::getRoutes();

        $this->info("Controllo rotte per sicurezza...\n");

        foreach ($routes as $route) {
            $name = $route->getName() ?? '—';
            $uri = $route->uri();
            $method = implode('|', $route->methods());
            $middleware = collect($route->middleware());

            $secure = $middleware->contains(fn($m) => str_contains($m, 'auth') || str_contains($m, 'can') || str_contains($m, 'role'));

            if (!$secure && !str_starts_with($uri, 'api')) {
                $this->warn("⚠️  [$method] /$uri ($name) — Nessun middleware di sicurezza");
            } else {
                $this->line("✔️  [$method] /$uri ($name)");
            }
        }

        $this->info("\nControllo completato.");
    }
}