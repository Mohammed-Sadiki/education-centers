<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Affiche le formulaire de connexion.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            if (Auth::user()->isSuperAdmin()) {
                return redirect()->route('super-admin.domaines');
            }
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Traite la tentative de connexion de l'utilisateur.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'L\'adresse e-mail est obligatoire.',
            'email.email'       => 'Veuillez saisir une adresse e-mail valide.',
            'password.required' => 'Le mot de passe est obligatoire.',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            if (Auth::user()->isSuperAdmin()) {
                return redirect()->intended(route('super-admin.domaines'))
                    ->with('success', 'Connexion réussie. Bienvenue !');
            }

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Connexion réussie. Bienvenue !');
        }

        return back()->withErrors([
            'email' => 'Ces identifiants ne correspondent à aucun compte actif.',
        ])->onlyInput('email');
    }

    /**
     * Déconnecte l'utilisateur et invalide sa session.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Vous avez été déconnecté avec succès.');
    }
}
