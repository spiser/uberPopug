<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkerController extends Controller
{
    public function view(Request $request): View
    {
        $user = $request->user();

        if ($request->role !== UserRole::worker) {
            throw new \Exception('Вы не Рабочий! Уходите!');
        }

        return view('worker.view', [
            'balance' => $user->balance,
            'transactions' => $user->transactions,
        ]);
    }
}
