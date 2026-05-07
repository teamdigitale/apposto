<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    /**
     * Mostra la rubrica con filtro per nome, telefono ed email.
     */
    public function index(Request $request)
    {
        $query = User::where('allow_view', true)
            ->where('id', '<>', Auth::user()->id)
            // Eager load per evitare N+1 e crash su relazioni null
            ->with([
                'team',
                'defaultWorkstation',
                'bookings_active' => fn($q) => $q->with(['desk.plan.workplace']),
            ]);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%$search%")
                  ->orWhere('phone', 'LIKE', "%$search%")
                  ->orWhere('email', 'LIKE', "%$search%");
            });
        }

        $contacts = $query->paginate(10); // Paginazione con 10 risultati per pagina

        return view('contacts.index', compact('contacts'));
    }
}