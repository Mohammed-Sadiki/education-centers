<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Affiche le tableau de bord temporaire avec les informations de l'utilisateur.
     */
    public function index(Request $request): View
    {
        $user = $request->user()->load('center');

        return view('dashboard', [
            'user' => $user,
        ]);
    }
}
