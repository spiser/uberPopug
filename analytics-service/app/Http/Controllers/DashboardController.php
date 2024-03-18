<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function view(Request $request): View
    {
        return view('dashboard', [
            'user' => $request->user(),
        ]);
    }
}
