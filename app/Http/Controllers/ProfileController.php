<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Desk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $availableWorkstations = Desk::where(function ($query) use ($user) {
            $query->whereDoesntHave('users', function ($subQuery) {
                $subQuery->whereNotNull('default_workstation_id');
            })->orWhere('id', $user->default_workstation_id);
        })->get();
        return view('profile.edit', compact('user', 'availableWorkstations'));
    }

    /**
     * Update the user's profile information.
     */ 
    public function update(Request $request): RedirectResponse
    {
        
        $user = $request->user();

        $allow_view = $request->input('allow_view') ? 1 : 0;

        $validated = $request->validate([
            'phone' => 'nullable|string|max:255',
            'default_workstation_id' => 'nullable|exists:desks,id',
        ]);

        // Controllo postazione occupata (solo se diversa da quella già assegnata)
        if (
            $validated['default_workstation_id'] &&
            $validated['default_workstation_id'] != $user->default_workstation_id
        ) {
            $alreadyAssigned = \App\Models\User::where('default_workstation_id', $validated['default_workstation_id'])
                ->where('id', '!=', $user->id)
                ->exists();

            if ($alreadyAssigned) {
                return back()->withErrors([
                    'default_workstation_id' => 'Questa postazione è già assegnata a un altro utente.',
                ]);
            }
        }

        // Aggiorna i dati utente
        $user->update([
            'allow_view' => $allow_view,
            'phone' => $validated['phone'],
            'default_workstation_id' => $validated['default_workstation_id'],
        ]);

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
